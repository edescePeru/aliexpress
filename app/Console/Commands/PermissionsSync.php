<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSync extends Command
{
    protected $signature =
        'permissions:sync {--force}';

    protected $description =
        'Sincroniza los permisos globales de Venti360 desde el catálogo.';

    public function handle()
    {
        $catalog =
            config(
                'permissions_catalog'
            );

        if (
            !is_array($catalog) ||
            empty($catalog)
        ) {
            $this->error(
                'El catálogo de permisos está vacío.'
            );

            return 1;
        }

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $created = 0;
        $updated = 0;

        foreach (
            $catalog as
            $name => $description
        ) {
            $permission =
                Permission::query()
                    ->where(
                        'name',
                        $name
                    )
                    ->where(
                        'guard_name',
                        'web'
                    )
                    ->first();

            if ($permission) {
                $permission->description =
                    $description;

                $permission->save();

                $updated++;
            } else {
                Permission::create([
                    'name' =>
                        $name,

                    'guard_name' =>
                        'web',

                    'description' =>
                        $description,
                ]);

                $created++;
            }
        }

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $this->info(
            'Permisos globales sincronizados correctamente.'
        );

        $this->line(
            'Creados: ' .
            $created
        );

        $this->line(
            'Actualizados: ' .
            $updated
        );

        $this->line('');

        $this->comment(
            'Los roles de tenant no se modificaron.'
        );

        $this->comment(
            'Para actualizar perfiles estándar ejecute: php artisan tenant-roles:sync --all'
        );

        return 0;
    }
}