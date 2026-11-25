<?php
namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class OwnerWorkspace
{
    public function data(): array
    {
        $user = Auth::user();
        abort_unless($user && $user->enable && $user->isTenantOwner() && !$user->isPlatformAdmin()
            && (int) $user->tenant_id === TenantContext::tenantId(), 403);
        $tenant = $user->tenant;
        abort_unless($tenant && $tenant->is_active, 403);
        $companies = $user->companies()->wherePivot('is_active', true)->where('companies.tenant_id', $tenant->id)
            ->where('companies.is_active', true)->orderBy('companies.business_name')->get();
        $branches = $user->branches()->wherePivot('is_active', true)->where('branches.is_active', true)
            ->whereIn('branches.company_id', $companies->pluck('id'))->orderBy('branches.name')->get();
        foreach ($companies as $company) { $company->setRelation('workspaceBranches', $branches->where('company_id', $company->id)->values()); }
        $plans = app(TenantPlanService::class);
        return ['workspaceCompanies'=>$companies, 'workspaceTenant'=>$tenant, 'workspacePlan'=>$tenant->plan,
            'workspaceStructure'=>$plans->structureSummary($tenant), 'workspaceUsers'=>$plans->activeUsersCount($tenant),
            'workspaceUserLimit'=>$tenant->plan ? $plans->maxActiveUsers($tenant) : null,
            'workspaceCompany'=>TenantContext::hasContext() ? TenantContext::company() : null,
            'workspaceBranch'=>TenantContext::hasContext() ? TenantContext::branch() : null];
    }
}
