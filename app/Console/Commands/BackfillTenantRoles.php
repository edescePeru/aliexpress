<?php

namespace App\Console\Commands;

use App\Role;
use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class BackfillTenantRoles extends Command
{
    protected $signature =
        'multitenancy:backfill-roles
        {tenant_id : Tenant al que pertenecen los roles actuales}';

    protected $description =
        'Asigna los roles globales existentes al tenant indicado.';

    public function handle()
    {
        $tenantId =
            (int) $this->argument(
                'tenant_id'
            );

        $tenant =
            Tenant::find(
                $tenantId
            );

        if (!$tenant) {
            $this->error(
                'El tenant no existe.'
            );

            return 1;
        }

        $roles = Role::query()
            ->whereNull('tenant_id')
            ->get();

        if ($roles->isEmpty()) {

            $this->info(
                'No existen roles pendientes de migrar.'
            );

            return 0;
        }

        $this->table(
            [
                'ID',
                'Código',
                'Descripción',
            ],
            $roles
                ->map(
                    function ($role) {
                        return [
                            $role->id,
                            $role->name,
                            $role->description,
                        ];
                    }
                )
                ->all()
        );

        if (!$this->confirm(
            '¿Desea asignar estos roles al tenant ' .
            $tenant->name .
            '?'
        )) {
            return 0;
        }

        DB::transaction(
            function () use (
                $roles,
                $tenant
            ) {

                foreach ($roles as $role) {

                    $role->tenant_id =
                        $tenant->id;

                    $role->is_active =
                        true;

                    $role->is_customized =
                        true;

                    /*
                     * Inicialmente asumimos que son
                     * personalizados porque son roles
                     * históricos del negocio.
                     */
                    $role->save();
                }

            }
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $this->info(
            'Roles migrados correctamente.'
        );

        return 0;
    }
}
