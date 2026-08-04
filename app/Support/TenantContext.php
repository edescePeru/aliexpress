<?php

namespace App\Support;

use App\Branch;
use App\Company;
use App\Tenant;
use RuntimeException;

class TenantContext
{
    public static function tenantId()
    {
        return self::requiredSessionValue(
            'multitenancy.tenant_id',
            'No existe un tenant activo.'
        );
    }

    public static function companyId()
    {
        return self::requiredSessionValue(
            'multitenancy.company_id',
            'No existe una empresa activa.'
        );
    }

    public static function branchId()
    {
        return self::requiredSessionValue(
            'multitenancy.branch_id',
            'No existe un local activo.'
        );
    }

    public static function tenant()
    {
        return Tenant::findOrFail(
            self::tenantId()
        );
    }

    public static function company()
    {
        return Company::findOrFail(
            self::companyId()
        );
    }

    public static function branch()
    {
        return Branch::findOrFail(
            self::branchId()
        );
    }

    public static function hasContext()
    {
        return session()->has([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }

    public static function clear()
    {
        session()->forget([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }

    private static function requiredSessionValue(
        $key,
        $message
    ) {
        $value = session($key);

        if (!$value) {
            throw new RuntimeException(
                $message
            );
        }

        return (int) $value;
    }
}