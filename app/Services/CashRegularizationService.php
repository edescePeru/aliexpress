<?php
namespace App\Services;

use App\{CashMovement,CashRegister,CashBox,CashBoxSubtype,CashSettlement};
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class CashRegularizationService
{
    public function fail($message): void
    {
        throw ValidationException::withMessages(['regularization'=>$message]);
    }

    public function authorize($request): void
    {
        $user=Auth::user();
        $tenant=TenantContext::tenantIdOrNull();$company=TenantContext::companyIdOrNull();$branch=TenantContext::branchIdOrNull();
        abort_unless($user&&!$user->isPlatformAdmin()&&$user->enable
            &&$tenant&&$company&&$branch&&(int)$user->tenant_id===(int)$tenant&&$user->can('regularize_caja')
            &&DB::table('companies')->where('id',$company)->where('tenant_id',$tenant)->where('is_active',true)->exists()
            &&DB::table('branches')->where('id',$branch)->where('company_id',$company)->where('is_active',true)->exists()
            &&DB::table('company_user')->where('company_id',$company)->where('user_id',$user->id)->where('is_active',true)->exists()
            &&DB::table('branch_user')->where('branch_id',$branch)->where('user_id',$user->id)->where('is_active',true)->exists(),
            403,'No tiene autorización para regularizar en la empresa y local actuales.');
        foreach(['tenant'=>$tenant,'company'=>$company,'branch'=>$branch]as$key=>$expected){
            $actual=$request->input('regularization_context_'.$key);
            if(!is_scalar($actual)||(string)$actual!==(string)$expected)
                $this->fail('Cambió la empresa o el local desde que abrió la pantalla. Recargue antes de regularizar.');
        }
    }

    private function context($query)
    {
        return $query->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
            ->where('branch_id',TenantContext::branchId());
    }

    private function money($value,bool $positive=false): string
    {
        if(!is_scalar($value)||is_bool($value)||!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',(string)$value))
            $this->fail('El monto debe ser un número no negativo con hasta dos decimales.');
        $value=bcadd((string)$value,'0',2);
        if($positive&&bccomp($value,'0',2)<=0)$this->fail('El monto neto debe ser mayor que cero.');
        return $value;
    }

    private function movement($id)
    {
        $movement=$this->context(CashMovement::query())->where('id',$id)->lockForUpdate()->first();
        if(!$movement)$this->fail('El movimiento no pertenece a la empresa y local actuales.');
        if(!$movement->user_id||!$movement->cash_box_id||!$movement->cash_register_id||!$movement->cash_box_subtype_id)
            $this->fail('El movimiento no tiene usuario, caja, sesión y subtipo identificables. Requiere conciliación.');
        if($movement->regularize||$movement->cash_movement_regularize_id||$movement->regularization_source_id||$movement->balance_effect===false)
            $this->fail('El movimiento ya fue regularizado o pertenece a una regularización.');
        if(!in_array($movement->type,['sale','income'],true))$this->fail('Solo se regularizan ventas o ingresos diferidos.');
        if($movement->cash_movement_origin_id)$this->fail('Este movimiento tiene vínculos del flujo anterior y requiere conciliación.');
        $children=CashMovement::withoutGlobalScopes()->where('cash_movement_origin_id',$movement->id)->exists();
        if($children)$this->fail('El movimiento tiene operaciones vinculadas que requieren revisión.');
        return $movement;
    }

    private function box(CashMovement $movement)
    {
        $box=CashBox::query()->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
            ->where('id',$movement->cash_box_id)->where('is_active',true)->where('type','bank')->where('uses_subtypes',true)
            ->where(function($q){$q->whereNull('branch_id')->orWhere('branch_id',TenantContext::branchId());})
            ->lockForUpdate()->first();
        if(!$box)$this->fail('La caja bancaria no está activa o no corresponde al contexto del movimiento.');
        return $box;
    }

    private function subtype(CashMovement $movement,CashBox $box)
    {
        $subtype=CashBoxSubtype::query()->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
            ->where('id',$movement->cash_box_subtype_id)->where('is_active',true)->where('is_deferred',true)
            ->where(function($q)use($box){$q->whereNull('cash_box_id')->orWhere('cash_box_id',$box->id);})
            ->lockForUpdate()->first();
        if(!$subtype)$this->fail('El subtipo diferido no está activo o no es válido para esta caja y empresa.');
        return $subtype;
    }

    public function regularize($request): array
    {
        $this->authorize($request);
        $data=$request->validate([
            'cash_movement_id'=>['required','regex:/^[1-9][0-9]*$/D'],
            'amount_regularize'=>['required'],
            'observation'=>['nullable','string','max:1000'],
        ]);
        $net=$this->money($data['amount_regularize'],true);
        return DB::transaction(function()use($data,$net){
            $movement=$this->movement($data['cash_movement_id']);
            $box=$this->box($movement);
            $subtype=$this->subtype($movement,$box);
            $originalRegister=$this->context(CashRegister::query())->where('id',$movement->cash_register_id)
                ->where('cash_box_id',$box->id)->where('user_id',$movement->user_id)->lockForUpdate()->first();
            if(!$originalRegister)$this->fail('La sesión original no corresponde a la caja, usuario y contexto del movimiento.');
            $afterClose=!$originalRegister->status;
            if($movement->cash_settlement_id){
                $settlement=$this->context(CashSettlement::query())->where('id',$movement->cash_settlement_id)
                    ->where('cash_register_id',$originalRegister->id)->where('cash_box_id',$box->id)
                    ->where('user_id',$movement->user_id)->first();
                if(!$settlement||!$afterClose)$this->fail('El movimiento tiene un arqueo incompatible con su sesión original.');
            }
            $receivingUser=$afterClose?Auth::id():$originalRegister->user_id;
            $duplicates=$this->context(CashRegister::query())->where('cash_box_id',$box->id)
                ->where('user_id',$receivingUser)->where('status',true)->orderBy('id')->limit(2)->lockForUpdate()->get();
            if($duplicates->count()!==1)
                $this->fail($afterClose?'Debe tener una única sesión abierta en la misma caja y local para recibir este abono.':
                    'Debe existir una única sesión abierta del usuario original en esta caja y local.');
            $register=$duplicates->first();
            if(!$afterClose&&(int)$register->id!==(int)$originalRegister->id)
                $this->fail('La sesión abierta no coincide con la sesión original.');
            if($movement->sale_id){
                $sale=DB::table('sales')->where('id',$movement->sale_id)
                    ->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
                    ->where('branch_id',TenantContext::branchId())->first();
                if(!$sale||(string)$sale->currency!==(string)$box->currency)
                    $this->fail('La venta vinculada no coincide con el contexto y la moneda de la caja.');
            }
            $gross=$this->money($movement->amount,true);
            if(bccomp($net,$gross,2)>0)$this->fail('El monto neto no puede superar el importe original.');
            if(!$subtype->requires_commission&&bccomp($net,$gross,2)!==0)
                $this->fail('Este canal no aplica comisión: el neto debe ser igual al importe original.');
            $commission=bcsub($gross,$net,2);
            $receipt=null;
            $extra=trim((string)($data['observation']??''));
            $note='Regularizado por usuario '.Auth::id().' el '.now()->format('Y-m-d H:i:s')
                .' en sesión '.$register->id.'.';
            if($afterClose){
                $receipt=CashMovement::create([
                    'tenant_id'=>TenantContext::tenantId(),'company_id'=>TenantContext::companyId(),'branch_id'=>TenantContext::branchId(),
                    'cash_box_id'=>$box->id,'cash_register_id'=>$register->id,'cash_box_subtype_id'=>$subtype->id,
                    'cash_settlement_id'=>null,'user_id'=>Auth::id(),'type'=>$movement->type,
                    'amount'=>$gross,'amount_regularize'=>$net,'commission'=>$commission,'regularize'=>true,'balance_effect'=>true,
                    'description'=>'Regularización de movimiento #'.$movement->id.' de sesión cerrada #'.$originalRegister->id,
                    'observation'=>trim(implode(' | ',array_filter([$extra,$note]))),
                    'cash_movement_origin_id'=>$movement->id,'regularization_source_id'=>$movement->id,
                    'cash_movement_regularize_id'=>null,'sale_id'=>null,'entry_id'=>null,'arqueo'=>false,
                ]);
                // Keep the original session/settlement references. Only the new receipt posts cash.
                $movement->balance_effect=false;
                $movement->cash_movement_regularize_id=$receipt->id;
            }
            $movement->amount_regularize=$net;
            $movement->commission=$commission;
            $movement->regularize=true;
            $movement->observation=trim(implode(' | ',array_filter([trim((string)$movement->observation),$extra,$note])));
            $movement->save();
            $balance=(string)($register->current_balance??'0');
            if(!preg_match('/^-?\d{1,18}(?:\.\d{1,2})?$/D',$balance))
                $this->fail('El saldo de la sesión no tiene un valor monetario válido.');
            $register->current_balance=bcadd($balance,$net,2);
            $counter=$movement->type==='sale'?'total_sales':'total_incomes';
            $register->$counter=bcadd((string)($register->$counter??'0'),$net,2);
            $register->save();
            \App\Audit::create(['user_id'=>Auth::id(),'action'=>'Regularizar movimiento '.$movement->id
                .' | tenant '.TenantContext::tenantId().' | empresa '.TenantContext::companyId()
                .' | local '.TenantContext::branchId().' | sesión '.$register->id,'time'=>0]);
            return ['message'=>$afterClose?'Regularización abonada en la sesión abierta, conservando el cierre original.':'Regularización aplicada correctamente.',
                'balance_total'=>$register->current_balance,
                'cash_register_id'=>$register->id,'movement_id'=>$movement->id,
                'original_cash_register_id'=>$originalRegister->id,'receipt_movement_id'=>$receipt?$receipt->id:null,
                'regularized_after_close'=>$afterClose,'amount_regularize'=>$net,'commission'=>$commission,'currency'=>$box->currency];
        },3);
    }
}
