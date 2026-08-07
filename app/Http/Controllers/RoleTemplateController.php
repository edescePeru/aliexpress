<?php

namespace App\Http\Controllers;

use App\RoleTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class RoleTemplateController extends Controller
{
    public function index()
    {
        return view(
            'roleTemplate.index'
        );
    }

    public function create()
    {
        return view(
            'roleTemplate.create'
        );
    }

    public function edit($id)
    {
        $template = RoleTemplate::findOrFail(
            $id
        );

        return view(
            'roleTemplate.edit',
            compact('template')
        );
    }

    public function data(Request $request)
    {
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

        $query = RoleTemplate::query()
            ->withCount('permissions')
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use (
                $search
            ) {
                $q->where(
                    'code',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        $templates = $query
            ->paginate($perPage);

        return response()->json(
            $templates
        );
    }

    public function permissions()
    {
        $permissions = Permission::query()
            ->select(
                'id',
                'name',
                'description'
            )
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $modulesCatalog = config(
            'permissions_modules',
            []
        );

        $groups = [];

        foreach ($permissions as $permission) {

            $moduleCode =
                $this->getPermissionModule(
                    $permission->name
                );

            /*
             * Si encontramos el módulo en nuestro catálogo,
             * usamos su nombre amigable.
             *
             * Si todavía no fue registrado en
             * permissions_modules.php, no hacemos fallar
             * el sistema. Lo mostramos como "OTROS".
             */
            $moduleName =
                $modulesCatalog[$moduleCode]
                ?? 'OTROS';

            if (!isset($groups[$moduleCode])) {

                $groups[$moduleCode] = [
                    'code' => $moduleCode,
                    'name' => $moduleName,
                    'permissions' => [],
                ];
            }

            $groups[$moduleCode]['permissions'][] = [
                'id' => $permission->id,
                'name' => $permission->name,

                'description' =>
                    $permission->description
                        ?: $permission->name,
            ];
        }

        /*
         * Ordenamos alfabéticamente por nombre del módulo.
         */
        uasort(
            $groups,
            function ($a, $b) {
                return strcmp(
                    $a['name'],
                    $b['name']
                );
            }
        );

        return response()->json([
            'groups' => array_values(
                $groups
            ),
        ]);
    }

    private function getPermissionModule($permissionName)
    {
        $position = strpos(
            $permissionName,
            '_'
        );

        /*
         * Si algún permiso no respeta el formato:
         *
         * accion_modulo
         *
         * evitamos errores y lo mandamos a OTROS.
         */
        if ($position === false) {
            return 'other';
        }

        return substr(
            $permissionName,
            $position + 1
        );
    }

    public function show($id)
    {
        $template = RoleTemplate::with([
            'permissions:id,name,description',
        ])->findOrFail($id);

        return response()->json([
            'template' => $template,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:role_templates,code',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:250',
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

        DB::transaction(function () use (
            $validated
        ) {
            $template = RoleTemplate::create([
                'code' => strtolower(
                    $validated['code']
                ),

                'name' =>
                    $validated['name'],

                'description' =>
                    $validated['description']
                    ?? null,

                'is_owner_assignable' =>
                    (bool) (
                        $validated[
                        'is_owner_assignable'
                        ] ?? false
                    ),

                'is_active' => true,
            ]);

            $template->permissions()
                ->sync(
                    $validated[
                    'permissions'
                    ] ?? []
                );
        });

        return response()->json([
            'message' =>
                'Plantilla registrada correctamente.',
        ]);
    }

    public function update(
        Request $request,
        $id
    ) {
        $template =
            RoleTemplate::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',

                Rule::unique(
                    'role_templates',
                    'code'
                )->ignore($template->id),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:250',
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

        DB::transaction(function () use (
            $template,
            $validated
        ) {
            $template->update([
                'code' => strtolower(
                    $validated['code']
                ),

                'name' =>
                    $validated['name'],

                'description' =>
                    $validated['description']
                    ?? null,

                'is_owner_assignable' =>
                    (bool) (
                        $validated[
                        'is_owner_assignable'
                        ] ?? false
                    ),
            ]);

            $template->permissions()
                ->sync(
                    $validated[
                    'permissions'
                    ] ?? []
                );
        });

        return response()->json([
            'message' =>
                'Plantilla actualizada correctamente.',
        ]);
    }

    public function toggleStatus($id)
    {
        $template =
            RoleTemplate::findOrFail($id);

        $template->is_active =
            !$template->is_active;

        $template->save();

        return response()->json([
            'message' =>
                $template->is_active
                    ? 'Plantilla habilitada correctamente.'
                    : 'Plantilla inhabilitada correctamente.',
        ]);
    }
}
