<?php
namespace App\Services\Transfers;

use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{Auth, DB};

class WarehouseTransferAccess
{
    public function context(): void
    {
        $user = Auth::user();
        if (!$user || $user->isPlatformAdmin() || (int) $user->tenant_id !== TenantContext::tenantId()) { $this->deny(); }
        if (!DB::table('companies')->where('id', TenantContext::companyId())->where('tenant_id', TenantContext::tenantId())->where('is_active', true)->exists()
            || !DB::table('company_user')->where('user_id', $user->id)->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            || !DB::table('branches')->where('id', TenantContext::branchId())->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            || !DB::table('branch_user')->where('user_id', $user->id)->where('branch_id', TenantContext::branchId())->where('is_active', true)->exists()) { $this->deny(); }
    }

    public function managesCompany(): bool { return (bool) Auth::user()->can('manageAll_transfer'); }

    public function scopeVisible($query)
    {
        $this->context();
        $query->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
        if (!$this->managesCompany()) {
            $query->where(function ($q) { $q->where('source_branch_id', TenantContext::branchId())->orWhere('destination_branch_id', TenantContext::branchId()); });
        }
        return $query;
    }

    public function warehouse(int $id)
    {
        $this->context();
        $row = DB::table('warehouses')->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('id', $id)->first();
        if (!$row || ($row->branch_id !== null && !DB::table('branches')->where('id', $row->branch_id)
            ->where('company_id', TenantContext::companyId())->where('is_active', true)->exists())) { $this->deny(); }
        return $row;
    }

    public function authorizeCreate(int $source, int $destination): void
    {
        $this->context(); $this->permission('create');
        $this->warehouse($source); $target = $this->warehouse($destination);
        if (!$this->managesCompany() && (int) $target->branch_id !== TenantContext::branchId()) { $this->deny(); }
    }

    public function authorize(string $action, $transfer): void
    {
        $this->context(); $this->permission($action === 'view' ? 'list' : $action);
        if ((int) $transfer->tenant_id !== TenantContext::tenantId() || (int) $transfer->company_id !== TenantContext::companyId()) { $this->deny(); }
        $source = (int) $transfer->source_branch_id === TenantContext::branchId();
        $destination = (int) $transfer->destination_branch_id === TenantContext::branchId();
        if ($action === 'view') {
            if (!$this->managesCompany() && !$source && !$destination) { $this->deny(); }
            return;
        }
        $this->warehouse((int) $transfer->source_warehouse_id); $this->warehouse((int) $transfer->destination_warehouse_id);
        if ($this->managesCompany()) { return; }
        if (in_array($action, ['prepare', 'dispatch'], true) && $source) { return; }
        if ($action === 'receive' && $destination) { return; }
        if ($action === 'cancel' && ($source || ($transfer->status === 'requested' && $destination && (int) $transfer->requested_by === (int) Auth::id()))) { return; }
        $this->deny();
    }

    public function allows(string $action, $transfer): bool
    {
        try { $this->authorize($action, $transfer); return true; }
        catch (AuthorizationException $e) { return false; }
    }
    private function permission(string $action): void
    {
        if (!Auth::user()->can($action.'_transfer')) { $this->deny(); }
    }
    private function deny(): void { throw new AuthorizationException('No tiene acceso a esta operación en la empresa y el local actuales.'); }
}
