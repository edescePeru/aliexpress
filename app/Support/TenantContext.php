<?php

namespace App\Support;

use App\Branch;
use App\Company;
use App\Tenant;
use RuntimeException;

class TenantContext
{
    /*
     * ============================================================
     * CONTEXTO OBLIGATORIO
     * ============================================================
     *
     * Estos métodos mantienen el comportamiento existente.
     *
     * Si no existe contexto, lanzan RuntimeException.
     */

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


    /*
     * ============================================================
     * CONTEXTO OPCIONAL
     * ============================================================
     *
     * Estos métodos son necesarios para Global Scopes,
     * comandos Artisan, Tinker y Platform Admin.
     *
     * Si no existe contexto simplemente devuelven null.
     */

    public static function tenantIdOrNull()
    {
        return self::optionalSessionValue(
            'multitenancy.tenant_id'
        );
    }

    public static function companyIdOrNull()
    {
        return self::optionalSessionValue(
            'multitenancy.company_id'
        );
    }

    public static function branchIdOrNull()
    {
        return self::optionalSessionValue(
            'multitenancy.branch_id'
        );
    }


    /*
     * ============================================================
     * MODELOS DEL CONTEXTO
     * ============================================================
     */

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


    /*
     * Versiones opcionales.
     */

    public static function tenantOrNull()
    {
        $tenantId =
            self::tenantIdOrNull();

        if (!$tenantId) {
            return null;
        }

        return Tenant::find(
            $tenantId
        );
    }

    public static function companyOrNull()
    {
        $companyId =
            self::companyIdOrNull();

        if (!$companyId) {
            return null;
        }

        return Company::find(
            $companyId
        );
    }

    public static function branchOrNull()
    {
        $branchId =
            self::branchIdOrNull();

        if (!$branchId) {
            return null;
        }

        return Branch::find(
            $branchId
        );
    }


    /*
     * ============================================================
     * VALIDACIÓN DE CONTEXTO
     * ============================================================
     */

    public static function hasContext()
    {
        return session()->has([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }

    public static function hasTenant()
    {
        return session()->has(
            'multitenancy.tenant_id'
        );
    }

    public static function hasCompany()
    {
        return session()->has(
            'multitenancy.company_id'
        );
    }

    public static function hasBranch()
    {
        return session()->has(
            'multitenancy.branch_id'
        );
    }


    /*
     * ============================================================
     * LIMPIEZA
     * ============================================================
     */

    public static function clear()
    {
        session()->forget([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }


    /*
     * ============================================================
     * HELPERS INTERNOS
     * ============================================================
     */

    private static function requiredSessionValue(
        $key,
        $message
    ) {
        $value =
            session($key);

        if (!$value) {
            throw new RuntimeException(
                $message
            );
        }

        return (int) $value;
    }


    private static function optionalSessionValue(
        $key
    ) {
        $value =
            session($key);

        if (!$value) {
            return null;
        }

        return (int) $value;
    }
}