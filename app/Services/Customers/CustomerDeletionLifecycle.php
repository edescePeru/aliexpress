<?php
namespace App\Services\Customers;
use App\Customer;
use App\ContactName;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDeletionLifecycle
{
    private function tenant($permission = null): int
    {
        $tenant = TenantContext::tenantIdOrNull();
        abort_unless($tenant,403,'Seleccione un tenant operativo.');
        $user = Auth::user();
        abort_unless($user && !$user->isPlatformAdmin() && (bool)$user->enable && (int)$user->tenant_id === $tenant,403);
        if ($permission) { abort_unless($user->can($permission),403); }
        return $tenant;
    }
    private function id($id): int
    {
        if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/D',(string)$id) || filter_var($id,FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages(['id'=>'Identificador inválido.']);
        }
        return (int)$id;
    }
    private function check($ok): void
    {
        if (!$ok) { throw new \RuntimeException('No se pudo completar la operación.'); }
    }
    public function deleteCustomer($id): void
    {
        $tenant=$this->tenant('destroy_customer');$id=$this->id($id);
        DB::transaction(function()use($tenant,$id){
            $customer=Customer::where('tenant_id',$tenant)->lockForUpdate()->findOrFail($id);
            $contacts=ContactName::where('tenant_id',$tenant)->where('customer_id',$id)->orderBy('id')->lockForUpdate()->get();
            $batch=(string)Str::uuid();
            $this->check($customer->forceFill(['customer_deletion_batch'=>$batch])->save());
            foreach($contacts as$contact){
                $this->check($contact->forceFill(['customer_deletion_batch'=>$batch])->save());
                $this->check($contact->delete());
            }
            // La cascada existente ya no encuentra contactos activos de este tenant.
            $this->check($customer->delete());
            if(ContactName::where('tenant_id',$tenant)->where('customer_id',$id)->exists()){
                throw new \RuntimeException('Quedaron contactos activos; se revierte la eliminación.');
            }
        });
    }
    public function restoreCustomer($id): array
    {
        $tenant=$this->tenant('destroy_customer');$id=$this->id($id);
        return DB::transaction(function()use($tenant,$id){
            $customer=Customer::onlyTrashed()->where('tenant_id',$tenant)->lockForUpdate()->findOrFail($id);
            $batch=$customer->customer_deletion_batch;
            if($batch && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',$batch)){
                throw ValidationException::withMessages(['customer'=>'La trazabilidad del borrado requiere revisión.']);
            }
            $contacts=$batch ? ContactName::onlyTrashed()->where('tenant_id',$tenant)->where('customer_id',$id)
                ->where('customer_deletion_batch',$batch)->orderBy('id')->lockForUpdate()->get() : collect();
            $customer->forceFill(['customer_deletion_batch'=>null]);$this->check($customer->restore());
            foreach($contacts as$contact){$contact->forceFill(['customer_deletion_batch'=>null]);$this->check($contact->restore());}
            return ['restored_contacts'=>$contacts->count(),'legacy_without_batch'=>!$batch];
        });
    }
    public function activeParent($id): Customer
    {
        if(DB::transactionLevel()<1){throw new \LogicException('El bloqueo del cliente requiere una transacción.');}
        return Customer::where('tenant_id',$this->tenant())->lockForUpdate()->findOrFail($this->id($id));
    }
    public function lockContact($id, bool $trashed = false, $destination = null): ContactName
    {
        if(DB::transactionLevel()<1){throw new \LogicException('El bloqueo del contacto requiere una transacción.');}
        $tenant=$this->tenant();$id=$this->id($id);
        $query=ContactName::where('tenant_id',$tenant);if($trashed){$query->onlyTrashed();}
        $probe=(clone $query)->findOrFail($id);$parentIds=[(int)$probe->customer_id];
        if($destination!==null){$parentIds[]=$this->id($destination);}
        $parentIds=array_values(array_unique($parentIds));sort($parentIds);
        $parents=Customer::where('tenant_id',$tenant)->whereIn('id',$parentIds)->orderBy('id')->lockForUpdate()->get();
        if($parents->count()!==count($parentIds)){
            throw ValidationException::withMessages(['customer'=>'El cliente asociado debe estar activo y pertenecer al tenant actual.']);
        }
        $contact=$query->lockForUpdate()->findOrFail($id);
        if((int)$contact->customer_id!==(int)$probe->customer_id){
            throw ValidationException::withMessages(['contact'=>'El cliente del contacto cambió. Actualice la pantalla.']);
        }
        return $contact;
    }
    public function deleteContact($id): void
    {
        $this->tenant('destroy_contactName');
        DB::transaction(function()use($id){$contact=$this->lockContact($id);$this->check($contact->forceFill(['customer_deletion_batch'=>null])->save());$this->check($contact->delete());});
    }
    public function restoreContact($id): void
    {
        $this->tenant('destroy_contactName');
        DB::transaction(function()use($id){$contact=$this->lockContact($id,true);$contact->forceFill(['customer_deletion_batch'=>null]);$this->check($contact->restore());});
    }
}
