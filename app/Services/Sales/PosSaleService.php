<?php
namespace App\Services\Sales;
use App\{Sale,SaleDetail,Output,StockItem,Worker,Audit};
use App\Services\{SettingService,BranchPriceListService};
use App\Services\Workers\WorkerUserLink;
use App\Support\TenantContext as Context;
use Illuminate\Support\Facades\{DB,Auth};
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    private function fail($message): void {throw ValidationException::withMessages(['sale'=>$message]);}
    public function store($request): array
    {
        app(PosCatalogReadService::class)->authorize();
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        $request->validate(['pos_request_key'=>'required|string|regex:/^[a-f0-9]{32}$/D',
            'items'=>'required|string|max:300000','invoice_type'=>'required|in:ninguno,ticket,boleta,factura',
            'pagos_parciales_venta'=>'required|in:s,n']);
        $rows=json_decode($request->input('items'),true);
        if(!is_array($rows)||!$rows||count($rows)>100||array_keys($rows)!==range(0,count($rows)-1))$this->fail('El carrito debe contener entre 1 y 100 líneas.');
        foreach($rows as$row){
            if(!is_array($row)||!isset($row['productId'])||!is_scalar($row['productId'])||!preg_match('/^[1-9][0-9]*$/D',(string)$row['productId']))$this->fail('StockItem inválido en el carrito.');
        }
        return DB::transaction(function()use($request,$rows){
            $identity=app(WorkerUserLink::class)->saleIdentity(); // Locks registering user; worker may be null.
            $existing=Sale::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
                ->where('branch_id',Context::branchId())->where('pos_request_key',$request->input('pos_request_key'))->lockForUpdate()->first();
            if($existing){
                if((int)$existing->created_by!==Auth::id())$this->fail('La clave de esta operación corresponde a otro usuario.');
                return ['sale'=>$existing,'replayed'=>true];
            }
            $settings=app(SettingService::class);
            $partial=$request->input('pagos_parciales_venta')==='s';
            if($partial&&!$settings->get('sales.allow_partial_payments'))$this->fail('La empresa no permite pagos parciales.');
            $list=app(PosSalePriceReference::class)->begin($request);
            $currency=(string)$settings->get('finance.base_currency');
            if(!in_array($currency,['PEN','USD'],true)||(string)$list->currency!==$currency)$this->fail('La moneda de la lista debe coincidir con la moneda de venta configurada en la empresa.');
            if($request->input('pos_currency')!==$currency)$this->fail('La moneda cambió desde que abrió el POS. Recargue la pantalla.');
            $ids=array_unique(array_map(function($v){return(int)$v['productId'];},$rows));sort($ids);
            $items=app(PosCatalogReadService::class)->catalog()->whereIn('stock_items.id',$ids)->orderBy('stock_items.id')
                ->with(['material.typeTax'])->lockForUpdate()->get()->keyBy('id');
            if($items->count()!==count($ids))$this->fail('Uno de los productos ya no está activo o habilitado para esta empresa.');
            $calculator=app(PosLineCalculator::class);$lines=[];
            foreach($rows as$row)$lines[]=$calculator->line($row,$items->get((int)$row['productId']),$list);
            if($request->has('total_descuentos')&&bccomp($calculator->decimal($request->input('total_descuentos')),'0',4)!==0)$this->fail('Los descuentos están deshabilitados.');
            $totals=$calculator->totals($lines);
            // Ignore submitted aggregate totals as a source of truth; reject stale UI totals.
            if($request->has('total_importe')&&bccomp($calculator->cents($calculator->decimal($request->input('total_importe'))),$totals['total'],2)!==0)$this->fail('El total cambió o no coincide con los precios configurados. Actualice el carrito.');
            $cash=app(PosCashService::class);$payment=$cash->prepare($request,$currency,$totals['total'],$partial);
            $worker=null;
            if($request->filled('worker_id')){
                $value=$request->input('worker_id');
                if(!is_scalar($value)||!preg_match('/^[1-9][0-9]*$/D',(string)$value))$this->fail('Trabajador inválido.');
                $worker=$this->workers()->where('id',$value)->lockForUpdate()->first();
                if(!$worker)$this->fail('El trabajador no está activo o no pertenece a esta empresa/local.');
            }elseif($identity['worker_id']){
                $worker=$this->workers()->where('id',$identity['worker_id'])->first();
            }
            $document=$this->document($request,$partial);
            if($document['type_document']!==null){
                $rates=[];
                foreach($lines as$line)if($line['tax']['kind']==='gravada')$rates[(string)(float)$line['tax']['rate']]=true;
                if(count($rates)>1)$this->fail('Separe en comprobantes distintos los productos con diferentes tasas de IGV.');
            }
            $context=['tenant_id'=>Context::tenantId(),'company_id'=>Context::companyId(),'branch_id'=>Context::branchId()];
            $sale=Sale::create(array_merge($context,$document,[
                'pos_request_key'=>$request->input('pos_request_key'),'date_sale'=>now(),'serie'=>bin2hex(random_bytes(5)),
                'created_by'=>$identity['created_by'],'created_by_name'=>$identity['created_by_name'],'worker_id'=>$worker?$worker->id:null,
                'caja'=>$worker?$worker->id:null,'currency'=>$currency,'free_sale'=>false,
                'op_exonerada'=>$totals['exonerada'],'op_inafecta'=>$totals['inafecta'],'op_gravada'=>$totals['gravada'],
                'igv'=>$totals['igv'],'total_descuentos'=>0,'importe_total'=>$totals['total'],'vuelto'=>$partial?'0.00':$payment['change'],
                'dispatch_status'=>$partial?'pendiente':'despachado','pagos_parciales_venta'=>$partial?'s':'n','emission_status'=>'not_requested',
            ]));
            $output=Output::create(array_merge($context,['execution_order'=>'VENTA DE POS','request_date'=>$sale->date_sale,
                'requesting_user'=>Auth::id(),'responsible_user'=>Auth::id(),'state'=>'confirmed','indicator'=>'or']));
            $used=[];
            foreach($lines as$line){
                $detail=SaleDetail::create(array_merge(['tenant_id'=>Context::tenantId(),'company_id'=>Context::companyId()],$line['snapshot'],[
                    'sale_id'=>$sale->id,'material_id'=>$line['item']->material_id,'stock_item_id'=>$line['item']->id,
                    'description'=>$line['item']->display_name?:$line['item']->material->full_name,
                    'material_presentation_id'=>$line['presentation']?$line['presentation']->id:null,
                    'presentation_label_snapshot'=>$line['presentationLabel'],
                    'type_tax_id'=>$line['item']->material->type_tax_id,'tax_kind'=>$line['tax']['kind'],'tax_provider_code'=>$line['tax']['provider'],
                    'tax_rate_snapshot'=>$line['tax']['rate'],
                    'quantity'=>$line['units'],'packs'=>$line['packs'],'units_per_pack'=>$line['perPack'],
                    'price'=>$line['price'],'valor_unitario'=>bcdiv($line['price'],$line['factor'],10),
                    'percentage_tax'=>$line['tax']['rate'],'tax_amount'=>$line['taxAmount'],'total'=>$line['gross'],'discount'=>0,
                ]));
                app(PosInventoryService::class)->consume($line,$detail,$output,$used);
            }
            $cash->post($sale,$payment);
            Audit::create(['user_id'=>Auth::id(),'action'=>'Guardar venta POS contextual','time'=>0]);
            return ['sale'=>$sale,'replayed'=>false];
        },3);
    }
    private function workers()
    {
        return Worker::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())->where('enable',true)
            ->where(function($q){$q->whereNull('branch_id')->orWhere('branch_id',Context::branchId());});
    }
    private function document($request,bool $partial): array
    {
        if($partial||in_array($request->input('invoice_type'),['ticket','ninguno'],true))return ['type_document'=>null,'nombre_cliente'=>'VENTA DIRECTA'];
        if($request->input('invoice_type')==='factura'){
            $request->validate(['ruc'=>'required|regex:/^\d{11}$/D','razon_social'=>'required|string|max:250',
                'direccion_fiscal'=>'required|string|max:500','email_invoice_factura'=>'nullable|email|max:250']);
            return ['type_document'=>'01','numero_documento_cliente'=>$request->input('ruc'),'tipo_documento_cliente'=>'6',
                'nombre_cliente'=>$request->input('razon_social'),'direccion_cliente'=>$request->input('direccion_fiscal'),'email_cliente'=>$request->input('email_invoice_factura')];
        }
        $request->validate(['dni'=>'required|regex:/^\d{8}$/D','name'=>'required|string|max:250','address'=>'nullable|string|max:500','email_invoice_boleta'=>'nullable|email|max:250']);
        return ['type_document'=>'03','numero_documento_cliente'=>$request->input('dni'),'tipo_documento_cliente'=>'1',
            'nombre_cliente'=>$request->input('name'),'direccion_cliente'=>$request->input('address'),'email_cliente'=>$request->input('email_invoice_boleta')];
    }
}
