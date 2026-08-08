<?php

namespace App\Services;

use App\Role;
use App\RoleTemplate;
use App\Tenant;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleService
{
    /**
     * Crea para un tenant un rol estándar
     * utilizando una plantilla global.
     */
    public function createFromTemplate( Tenant $tenant, RoleTemplate $template ) {
        if (!$template->is_active) {
            throw new \RuntimeException(
                'La plantilla se encuentra inactiva.'
            );
        }

        return DB::transaction(function () use (
            $tenant,
            $template
        ) {
            /*
             * Primero verificamos si ya existe una copia
             * estándar de esta plantilla.
             */
            $roleFromTemplate = Role::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'role_template_id',
                    $template->id
                )
                ->where(
                    'is_customized',
                    false
                )
                ->first();

            if ($roleFromTemplate) {
                throw new \RuntimeException(
                    'El tenant ya tiene un rol estándar generado desde esta plantilla.'
                );
            }

            /*
             * También protegemos el código del rol.
             *
             * Puede existir, por ejemplo, un rol histórico
             * personalizado llamado "cashier".
             */
            $roleWithSameName = Role::query()
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

            if ($roleWithSameName) {
                throw new \RuntimeException(
                    'Ya existe un rol con el código "' .
                    $template->code .
                    '" dentro del tenant.'
                );
            }

            /*
             * IMPORTANTE:
             *
             * No usamos Role::create() porque Spatie v4
             * comprueba name + guard_name globalmente.
             *
             * Nuestro índice multi-tenant permite el mismo
             * código en diferentes tenants.
             */
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

            $this->syncPermissionsFromTemplate(
                $role,
                $template
            );

            return $role;
        });
    }

    /**
     * Sincroniza un rol estándar con su plantilla.
     *
     * Nunca modifica roles personalizados.
     */
    public function syncRoleFromTemplate( Role $role, RoleTemplate $template ) {
        if ($role->is_customized) {
            return false;
        }

        if (
            (int) $role->role_template_id
            !==
            (int) $template->id
        ) {
            throw new \RuntimeException(
                'El rol no pertenece a la plantilla indicada.'
            );
        }

        DB::transaction(function () use (
            $role,
            $template
        ) {
            /*
             * Los roles estándar pueden acompañar
             * los cambios de la plantilla.
             */
            $role->name =
                $template->code;

            $role->description =
                $template->name;

            $role->is_owner_assignable =
                $template->is_owner_assignable;

            /*
             * No propagamos is_active desde la plantilla.
             *
             * Desactivar una plantilla significa:
             * "no usarla para nuevas generaciones".
             *
             * No deshabilita roles ya existentes.
             */
            $role->save();

            $this->syncPermissionsFromTemplate(
                $role,
                $template
            );
        });

        return true;
    }

    /**
     * Sincroniza todas las plantillas activas
     * contra un tenant.
     */
    public function syncTenant( Tenant $tenant ) {
        $result = [
            'created' => [],
            'updated' => [],
            'customized' => [],
            'conflicts' => [],
        ];

        $templates = RoleTemplate::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('id')
            ->get();

        foreach ($templates as $template) {

            /*
             * Buscamos primero un rol estándar que ya
             * venga de esta plantilla.
             */
            $standardRole = Role::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'role_template_id',
                    $template->id
                )
                ->where(
                    'is_customized',
                    false
                )
                ->first();

            if ($standardRole) {
                $this->syncRoleFromTemplate(
                    $standardRole,
                    $template
                );

                $result['updated'][] = [
                    'role_id' =>
                        $standardRole->id,

                    'template_id' =>
                        $template->id,

                    'name' =>
                        $template->name,
                ];

                continue;
            }

            /*
             * Puede existir un rol personalizado
             * derivado de esa plantilla.
             *
             * No debemos tocarlo.
             */
            $customizedRole = Role::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'role_template_id',
                    $template->id
                )
                ->where(
                    'is_customized',
                    true
                )
                ->first();

            if ($customizedRole) {
                $result['customized'][] = [
                    'role_id' =>
                        $customizedRole->id,

                    'template_id' =>
                        $template->id,

                    'name' =>
                        $customizedRole->description
                            ?: $customizedRole->name,
                ];

                continue;
            }

            /*
             * Protección adicional:
             *
             * Puede existir un rol histórico con el mismo
             * código pero sin role_template_id.
             */
            $sameNameRole = Role::query()
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

            if ($sameNameRole) {
                $result['conflicts'][] = [
                    'role_id' =>
                        $sameNameRole->id,

                    'template_id' =>
                        $template->id,

                    'name' =>
                        $template->name,

                    'reason' =>
                        'Ya existe un rol con el mismo código.',
                ];

                continue;
            }

            /*
             * No existe: lo generamos.
             */
            $role = $this->createFromTemplate(
                $tenant,
                $template
            );

            $result['created'][] = [
                'role_id' =>
                    $role->id,

                'template_id' =>
                    $template->id,

                'name' =>
                    $template->name,
            ];
        }

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        return $result;
    }

    /**
     * Sincroniza solamente los permisos
     * de una plantilla hacia un rol.
     */
    private function syncPermissionsFromTemplate( Role $role, RoleTemplate $template ) {
        $permissionIds = $template
            ->permissions()
            ->pluck(
                'permissions.id'
            )
            ->all();

        $role->syncPermissions(
            $permissionIds
        );
    }
}