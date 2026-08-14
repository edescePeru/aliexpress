<?php

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(
        Builder $builder,
        Model $model
    ) {
        /*
         * Obtenemos el tenant de manera opcional.
         *
         * No usamos TenantContext::tenantId()
         * porque ese método lanza una excepción
         * cuando no existe contexto.
         */
        $tenantId =
            TenantContext::tenantIdOrNull();

        /*
         * Sin tenant activo no aplicamos filtro.
         *
         * Casos esperados:
         *
         * - Platform Admin
         * - comandos Artisan
         * - Tinker
         * - migraciones
         * - procesos técnicos controlados
         */
        if (!$tenantId) {
            return;
        }

        $builder->where(
            $model->qualifyColumn(
                'tenant_id'
            ),
            $tenantId
        );
    }
}