<?php
namespace App\Services\Workers;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class WorkerUserLink
{
    public function options(?int $worker = null)
    {
        app(WorkerAccess::class)->context();
        return $this->users()->whereNotExists(function ($q) use ($worker) {
            $q->selectRaw('1')->from('workers as w')->whereColumn('w.user_id', 'users.id')
                ->where('w.tenant_id', TenantContext::tenantId())->where('w.company_id', TenantContext::companyId());
            if ($worker !== null) { $q->where('w.id', '<>', $worker); }
        })->orderBy('users.name')->get(['users.id', 'users.name', 'users.email']);
    }

    public function validateLink($value, ?int $worker = null): ?int
    {
        $this->transaction();
        app(WorkerAccess::class)->context();
        if ($worker !== null) { app(WorkerAccess::class)->worker($worker); }
        if ($value === null || $value === '') { return null; }
        if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string) $value)) { $this->invalid('Seleccione un usuario válido.'); }
        // Serializes assignments for this account; unique DB index is the final guard.
        $user = $this->users()->where('users.id', $value)->lockForUpdate()->first(['users.id']);
        if (!$user) { $this->invalid('El usuario debe estar activo y autorizado para esta empresa y tenant.'); }
        $duplicates = DB::table('workers')->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('user_id', $user->id);
        if ($worker !== null) { $duplicates->where('id', '<>', $worker); }
        if ($duplicates->exists()) { $this->invalid('Este usuario ya está asociado a otro trabajador de la empresa, incluso inactivo o eliminado. Revise ese vínculo antes de reasignarlo.'); }
        return (int) $user->id;
    }

    public function saleIdentity(): array
    {
        $this->transaction();
        app(WorkerAccess::class)->context();
        $user = $this->users()->where('users.id', Auth::id())->lockForUpdate()->first(['users.id', 'users.name']);
        if (!$user) { $this->invalid('El usuario registrador ya no está habilitado en la empresa.'); }
        $workers = DB::table('workers')->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
            ->where('user_id', $user->id)->whereNull('deleted_at')->where('enable', true)->orderBy('id')->limit(2)->lockForUpdate()->get(['id']);
        if ($workers->count() > 1) { $this->invalid('Hay varios trabajadores activos vinculados a esta cuenta en la empresa. Requiere revisión.'); }
        return ['created_by' => (int) $user->id, 'created_by_name' => $user->name, 'worker_id' => $workers->isEmpty() ? null : (int) $workers->first()->id];
    }

    private function users()
    {
        return DB::table('users')->where('users.tenant_id', TenantContext::tenantId())->where('users.enable', true)
            ->where(function ($q) { $q->where('users.is_platform_admin', false)->orWhereNull('users.is_platform_admin'); })
            ->whereExists(function ($q) { $q->selectRaw('1')->from('company_user as cu')->whereColumn('cu.user_id', 'users.id')->where('cu.company_id', TenantContext::companyId())->where('cu.is_active', true); });
    }
    private function transaction(): void { if (DB::transactionLevel() < 1) { throw new \LogicException('El vínculo requiere una transacción.'); } }
    private function invalid(string $message): void { throw ValidationException::withMessages(['user_id' => $message]); }
}
