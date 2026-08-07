<?php

namespace App\Services;

use App\Role;
use App\RoleTemplate;
use App\Tenant;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleService
{
    public function createFromTemplate(
        Tenant $tenant,
        RoleTemplate $template
    ) {
        if (!$template->is_active) {
            throw new \RuntimeException(
                'La plantilla se encuentra inactiva.'
            );
        }

        return DB::transaction(
            function () use (
                $tenant,
                $template
            ) {

                /*
                 * Por ahora consultamos manualmente porque
                 * todavía no hemos cambiado el índice único.
                 */
                $existing = Role::query()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->where(
                        'name',
                        $template->code
                    )
                    ->where(
                        'guard_name',
                        'web'
                    )
                    ->first();

                if ($existing) {
                    throw new \RuntimeException(
                        'El tenant ya tiene un rol con este código.'
                    );
                }

                $role = new Role();

                $role->tenant_id =
                    $tenant->id;

                $role->role_template_id =
                    $template->id;

                $role->name =
                    $template->code;

                $role->description =
                    $template->name;

                $role->guard_name =
                    'web';

                $role->is_owner_assignable =
                    $template->is_owner_assignable;

                $role->is_customized =
                    false;

                $role->is_active =
                    true;

                $role->save();

                /*
                 * Copiar permisos de plantilla al rol.
                 */
                $permissionIds =
                    $template
                        ->permissions()
                        ->pluck(
                            'permissions.id'
                        )
                        ->all();

                $role->syncPermissions(
                    $permissionIds
                );

                app(
                    PermissionRegistrar::class
                )->forgetCachedPermissions();

                return $role;
            }
        );
    }
}