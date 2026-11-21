<?php
namespace App\Services\Suppliers;
use App\Supplier;
use App\SupplierAccount;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class SupplierAccountRead
{
    private function authorize($permission): int
    {
        $tenant=TenantContext::tenantIdOrNull();$user=Auth::user();
        abort_unless($tenant && $user && !$user->isPlatformAdmin() && (bool)$user->enable && (int)$user->tenant_id===(int)$tenant,403);
        abort_unless($user->can($permission),403);
        return $tenant;
    }
    private function suppliers(int $tenant, bool $accounts)
    {
        $q=Supplier::where('suppliers.tenant_id',$tenant)->select('suppliers.id','suppliers.code','suppliers.business_name','suppliers.RUC','suppliers.address','suppliers.phone','suppliers.email');
        if($accounts){$q->with(['accounts'=>function($q)use($tenant){$q->where('supplier_accounts.tenant_id',$tenant)->select('id','supplier_id','number_account','currency','bank_id')->orderBy('id');}]);}
        return $q->orderBy('suppliers.id');
    }
    private function row($supplier, bool $accounts): array
    {
        return ['id'=>$supplier->id,'code'=>$supplier->code,'business_name'=>$supplier->business_name,'RUC'=>$supplier->RUC,
            'address'=>$supplier->address,'phone'=>$supplier->phone,'email'=>$supplier->email,
            'accounts'=>$accounts ? $supplier->accounts->map(function($a){return ['id'=>$a->id,'supplier_id'=>$a->supplier_id,'number_account'=>$a->number_account,'currency'=>$a->currency,'bank_id'=>$a->bank_id];})->values()->all() : []];
    }
    public function listing()
    {
        $tenant=$this->authorize('list_supplier');$allowed=Auth::user()->can('list_supplierAccount');
        return $this->suppliers($tenant,$allowed)->get()->map(function($s)use($allowed){return $this->row($s,$allowed);});
    }
    public function exportData(): array
    {
        $tenant=$this->authorize('exportreport_supplier');$allowed=Auth::user()->can('list_supplierAccount');
        $active=$this->suppliers($tenant,$allowed)->get()->map(function($s)use($allowed){
            $row=$this->row($s,$allowed);$row['accounts']=array_map(function($a,$i){return 'Cuenta '.($i+1).': '.$a['number_account'];},$row['accounts'],array_keys($row['accounts']));return $row;
        })->all();
        // La hoja de eliminados conserva su contrato sin cuentas.
        $deleted=$this->suppliers($tenant,false)->onlyTrashed()->get()->map(function($s){$row=$this->row($s,false);unset($row['accounts']);return $row;})->all();
        return ['data'=>$active,'deletedData'=>$deleted];
    }
    public function supplier($id): Supplier
    {
        $tenant=$this->authorize('list_supplierAccount');
        abort_unless((is_int($id)||is_string($id)) && preg_match('/^[1-9][0-9]*$/D',(string)$id) && filter_var($id,FILTER_VALIDATE_INT)!==false,404);
        return Supplier::where('tenant_id',$tenant)->findOrFail((int)$id);
    }
    public function accounts($supplierId)
    {
        $supplier=$this->supplier($supplierId);
        return SupplierAccount::where('tenant_id',TenantContext::tenantId())->where('supplier_id',$supplier->id)->with('bank')->orderBy('id')->get();
    }
}
