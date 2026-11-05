<?php
namespace App\Services\Sales;
use App\{Item,StockLot,InventoryLevel,OutputDetail};
use App\Support\TenantContext as Context;
use Illuminate\Support\Facades\DB;

class PosInventoryService
{
    private function fail($message): void {app(PosLineCalculator::class)->fail($message);}
    public function lots($query)
    {
        return $query->where('stock_lots.tenant_id',Context::tenantId())->where('stock_lots.company_id',Context::companyId())
            ->whereExists(function($q){$q->selectRaw('1')->from('warehouses as pos_w')->whereColumn('pos_w.id','stock_lots.warehouse_id')
                ->where('pos_w.tenant_id',Context::tenantId())->where('pos_w.company_id',Context::companyId())->where('pos_w.branch_id',Context::branchId());})
            ->whereExists(function($q){$q->selectRaw('1')->from('locations as pos_l')->whereColumn('pos_l.id','stock_lots.location_id')
                ->whereColumn('pos_l.warehouse_id','stock_lots.warehouse_id')->where('pos_l.tenant_id',Context::tenantId())->where('pos_l.company_id',Context::companyId());});
    }
    public function items($query,int $stockId)
    {
        return $query->where('items.tenant_id',Context::tenantId())->where('items.company_id',Context::companyId())
            ->where('items.stock_item_id',$stockId)->where('items.state_item','entered')->where('items.percentage',1)
            ->whereExists(function($q){$q->selectRaw('1')->from('stock_items as physical_stock')
                ->whereColumn('physical_stock.id','items.stock_item_id')->whereColumn('physical_stock.material_id','items.material_id')
                ->where('physical_stock.tenant_id',Context::tenantId());})
            ->whereExists(function($q)use($stockId){
                $q->selectRaw('1')->from('stock_lots')->whereColumn('stock_lots.id','items.stock_lot_id')
                    ->whereColumn('stock_lots.warehouse_id','items.warehouse_id')->whereColumn('stock_lots.location_id','items.location_id')
                    ->where('stock_lots.stock_item_id',$stockId)->whereRaw('qty_on_hand - qty_reserved >= 1');
                $this->lots($q);
            });
    }
    public function consume(array $line,$detail,$output,array &$used): void
    {
        $item=$line['item'];$stockId=(int)$item->id;$quantity=$line['units'];
        if(!$item->tracks_inventory)$this->fail('Este flujo POS requiere un producto inventariable con existencias locales.');
        $lots=$this->lots(StockLot::query())->where('stock_item_id',$stockId)
            ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')->orderBy('expiration_date')->orderBy('id')->lockForUpdate()->get();
        foreach($lots as$lot){
            if(bccomp((string)$lot->qty_on_hand,'0',4)<0||bccomp((string)$lot->qty_reserved,'0',4)<0||bccomp((string)$lot->qty_reserved,(string)$lot->qty_on_hand,4)>0)$this->fail('El lote tiene saldos inconsistentes.');
        }
        $selected=collect();
        if((int)$item->material->tipo_venta_id===3){
            $ids=$line['row']['selected_item_ids']??[];
            if(is_string($ids))$ids=json_decode($ids,true);
            if(!is_array($ids)||count($ids)!==(int)$quantity)$this->fail('Seleccione exactamente los Items físicos de esta línea.');
            foreach($ids as$id){
                if(!is_scalar($id)||!preg_match('/^[1-9][0-9]*$/D',(string)$id)||isset($used[(string)$id]))$this->fail('Item físico inválido o repetido.');
                $used[(string)$id]=true;
            }
            $selected=$this->items(Item::query(),$stockId)->whereIn('items.id',$ids)->orderBy('items.id')->lockForUpdate()->get();
            if($selected->count()!==count($ids))$this->fail('Uno de los Items ya no está disponible en el local actual.');
        }
        $plan=[];$remaining=$quantity;
        foreach($lots as$lot){
            $available=bcsub((string)$lot->qty_on_hand,(string)$lot->qty_reserved,4);
            $physical=$selected->where('stock_lot_id',$lot->id);
            $take=$selected->isNotEmpty()?(string)$physical->count():(bccomp($remaining,$available,4)<0?$remaining:$available);
            if(bccomp($take,'0',4)<=0)continue;
            if(bccomp($take,$available,4)>0)$this->fail('El lote no tiene disponibilidad libre de reservas.');
            $level=InventoryLevel::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
                ->where('stock_item_id',$stockId)->where('warehouse_id',$lot->warehouse_id)->where('location_id',$lot->location_id)->lockForUpdate()->first();
            if(!$level||bccomp(bcsub((string)$level->qty_on_hand,(string)$level->qty_reserved,4),$take,4)<0)$this->fail('El nivel de inventario no tiene disponibilidad coherente con el lote.');
            $plan[]=[$lot,$level,$take,$physical];$remaining=bcsub($remaining,$take,4);
            if(bccomp($remaining,'0',4)===0)break;
        }
        if(bccomp($remaining,'0',4)!==0)$this->fail('Stock insuficiente en los almacenes del local actual.');
        $totalCost='0';
        foreach($plan as[$lot,$level,$take,$physical]){
            $costPerUnit=$this->cost($lot->unit_cost??0);
            if($physical->isNotEmpty()){
                foreach($physical as$physicalItem){
                    $cost=$this->cost($physicalItem->unit_cost??$costPerUnit);
                    $this->output($output,$detail,$line,$lot,'1',$cost,$physicalItem);
                    $totalCost=bcadd($totalCost,$cost,8);$physicalItem->state_item='exited';$physicalItem->save();
                }
            }else{
                $this->output($output,$detail,$line,$lot,$take,$costPerUnit,null);$totalCost=bcadd($totalCost,bcmul($take,$costPerUnit,8),8);
            }
            $lot->qty_on_hand=bcsub((string)$lot->qty_on_hand,$take,4);$lot->save();
            // Subtract precisely the consumed amount; do not rebuild global quantities.
            $level->refresh();
            if(bccomp(bcsub((string)$level->qty_on_hand,(string)$level->qty_reserved,4),$take,4)<0)$this->fail('El nivel de inventario no cubre el consumo acumulado de los lotes.');
            $level->qty_on_hand=bcsub((string)$level->qty_on_hand,$take,4);$level->save();
        }
        $detail->unit_cost=bcdiv($totalCost,$quantity,8);$detail->total_cost=$totalCost;
        $detail->item_snapshot=$selected->isEmpty()?null:$selected->map(function($v){return ['id'=>(int)$v->id,'code'=>(string)$v->code];})->values()->all();$detail->save();
    }
    private function output($output,$detail,$line,$lot,$quantity,$cost,$physicalItem): void
    {
        OutputDetail::create(['tenant_id'=>Context::tenantId(),'company_id'=>Context::companyId(),'output_id'=>$output->id,'sale_detail_id'=>$detail->id,
            'item_id'=>$physicalItem?$physicalItem->id:null,'material_id'=>$line['item']->material_id,'stock_item_id'=>$line['item']->id,'stock_lot_id'=>$lot->id,
            'warehouse_id'=>$lot->warehouse_id,'location_id'=>$lot->location_id,'custom'=>0,
            // Existing output convention: percentage stores consumed units for normal lots.
            'percentage'=>$physicalItem?1:$quantity,'price'=>$line['price'],
            'length'=>$physicalItem?$physicalItem->length:null,'width'=>$physicalItem?$physicalItem->width:null,
            'unit_cost'=>$cost,'total_cost'=>bcmul($cost,$quantity,8)]);
    }
    private function cost($value): string
    {
        if(!is_scalar($value)||!preg_match('/^\d{1,12}(?:\.\d{1,10})?$/D',(string)$value))$this->fail('El costo del lote o Item no es válido.');
        return bcadd((string)$value,'0',8);
    }
}
