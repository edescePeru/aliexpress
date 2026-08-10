<?php

namespace App\Http\Controllers;

use App\Role;
use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $selectedTenantId =
            (int) $request->get(
                'tenant_id',
                0
            );

        return view(
            'tenantRole.index',
            compact(
                'tenants',
                'selectedTenantId'
            )
        );
    }

    public function data(Request $request)
    {
        $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],
        ]);

        $tenantId = (int) $request->get(
            'tenant_id'
        );

        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (!in_array(
            $perPage,
            [10, 20, 50]
        )) {
            $perPage = 10;
        }

        $search = trim(
            (string) $request->get('search')
        );

        $query = Role::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->with([
                'template:id,code,name',
            ])
            ->withCount('permissions')
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $query->where(
                function ($q) use (
                    $search
                ) {
                    $q->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'description',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }

        return response()->json(
            $query->paginate(
                $perPage
            )
        );
    }

    public function create($tenantId)
    {
        $tenant = Tenant::query()
            ->where('is_active', true)
            ->findOrFail($tenantId);

        return view(
            'tenantRole.create',
            compact('tenant')
        );
    }

    public function edit($tenantId,$roleId) {
        $tenant = Tenant::findOrFail(
            $tenantId
        );

        $role = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->findOrFail(
                $roleId
            );

        return view(
            'tenantRole.edit',
            compact(
                'tenant',
                'role'
            )
        );
    }

    public function show($tenantId,$roleId) {
        $tenant = Tenant::findOrFail(
            $tenantId
        );

        $role = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->with([
                'template:id,code,name',
                'permissions:id,name,description',
            ])
            ->findOrFail(
                $roleId
            );

        return response()->json([
            'role' => $role,
        ]);
    }

    public function store(Request $request,$tenantId) {
        $tenant = Tenant::query()
            ->where('is_active', true)
            ->findOrFail(
                $tenantId
            );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
            ],

            'description' => [
                'required',
                'string',
                'max:125',
            ],

            'is_owner_assignable' => [
                'nullable',
                'boolean',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        /*
         * La validación de unicidad es nuestra,
         * porque Spatie v4 sigue pensando en
         * name + guard_name global.
         */
        $exists = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'name',
                $validated['name']
            )
            ->where(
                'guard_name',
                'web'
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'message' =>
                    'Ya existe un rol con ese código dentro del tenant.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $tenant,
                $validated
            ) {
                /*
                 * No usamos Role::create()
                 * por la validación interna de Spatie v4.
                 */
                $role = new Role();

                $role->tenant_id =
                    $tenant->id;

                $role->role_template_id =
                    null;

                $role->name =
                    $validated['name'];

                $role->description =
                    $validated['description'];

                $role->guard_name =
                    'web';

                $role->is_owner_assignable =
                    (bool) (
                        $validated[
                        'is_owner_assignable'
                        ] ?? false
                    );

                $role->is_customized =
                    true;

                $role->is_active =
                    true;

                $role->save();

                $role->syncPermissions(
                    $validated[
                    'permissions'
                    ] ?? []
                );
            }
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        return response()->json([
            'message' =>
                'Rol personalizado creado correctamente.',
        ]);
    }

    public function update(Request $request,$tenantId,$roleId) {
        $tenant = Tenant::findOrFail(
            $tenantId
        );

        $role = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->findOrFail(
                $roleId
            );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
            ],

            'description' => [
                'required',
                'string',
                'max:125',
            ],

            'is_owner_assignable' => [
                'nullable',
                'boolean',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        $duplicate = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'name',
                $validated['name']
            )
            ->where(
                'guard_name',
                'web'
            )
            ->where(
                'id',
                '!=',
                $role->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'Ya existe otro rol con ese código dentro del tenant.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $role,
                $validated
            ) {
                $role->name =
                    $validated['name'];

                $role->description =
                    $validated['description'];

                $role->is_owner_assignable =
                    (bool) (
                        $validated[
                        'is_owner_assignable'
                        ] ?? false
                    );

                /*
                 * Cualquier modificación manual desde
                 * este panel convierte el rol en personalizado.
                 */
                $role->is_customized =
                    true;

                $role->save();

                $role->syncPermissions(
                    $validated[
                    'permissions'
                    ] ?? []
                );
            }
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        return response()->json([
            'message' =>
                'Rol personalizado correctamente.',
        ]);
    }

    public function toggleStatus($tenantId,$roleId) {
        $tenant = Tenant::findOrFail(
            $tenantId
        );

        $role = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->findOrFail(
                $roleId
            );

        /*
         * No debemos inhabilitar un rol si todavía
         * tiene usuarios asignados.
         */
        if (
            $role->is_active &&
            $role->users()
                ->where(
                    'users.enable',
                    true
                )
                ->exists()
        ) {
            return response()->json([
                'message' =>
                    'No se puede inhabilitar el rol porque tiene usuarios activos asignados.',
            ], 422);
        }

        $role->is_active =
            !$role->is_active;

        $role->save();

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        return response()->json([
            'message' =>
                $role->is_active
                    ? 'Rol habilitado correctamente.'
                    : 'Rol inhabilitado correctamente.',
        ]);
    }
}