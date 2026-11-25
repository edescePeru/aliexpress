<?php

namespace App\Services;

use App\Tenant;
use App\User;
use RuntimeException;

class TenantPlanService
{
    public function activeUsersCount(
        Tenant $tenant
    ) {
        return User::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_platform_admin',
                false
            )
            ->where(
                'enable',
                true
            )
            ->count();
    }

    public function maxActiveUsers(
        Tenant $tenant
    ) {
        if (!$tenant->plan) {
            throw new RuntimeException(
                'El tenant no tiene un plan asignado.'
            );
        }

        return (int)
        $tenant
            ->plan
            ->max_active_users;
    }

    public function availableUsers(
        Tenant $tenant
    ) {
        return max(
            0,
            $this->maxActiveUsers(
                $tenant
            ) -
            $this->activeUsersCount(
                $tenant
            )
        );
    }

    public function canActivateUser(
        Tenant $tenant
    ) {
        return $this->activeUsersCount(
                $tenant
            ) <
            $this->maxActiveUsers(
                $tenant
            );
    }

    public function ensureCanActivateUser(
        Tenant $tenant
    ) {
        if (
        !$this->canActivateUser(
            $tenant
        )
        ) {
            throw new RuntimeException(
                'El plan ' .
                $tenant->plan->name .
                ' permite un máximo de ' .
                $tenant->plan
                    ->max_active_users .
                ' usuarios activos.'
            );
        }
    }

    // Call before any quota-consuming write, inside the caller's transaction.
    // The shared plan lock prevents an edit from changing limits during the write.
    public function lockTenant(int $tenantId): Tenant
    {
        if (\Illuminate\Support\Facades\DB::transactionLevel() < 1) {
            throw new RuntimeException('El control de cupos requiere una transacción.');
        }
        $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
        $plan = \App\Plan::query()->whereKey($tenant->plan_id)->sharedLock()->first();
        if (!$plan) { throw new RuntimeException('El tenant no tiene un plan asignado.'); }
        $tenant->setRelation('plan', $plan);
        return $tenant;
    }

    public function activeCompaniesCount(Tenant $tenant): int
    {
        return \App\Company::query()->where('tenant_id', $tenant->id)->where('is_active', true)->count();
    }

    public function activeBranchesCount(Tenant $tenant): int
    {
        // Company status intentionally does not filter this count.
        return \Illuminate\Support\Facades\DB::table('branches')->join('companies', 'companies.id', '=', 'branches.company_id')
            ->where('companies.tenant_id', $tenant->id)->where('branches.is_active', true)->count();
    }

    private function structureLimit(Tenant $tenant, string $field): ?int
    {
        $value = $tenant->plan ? $tenant->plan->getAttribute($field) : null;
        return $value === null ? null : (int) $value;
    }

    public function maxActiveCompanies(Tenant $tenant): ?int { return $this->structureLimit($tenant, 'max_active_companies'); }
    public function maxActiveBranches(Tenant $tenant): ?int { return $this->structureLimit($tenant, 'max_active_branches'); }

    public function ensureStructureCapacity(Tenant $tenant, int $companies = 0, int $branches = 0): void
    {
        if ($companies < 0 || $branches < 0) { throw new RuntimeException('Consumo de cupo inválido.'); }
        if (($companies || $branches) && (!$tenant->plan || !$tenant->plan->is_active)) {
            throw new \App\Exceptions\TenantCapacityException('El plan asignado no está disponible para nuevas altas o reactivaciones.');
        }
        foreach ([['empresas', $companies, $this->maxActiveCompanies($tenant), $this->activeCompaniesCount($tenant)],
                  ['locales', $branches, $this->maxActiveBranches($tenant), $this->activeBranchesCount($tenant)]] as $row) {
            list($label, $additional, $limit, $used) = $row;
            if (!$additional) { continue; }
            if ($limit === null) { throw new \App\Exceptions\TenantCapacityException('El máximo de '.$label.' del plan está pendiente de configurar. Contacte al administrador de Venti360.'); }
            if ($used + $additional > $limit) { throw new \App\Exceptions\TenantCapacityException('No hay cupo de '.$label.': '.$used.' activos de '.$limit.' permitidos para todo el grupo empresarial.'); }
        }
    }

    public function structureSummary(Tenant $tenant): array
    {
        $result = [];
        foreach (['companies' => [$this->activeCompaniesCount($tenant), $this->maxActiveCompanies($tenant)],
                  'branches' => [$this->activeBranchesCount($tenant), $this->maxActiveBranches($tenant)]] as $key => $values) {
            list($used, $limit) = $values;
            $result[$key] = ['used' => $used, 'limit' => $limit, 'available' => $limit === null ? 0 : max(0, $limit - $used),
                'configured' => $limit !== null, 'over_limit' => $limit !== null && $used > $limit];
        }
        return $result;
    }
}
