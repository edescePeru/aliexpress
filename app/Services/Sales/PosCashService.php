<?php
namespace App\Services\Sales;

use App\{CashBox,CashBoxSubtype,CashRegister,CashMovement};
use App\Support\TenantContext as Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PosCashService
{
    public function boxes()
    {
        return CashBox::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
            ->where('is_active',true)->where(function($q){$q->whereNull('branch_id')->orWhere('branch_id',Context::branchId());});
    }
    public function selectableBoxes()
    {
        // Screen read: only a unique open session of this user in this exact context.
        // The write path revalidates and locks sessions in prepare().
        $open=CashRegister::query()->select('cash_box_id')
            ->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
            ->where('branch_id',Context::branchId())->where('user_id',Auth::id())
            ->where('status',1)->groupBy('cash_box_id')->havingRaw('COUNT(*) = 1');
        return $this->boxes()->whereIn('cash_boxes.id',$open);
    }

    public function subtypes($boxIds)
    {
        return CashBoxSubtype::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
            ->where('is_active',true)->where(function($q)use($boxIds){$q->whereNull('cash_box_id')->orWhereIn('cash_box_id',$boxIds);});
    }
    private function fail($message): void {throw ValidationException::withMessages(['cash_box_id'=>$message]);}
    public function prepare($request,string $currency,string $total,bool $partial): array
    {
        $change=$this->money($request->input('total_vuelto','0'));
        if($partial){
            if(bccomp($change,'0',2)!==0)$this->fail('Una venta a pagos parciales no entrega vuelto en este registro.');
            return [];
        }
        $lockIds=[];
        foreach(['cash_box_id','vuelto_cash_box_id']as$field){
            $value=$request->input($field);
            if(is_scalar($value)&&preg_match('/^[1-9][0-9]*$/D',(string)$value))$lockIds[]=(int)$value;
        }
        $this->boxes()->whereIn('id',array_unique($lockIds))->orderBy('id')->lockForUpdate()->get();
        $subtypeIds=[];
        foreach(['cash_box_subtype_id','vuelto_cash_box_subtype_id']as$field){
            $value=$request->input($field);
            if(is_scalar($value)&&preg_match('/^[1-9][0-9]*$/D',(string)$value))$subtypeIds[]=(int)$value;
        }
        CashBoxSubtype::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
            ->whereIn('id',array_unique($subtypeIds))->orderBy('id')->lockForUpdate()->get();
        $box=$this->box($request->input('cash_box_id'),$currency);
        $subtype=$this->subtype($box,$request->input('cash_box_subtype_id'));
        $changeBox=null;$changeSubtype=null;
        if(bccomp($change,'0',2)>0){
            $changeBox=$this->box($request->input('vuelto_cash_box_id'),$currency);
            $changeSubtype=$this->subtype($changeBox,$request->input('vuelto_cash_box_subtype_id'));
            if($changeSubtype&&$changeSubtype->is_deferred)$this->fail('El vuelto debe salir de un canal inmediato, no diferido.');
        }
        // Stable order across payment/change avoids lock inversion.
        $ids=[$box->id];if($changeBox)$ids[]=$changeBox->id;sort($ids);
        $registers=[];
        foreach(array_unique($ids)as$id){
            $rows=CashRegister::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
                ->where('branch_id',Context::branchId())->where('user_id',Auth::id())->where('cash_box_id',$id)
                ->where('status',1)->orderBy('id')->limit(2)->lockForUpdate()->get();
            if($rows->count()!==1)$this->fail('Debe existir una sola sesión abierta del usuario en la caja y local seleccionados.');
            $registers[$id]=$rows->first();
        }
        $amount=bcadd($total,$change,2);
        $regularize=$subtype&&$subtype->is_deferred?0:1;
        if($changeBox){
            $balance=(string)$registers[$changeBox->id]->current_balance;
            if($changeBox->id===$box->id&&$regularize)$balance=bcadd($balance,$amount,2);
            if(bccomp($balance,$change,2)<0)$this->fail('La caja seleccionada no tiene saldo suficiente para el vuelto.');
        }
        return compact('box','subtype','changeBox','changeSubtype','registers','amount','change','regularize');
    }
    private function box($id,$currency)
    {
        if(!is_scalar($id)||!preg_match('/^[1-9][0-9]*$/D',(string)$id))$this->fail('Seleccione una caja válida.');
        $box=$this->boxes()->where('id',$id)->first();
        if(!$box)$this->fail('La caja no está activa o no pertenece a esta empresa/local.');
        if((string)$box->currency!==$currency)$this->fail('La moneda de la caja debe coincidir con la venta.');
        return $box;
    }
    private function subtype($box,$id)
    {
        if($box->type!=='bank'||!$box->uses_subtypes){
            if($id!==null&&$id!=='')$this->fail('Esta caja no utiliza subtipos.');
            return null;
        }
        if(!is_scalar($id)||!preg_match('/^[1-9][0-9]*$/D',(string)$id))$this->fail('Seleccione el subtipo de pago.');
        $subtype=$this->subtypes([$box->id])->where('id',$id)->first();
        if(!$subtype)$this->fail('El subtipo no está activo o no corresponde a esta caja y empresa.');
        return $subtype;
    }
    public function money($value): string
    {
        if(!is_scalar($value)||!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',(string)$value))$this->fail('El vuelto debe ser un importe no negativo con hasta dos decimales.');
        return bcadd((string)$value,'0',2);
    }
    public function post($sale,array $data): void
    {
        if(!$data)return;
        $this->movement($sale,$data['box'],$data['registers'][$data['box']->id],$data['subtype'],'sale',$data['amount'],$data['regularize']);
        if($data['changeBox'])$this->movement($sale,$data['changeBox'],$data['registers'][$data['changeBox']->id],$data['changeSubtype'],'expense',$data['change'],1);
    }
    private function movement($sale,$box,$register,$subtype,$type,$amount,$regularize): void
    {
        CashMovement::create(['tenant_id'=>Context::tenantId(),'company_id'=>Context::companyId(),'branch_id'=>Context::branchId(),
            'cash_box_id'=>$box->id,'cash_register_id'=>$register->id,'cash_box_subtype_id'=>$subtype?$subtype->id:null,
            'user_id'=>Auth::id(),'sale_id'=>$sale->id,'type'=>$type,'amount'=>$amount,'regularize'=>$regularize,
            'description'=>$type==='sale'?'Venta POS':'Vuelto de venta POS']);
        if($regularize){
            $register->current_balance=$type==='sale'?bcadd((string)$register->current_balance,$amount,2):bcsub((string)$register->current_balance,$amount,2);
            $field=$type==='sale'?'total_sales':'total_expenses';
            $register->$field=bcadd((string)($register->$field??'0'),$amount,2);$register->save();
        }
    }
}
