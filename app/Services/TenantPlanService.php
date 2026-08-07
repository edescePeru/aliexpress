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
}