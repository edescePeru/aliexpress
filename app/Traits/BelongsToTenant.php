<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Support\TenantContext;
use App\Tenant;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        /*
         * Aplicar aislamiento automático
         * en las consultas.
         */
        static::addGlobalScope(
            new TenantScope()
        );


        /*
         * Asignar automáticamente tenant_id
         * cuando se crea un registro dentro
         * de un contexto Tenant.
         */
        static::creating(
            function ($model) {

                /*
                 * Si tenant_id ya fue asignado
                 * explícitamente, lo respetamos.
                 */
                if (
                !empty(
                $model->tenant_id
                )
                ) {
                    return;
                }


                $tenantId =
                    TenantContext::
                    tenantIdOrNull();


                /*
                 * Platform Admin, Artisan,
                 * Tinker, etc. pueden no tener
                 * Tenant activo.
                 */
                if (!$tenantId) {
                    return;
                }


                $model->tenant_id =
                    $tenantId;
            }
        );
    }


    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }
}