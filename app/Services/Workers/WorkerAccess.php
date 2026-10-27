<?php
namespace App\Services\Workers;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Auth\Access\AuthorizationException;

class WorkerAccess
{
    public function context(): void
    {
        $user = Auth::user();
        if (!$user || $user->isPlatformAdmin() || !(bool) $user->enable || (int) $user->tenant_id !== TenantContext::tenantId()
            || !DB::table('companies')->where('id', TenantContext::companyId())->where('tenant_id', TenantContext::tenantId())->where('is_active', true)->exists()
            || !DB::table('company_user')->where('user_id', $user->id)->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            || !DB::table('branches')->where('id', TenantContext::branchId())->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            || !DB::table('branch_user')->where('user_id', $user->id)->where('branch_id', TenantContext::branchId())->where('is_active', true)->exists()) {
            throw new AuthorizationException('No tiene acceso al personal de esta empresa y local.');
        }
    }
    public function scope($query)
    {
        $this->context();
        return $query->where('workers.tenant_id', TenantContext::tenantId())->where('workers.company_id', TenantContext::companyId());
    }
    public function worker($id)
    {
        $row = $this->scope(DB::table('workers'))->whereNull('workers.deleted_at')->where('workers.id', $id)->first();
        abort_unless($row, 404);
        return $row;
    }
}
