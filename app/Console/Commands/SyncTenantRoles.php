<?php

namespace App\Console\Commands;

use App\Services\TenantRoleService;
use App\Tenant;
use Illuminate\Console\Command;

class SyncTenantRoles extends Command
{
    protected $signature =
        'tenant-roles:sync
        {--tenant= : ID de un tenant específico}
        {--all : Sincronizar todos los tenants activos}';

    protected $description =
        'Genera y sincroniza los roles estándar de los tenants desde las plantillas activas.';

    public function handle(
        TenantRoleService $service
    ) {
        $tenantId =
            $this->option('tenant');

        $all =
            (bool) $this->option('all');

        if (!$tenantId && !$all) {
            $this->error(
                'Debe indicar --tenant=ID o --all.'
            );

            return 1;
        }

        if ($tenantId && $all) {
            $this->error(
                'Utilice --tenant o --all, no ambos.'
            );

            return 1;
        }

        if ($tenantId) {
            $tenant = Tenant::find(
                (int) $tenantId
            );

            if (!$tenant) {
                $this->error(
                    'El tenant indicado no existe.'
                );

                return 1;
            }

            return $this->syncOneTenant(
                $service,
                $tenant
            );
        }

        $tenants = Tenant::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn(
                'No existen tenants activos.'
            );

            return 0;
        }

        foreach ($tenants as $tenant) {
            $this->syncOneTenant(
                $service,
                $tenant
            );
        }

        $this->line('');

        $this->info(
            'Sincronización general finalizada.'
        );

        return 0;
    }

    private function syncOneTenant(
        TenantRoleService $service,
        Tenant $tenant
    ) {
        $this->line('');

        $this->info(
            'Tenant #' .
            $tenant->id .
            ': ' .
            $tenant->name
        );

        try {
            $result = $service
                ->syncTenant(
                    $tenant
                );

        } catch (\Throwable $e) {
            $this->error(
                'Error: ' .
                $e->getMessage()
            );

            return 1;
        }

        $this->line(
            'Creados: ' .
            count(
                $result['created']
            )
        );

        $this->line(
            'Actualizados: ' .
            count(
                $result['updated']
            )
        );

        $this->line(
            'Personalizados sin modificar: ' .
            count(
                $result['customized']
            )
        );

        $this->line(
            'Conflictos: ' .
            count(
                $result['conflicts']
            )
        );

        if (
            count(
                $result['created']
            ) > 0
        ) {
            $this->table(
                [
                    'Rol creado',
                    'Role ID',
                ],
                collect(
                    $result['created']
                )
                    ->map(
                        function ($item) {
                            return [
                                $item['name'],
                                $item['role_id'],
                            ];
                        }
                    )
                    ->all()
            );
        }

        if (
            count(
                $result['conflicts']
            ) > 0
        ) {
            $this->warn(
                'Se encontraron conflictos:'
            );

            $this->table(
                [
                    'Plantilla',
                    'Role ID',
                    'Motivo',
                ],
                collect(
                    $result['conflicts']
                )
                    ->map(
                        function ($item) {
                            return [
                                $item['name'],
                                $item['role_id'],
                                $item['reason'],
                            ];
                        }
                    )
                    ->all()
            );
        }

        return 0;
    }
}