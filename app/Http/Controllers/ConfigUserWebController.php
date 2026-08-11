<?php

namespace App\Http\Controllers;

use App\Services\TenantPlanService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Branch;
use App\Company;
use App\Role;
use Illuminate\Support\Facades\DB;

class ConfigUserWebController extends Controller
{
    private function tenantUsersQuery()
    {
        $authUser = Auth::user();

        if (
            !$authUser ||
            !$authUser->tenant_id ||
            $authUser->is_platform_admin
        ) {
            abort(403, 'No existe un contexto de tenant válido.');
        }

        return User::query()
            ->where(
                'tenant_id',
                $authUser->tenant_id
            )
            ->where(
                'is_platform_admin',
                false
            );
    }

    private function findTenantUserOrFail($id)
    {
        return $this->tenantUsersQuery()
            ->where('id', $id)
            ->firstOrFail();
    }

    private function ensureTenantOwner()
    {
        $user = Auth::user();

        if (
            !$user ||
            !$user->is_tenant_owner ||
            $user->is_platform_admin
        ) {
            abort(
                403,
                'Solo el propietario del tenant puede administrar usuarios.'
            );
        }
    }

    public function listar()
    {
        $this->ensureTenantOwner();

        return view('configUserWeb.index');
    }

    public function getUsers(Request $request)
    {
        $this->ensureTenantOwner();

        $search = trim(
            (string) $request->get('search')
        );

        $status = $request->get(
            'status',
            'active'
        );

        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (!in_array(
            $perPage,
            [10, 25, 50]
        )) {
            $perPage = 10;
        }

        $query = $this->tenantUsersQuery()
            ->with('roles')
            ->select(
                'id',
                'tenant_id',
                'name',
                'email',
                'image',
                'enable',
                'is_tenant_owner',
                'updated_at'
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'LIKE',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'email',
                        'LIKE',
                        '%' . $search . '%'
                    );
            });
        }

        if ($status === 'active') {
            $query->where('enable', true);
        } elseif ($status === 'inactive') {
            $query->where('enable', false);
        }

        $users = $query
            ->orderByDesc('is_tenant_owner')
            ->orderBy('name')
            ->paginate($perPage);

        $users->getCollection()->transform(
            function ($user) {

                $role = $user->roles->first();

                return [
                    'id' =>
                        $user->id,

                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'image' =>
                        $this->getUserImage(
                            $user
                        ),

                    'enable' =>
                        (bool) $user->enable,

                    'is_tenant_owner' =>
                        (bool) $user->is_tenant_owner,

                    'updated_at' =>
                        optional(
                            $user->updated_at
                        )->format(
                            'd/m/Y H:i'
                        ),

                    'role' =>
                        $role
                            ? (
                        $role->description
                            ?: $role->name
                        )
                            : null,

                    'role_id' =>
                        $role
                            ? $role->id
                            : null,

                    'is_current_user' =>
                        $user->id === Auth::id(),
                ];
            }
        );

        return response()->json(
            $users
        );
    }

    private function getUserImage($user)
    {
        if ($user->image) {
            /*
            Ajusta esta ruta según dónde guardes tus imágenes.
            Ejemplos:
            return asset('storage/' . $user->image);
            */

            return asset('images/users/' . $user->image);
        }

        return asset('images/default-user.png');
    }

    public function edit($id)
    {
        $this->ensureTenantOwner();

        $authUser = Auth::user();

        $user = $this
            ->findTenantUserOrFail($id);

        $user->load([
            'roles',
            'companies',
            'branches',
        ]);

        $tenant = $authUser->tenant;

        /*
         * Roles que el Owner puede asignar.
         *
         * Para el Tenant Owner no utilizaremos
         * esta lista para modificar su perfil.
         */
        $roles = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_owner_assignable',
                true
            )
            ->select(
                'id',
                'name',
                'description'
            )
            ->orderBy('description')
            ->get();

        $companies = Company::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->with([
                'branches' => function ($query) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->select(
                            'id',
                            'company_id',
                            'name',
                            'code',
                            'is_main'
                        )
                        ->orderByDesc(
                            'is_main'
                        )
                        ->orderBy(
                            'name'
                        );
                },
            ])
            ->select(
                'id',
                'business_name',
                'trade_name'
            )
            ->orderBy(
                'business_name'
            )
            ->get();

        $selectedCompanyIds = $user
            ->companies
            ->where(
                'pivot.is_active',
                true
            )
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $selectedBranchIds = $user
            ->branches
            ->where(
                'pivot.is_active',
                true
            )
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $defaultCompany = $user
            ->companies
            ->first(function ($company) {
                return
                    (bool) $company->pivot->is_active &&
                    (bool) $company->pivot->is_default;
            });

        $defaultBranch = $user
            ->branches
            ->first(function ($branch) {
                return
                    (bool) $branch->pivot->is_active &&
                    (bool) $branch->pivot->is_default;
            });

        $currentRole = $user
            ->roles
            ->first();

        return view(
            'configUserWeb.edit',
            [
                'tenant' =>
                    $tenant,

                'userEdit' =>
                    $user,

                'roles' =>
                    $roles,

                'companies' =>
                    $companies,

                'currentRole' =>
                    $currentRole,

                'selectedCompanyIds' =>
                    $selectedCompanyIds,

                'selectedBranchIds' =>
                    $selectedBranchIds,

                'defaultCompanyId' =>
                    $defaultCompany
                        ? $defaultCompany->id
                        : null,

                'defaultBranchId' =>
                    $defaultBranch
                        ? $defaultBranch->id
                        : null,
            ]
        );
    }

    public function update(Request $request,$id) {
        $this->ensureTenantOwner();

        $authUser = Auth::user();

        $tenant = $authUser->tenant;

        $user = $this
            ->findTenantUserOrFail($id);

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $user->id
                ),
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];

        /*
         * Solo usuarios operativos pueden
         * modificar su alcance desde este módulo.
         */
        if (!$user->is_tenant_owner) {

            $rules = array_merge(
                $rules,
                [
                    'role_id' => [
                        'required',
                        'integer',
                    ],

                    'companies' => [
                        'required',
                        'array',
                        'min:1',
                    ],

                    'companies.*' => [
                        'integer',
                    ],

                    'branches' => [
                        'required',
                        'array',
                        'min:1',
                    ],

                    'branches.*' => [
                        'integer',
                    ],

                    'default_company_id' => [
                        'required',
                        'integer',
                    ],

                    'default_branch_id' => [
                        'required',
                        'integer',
                    ],
                ]
            );
        }

        $validated = $request->validate(
            $rules,
            [
                'name.required' =>
                    'El nombre es obligatorio.',

                'email.required' =>
                    'El correo electrónico es obligatorio.',

                'email.email' =>
                    'Ingrese un correo electrónico válido.',

                'email.unique' =>
                    'Este correo electrónico ya está registrado.',

                'role_id.required' =>
                    'Seleccione un perfil.',

                'companies.required' =>
                    'Seleccione al menos una empresa.',

                'branches.required' =>
                    'Seleccione al menos un local.',

                'default_company_id.required' =>
                    'Seleccione una empresa predeterminada.',

                'default_branch_id.required' =>
                    'Seleccione un local predeterminado.',
            ]
        );

        $role = null;
        $companyIds = [];
        $branchIds = [];
        $defaultCompanyId = null;
        $defaultBranchId = null;

        if (!$user->is_tenant_owner) {

            /*
             * ROLE
             */
            $role = Role::query()
                ->where(
                    'id',
                    $validated['role_id']
                )
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'is_owner_assignable',
                    true
                )
                ->first();

            if (!$role) {
                return response()->json([
                    'message' =>
                        'El perfil seleccionado no está disponible para este tenant.'
                ], 422);
            }

            /*
             * COMPANIES
             */
            $companyIds = array_values(
                array_unique(
                    array_map(
                        'intval',
                        $validated['companies']
                    )
                )
            );

            $validCompanies = Company::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereIn(
                    'id',
                    $companyIds
                )
                ->get();

            if (
                $validCompanies->count()
                !==
                count($companyIds)
            ) {
                return response()->json([
                    'message' =>
                        'Una o más empresas seleccionadas no pertenecen al tenant.'
                ], 422);
            }

            $defaultCompanyId =
                (int) $validated[
                'default_company_id'
                ];

            if (
            !in_array(
                $defaultCompanyId,
                $companyIds,
                true
            )
            ) {
                return response()->json([
                    'message' =>
                        'La empresa predeterminada debe estar entre las empresas autorizadas.'
                ], 422);
            }

            /*
             * BRANCHES
             */
            $branchIds = array_values(
                array_unique(
                    array_map(
                        'intval',
                        $validated['branches']
                    )
                )
            );

            $validBranches = Branch::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereIn(
                    'company_id',
                    $companyIds
                )
                ->whereIn(
                    'id',
                    $branchIds
                )
                ->with('company')
                ->get()
                ->filter(
                    function ($branch) use (
                        $tenant
                    ) {
                        return
                            $branch->company &&
                            (int)
                            $branch
                                ->company
                                ->tenant_id
                            ===
                            (int)
                            $tenant->id;
                    }
                )
                ->values();

            if (
                $validBranches->count()
                !==
                count($branchIds)
            ) {
                return response()->json([
                    'message' =>
                        'Uno o más locales seleccionados no son válidos para el tenant.'
                ], 422);
            }

            $defaultBranchId =
                (int) $validated[
                'default_branch_id'
                ];

            if (
            !in_array(
                $defaultBranchId,
                $branchIds,
                true
            )
            ) {
                return response()->json([
                    'message' =>
                        'El local predeterminado debe estar entre los locales autorizados.'
                ], 422);
            }

            $defaultBranch =
                $validBranches
                    ->firstWhere(
                        'id',
                        $defaultBranchId
                    );

            if (
                !$defaultBranch ||
                (int)
                $defaultBranch
                    ->company_id
                !==
                $defaultCompanyId
            ) {
                return response()->json([
                    'message' =>
                        'El local predeterminado debe pertenecer a la empresa predeterminada.'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            $user->name =
                $validated['name'];

            $user->email =
                $validated['email'];

            /*
             * Imagen.
             */
            if ($request->file('image')) {

                $path =
                    public_path(
                        'images/users/'
                    );

                if (!file_exists($path)) {
                    mkdir(
                        $path,
                        0755,
                        true
                    );
                }

                $extension =
                    $request
                        ->file('image')
                        ->getClientOriginalExtension();

                $filename =
                    $user->id .
                    '.' .
                    $extension;

                if (
                    $user->image &&
                    $user->image !==
                    'no_image.png'
                ) {
                    $oldImagePath =
                        $path .
                        $user->image;

                    if (
                    file_exists(
                        $oldImagePath
                    )
                    ) {
                        unlink(
                            $oldImagePath
                        );
                    }
                }

                $request
                    ->file('image')
                    ->move(
                        $path,
                        $filename
                    );

                $user->image =
                    $filename;

            } elseif (
            !$user->image
            ) {

                $user->image =
                    'no_image.png';
            }

            $user->save();

            if (!$user->is_tenant_owner) {

                /*
                 * Un solo perfil.
                 */
                $user->syncRoles([
                    $role
                ]);

                /*
                 * Companies.
                 */
                $companySync = [];

                foreach (
                    $companyIds as
                    $companyId
                ) {
                    $companySync[
                    $companyId
                    ] = [
                        'is_default' =>
                            $companyId ===
                            $defaultCompanyId,

                        'is_active' =>
                            true,
                    ];
                }

                $user->companies()
                    ->sync(
                        $companySync
                    );

                /*
                 * Branches.
                 */
                $branchSync = [];

                foreach (
                    $branchIds as
                    $branchId
                ) {
                    $branchSync[
                    $branchId
                    ] = [
                        'is_default' =>
                            $branchId ===
                            $defaultBranchId,

                        'is_active' =>
                            true,
                    ];
                }

                $user->branches()
                    ->sync(
                        $branchSync
                    );
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo actualizar el usuario.'
            ], 422);
        }

        return response()->json([
            'message' =>
                'Usuario actualizado correctamente.'
        ]);
    }

    public function resetPassword($id)
    {
        $this->ensureTenantOwner();

        $user = $this->findTenantUserOrFail($id);

        /*
         * Generamos una contraseña temporal.
         *
         * Evitamos caracteres ambiguos como:
         * 0 O l I
         */
        $temporaryPassword =$this->generateTemporaryPassword();

        $user->password =
            Hash::make(
                $temporaryPassword
            );

        $user->must_change_password =
            true;

        $user->remember_token =
            null;

        $user->save();

        return response()->json([
            'message' =>
                'La contraseña fue reseteada correctamente.',

            /*
             * Se devuelve únicamente en esta respuesta
             * para que el Owner pueda entregársela
             * al usuario.
             */
            'temporary_password' =>
                $temporaryPassword,
        ]);
    }

    private function generateTemporaryPassword()
    {
        /*
         * 10 caracteres:
         *
         * mayúscula
         * minúscula
         * número
         * símbolo
         * + 6 aleatorios
         */

        $upper =
            'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $lower =
            'abcdefghijkmnopqrstuvwxyz';

        $numbers =
            '23456789';

        $symbols =
            '!@#$%';

        $all =
            $upper .
            $lower .
            $numbers .
            $symbols;

        $password =
            $upper[
            random_int(
                0,
                strlen($upper) - 1
            )
            ];

        $password .=
            $lower[
            random_int(
                0,
                strlen($lower) - 1
            )
            ];

        $password .=
            $numbers[
            random_int(
                0,
                strlen($numbers) - 1
            )
            ];

        $password .=
            $symbols[
            random_int(
                0,
                strlen($symbols) - 1
            )
            ];

        for ($i = 0; $i < 6; $i++) {

            $password .=
                $all[
                random_int(
                    0,
                    strlen($all) - 1
                )
                ];
        }

        /*
         * Mezclamos usando random_int en lugar
         * de depender de str_shuffle.
         */
        $characters =
            str_split(
                $password
            );

        for (
            $i = count($characters) - 1;
            $i > 0;
            $i--
        ) {
            $j =
                random_int(
                    0,
                    $i
                );

            $tmp =
                $characters[$i];

            $characters[$i] =
                $characters[$j];

            $characters[$j] =
                $tmp;
        }

        return implode(
            '',
            $characters
        );
    }

    public function changeStatus( Request $request, $id, TenantPlanService $planService ) {
        $this->ensureTenantOwner();

        $user = $this
            ->findTenantUserOrFail($id);

        $authUser = Auth::user();

        if ($user->id === $authUser->id) {
            return response()->json([
                'message' =>
                    'No puedes cambiar el estado de tu propia cuenta.'
            ], 422);
        }

        if ($user->is_tenant_owner) {
            return response()->json([
                'message' =>
                    'El propietario del tenant no puede ser inhabilitado desde este módulo.'
            ], 422);
        }

        $request->validate([
            'status' => [
                'required',
                'in:0,1'
            ],
        ]);

        $newStatus =
            (int) $request->status;

        /*
         * Si ya está en el mismo estado,
         * devolvemos sin hacer nada.
         */
        if (
            (int) $user->enable
            ===
            $newStatus
        ) {
            return response()->json([
                'message' =>
                    'El usuario ya se encuentra en ese estado.',
                'enable' =>
                    (bool) $user->enable,
            ]);
        }

        /*
         * Para HABILITAR hay que validar
         * capacidad del plan.
         */
        if ($newStatus === 1) {

            $tenant =
                $authUser->tenant;

            try {

                $planService
                    ->ensureCanActivateUser(
                        $tenant
                    );

            } catch (\RuntimeException $e) {

                return response()->json([
                    'message' =>
                        $e->getMessage(),
                ], 422);
            }
        }

        $user->enable =
            $newStatus === 1;

        /*
         * Al inhabilitar invalidamos
         * remember_token.
         */
        if (!$user->enable) {
            $user->remember_token = null;
        }

        $user->save();

        return response()->json([
            'message' =>
                $user->enable
                    ? 'Usuario activado correctamente.'
                    : 'Usuario inhabilitado correctamente.',

            'enable' =>
                (bool) $user->enable,
        ]);
    }

    public function planSummary(TenantPlanService $planService) {
        $this->ensureTenantOwner();

        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            return response()->json([
                'message' =>
                    'No se encontró el tenant del usuario.'
            ], 422);
        }

        if (!$tenant->plan) {
            return response()->json([
                'message' =>
                    'El tenant no tiene un plan asignado.'
            ], 422);
        }

        $activeUsers =
            $planService->activeUsersCount(
                $tenant
            );

        $maxUsers =
            $planService->maxActiveUsers(
                $tenant
            );

        $availableUsers =
            $planService->availableUsers(
                $tenant
            );

        $inactiveUsers =
            User::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'is_platform_admin',
                    false
                )
                ->where(
                    'enable',
                    false
                )
                ->count();

        $usagePercentage = 0;

        if ($maxUsers > 0) {
            $usagePercentage = round(
                ($activeUsers / $maxUsers) * 100,
                2
            );
        }

        return response()->json([
            'plan' => [
                'id' =>
                    $tenant->plan->id,

                'code' =>
                    $tenant->plan->code,

                'name' =>
                    $tenant->plan->name,
            ],

            'users' => [
                'active' =>
                    $activeUsers,

                'inactive' =>
                    $inactiveUsers,

                'max' =>
                    $maxUsers,

                'available' =>
                    $availableUsers,

                'usage_percentage' =>
                    $usagePercentage,

                'limit_reached' =>
                    $availableUsers <= 0,
            ],
        ]);
    }

    public function create()
    {
        $this->ensureTenantOwner();

        $authUser = Auth::user();

        $tenant = $authUser->tenant;

        if (!$tenant) {
            abort(
                422,
                'El usuario no tiene un tenant asignado.'
            );
        }

        /*
         * Solo perfiles que:
         *
         * - pertenezcan al tenant;
         * - estén activos;
         * - puedan ser asignados por el Owner.
         */
        $roles = Role::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_owner_assignable',
                true
            )
            ->select(
                'id',
                'name',
                'description'
            )
            ->orderBy('description')
            ->get();

        /*
         * El Tenant Owner puede administrar
         * las Companies de todo su tenant.
         */
        $companies = Company::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->with([
                'branches' => function ($query) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->select(
                            'id',
                            'company_id',
                            'name',
                            'code',
                            'is_main'
                        )
                        ->orderByDesc(
                            'is_main'
                        )
                        ->orderBy(
                            'name'
                        );
                },
            ])
            ->select(
                'id',
                'business_name',
                'trade_name'
            )
            ->orderBy(
                'business_name'
            )
            ->get();

        return view(
            'configUserWeb.create',
            compact(
                'tenant',
                'roles',
                'companies'
            )
        );
    }

    public function store(Request $request, TenantPlanService $planService) {
        $this->ensureTenantOwner();

        $authUser = Auth::user();

        $tenant = $authUser->tenant;

        if (!$tenant) {
            return response()->json([
                'message' =>
                    'No se encontró el tenant del usuario.'
            ], 422);
        }

        /*
         * Primero validamos el límite comercial.
         */
        try {

            $planService
                ->ensureCanActivateUser(
                    $tenant
                );

        } catch (\RuntimeException $e) {

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'role_id' => [
                'required',
                'integer',
            ],

            'companies' => [
                'required',
                'array',
                'min:1',
            ],

            'companies.*' => [
                'integer',
            ],

            'branches' => [
                'required',
                'array',
                'min:1',
            ],

            'branches.*' => [
                'integer',
            ],

            'default_company_id' => [
                'required',
                'integer',
            ],

            'default_branch_id' => [
                'required',
                'integer',
            ],
        ], [
            'name.required' =>
                'El nombre es obligatorio.',

            'email.required' =>
                'El correo electrónico es obligatorio.',

            'email.email' =>
                'Ingrese un correo electrónico válido.',

            'email.unique' =>
                'Este correo electrónico ya está registrado.',

            'role_id.required' =>
                'Seleccione un perfil.',

            'companies.required' =>
                'Seleccione al menos una empresa.',

            'branches.required' =>
                'Seleccione al menos un local.',

            'default_company_id.required' =>
                'Seleccione una empresa predeterminada.',

            'default_branch_id.required' =>
                'Seleccione un local predeterminado.',
        ]);

        /*
         * -------------------------------------------------
         * VALIDAR ROLE
         * -------------------------------------------------
         *
         * Nunca confiamos en role_id enviado por frontend.
         */
        $role = Role::query()
            ->where(
                'id',
                $validated['role_id']
            )
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_owner_assignable',
                true
            )
            ->first();

        if (!$role) {
            return response()->json([
                'message' =>
                    'El perfil seleccionado no está disponible para este tenant.'
            ], 422);
        }

        /*
         * -------------------------------------------------
         * VALIDAR COMPANIES
         * -------------------------------------------------
         */
        $companyIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['companies']
                )
            )
        );

        $validCompanies = Company::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'id',
                $companyIds
            )
            ->get();

        if (
            $validCompanies->count()
            !==
            count($companyIds)
        ) {
            return response()->json([
                'message' =>
                    'Una o más empresas seleccionadas no pertenecen al tenant.'
            ], 422);
        }

        /*
         * -------------------------------------------------
         * VALIDAR COMPANY DEFAULT
         * -------------------------------------------------
         */
        $defaultCompanyId =
            (int) $validated[
            'default_company_id'
            ];

        if (
        !in_array(
            $defaultCompanyId,
            $companyIds,
            true
        )
        ) {
            return response()->json([
                'message' =>
                    'La empresa predeterminada debe estar entre las empresas autorizadas.'
            ], 422);
        }

        /*
         * -------------------------------------------------
         * VALIDAR BRANCHES
         * -------------------------------------------------
         */
        $branchIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['branches']
                )
            )
        );

        $validBranches = Branch::query()
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'company_id',
                $companyIds
            )
            ->whereIn(
                'id',
                $branchIds
            )
            ->with('company')
            ->get();

        /*
         * Además comprobamos tenant vía Company.
         */
        $validBranches = $validBranches
            ->filter(
                function ($branch) use (
                    $tenant
                ) {
                    return
                        $branch->company &&
                        (int)
                        $branch->company
                            ->tenant_id
                        ===
                        (int)
                        $tenant->id;
                }
            )
            ->values();

        if (
            $validBranches->count()
            !==
            count($branchIds)
        ) {
            return response()->json([
                'message' =>
                    'Uno o más locales seleccionados no son válidos para el tenant.'
            ], 422);
        }

        /*
         * -------------------------------------------------
         * VALIDAR BRANCH DEFAULT
         * -------------------------------------------------
         */
        $defaultBranchId =
            (int) $validated[
            'default_branch_id'
            ];

        if (
        !in_array(
            $defaultBranchId,
            $branchIds,
            true
        )
        ) {
            return response()->json([
                'message' =>
                    'El local predeterminado debe estar entre los locales autorizados.'
            ], 422);
        }

        $defaultBranch =
            $validBranches
                ->firstWhere(
                    'id',
                    $defaultBranchId
                );

        if (
            !$defaultBranch ||
            (int) $defaultBranch->company_id
            !==
            $defaultCompanyId
        ) {
            return response()->json([
                'message' =>
                    'El local predeterminado debe pertenecer a la empresa predeterminada.'
            ], 422);
        }

        /*
         * Antes de crear volvemos a comprobar cupo.
         *
         * Esto reduce problemas si dos peticiones
         * intentan crear usuarios casi simultáneamente.
         */
        try {

            $planService
                ->ensureCanActivateUser(
                    $tenant
                );

        } catch (\RuntimeException $e) {

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        $temporaryPassword =
            $this->generateTemporaryPassword();

        DB::beginTransaction();

        try {

            $user = User::create([
                'tenant_id' =>
                    $tenant->id,

                'is_platform_admin' =>
                    false,

                'is_tenant_owner' =>
                    false,

                /*
                 * Campo antiguo de cajas.
                 * No tiene relación con Tenant Owner.
                 */
                'owner' =>
                    false,

                'name' =>
                    $validated['name'],

                'email' =>
                    $validated['email'],

                'password' =>
                    Hash::make(
                        $temporaryPassword
                    ),

                'image' =>
                    'no_image.png',

                'enable' =>
                    true,

                'must_change_password' =>
                    true,
            ]);

            /*
             * Un solo perfil operativo.
             */
            $user->syncRoles([
                $role
            ]);

            /*
             * Companies autorizadas.
             */
            $companySync = [];

            foreach (
                $companyIds as
                $companyId
            ) {
                $companySync[
                $companyId
                ] = [
                    'is_default' =>
                        $companyId ===
                        $defaultCompanyId,

                    'is_active' =>
                        true,
                ];
            }

            $user->companies()
                ->sync(
                    $companySync
                );

            /*
             * Branches autorizadas.
             */
            $branchSync = [];

            foreach (
                $branchIds as
                $branchId
            ) {
                $branchSync[
                $branchId
                ] = [
                    'is_default' =>
                        $branchId ===
                        $defaultBranchId,

                    'is_active' =>
                        true,
                ];
            }

            $user->branches()
                ->sync(
                    $branchSync
                );

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo crear el usuario.'
            ], 422);
        }

        return response()->json([
            'message' =>
                'Usuario creado correctamente.',

            'user' => [
                'id' =>
                    $user->id,

                'name' =>
                    $user->name,

                'email' =>
                    $user->email,
            ],

            /*
             * Solo se devuelve ahora.
             *
             * No la almacenamos en texto plano.
             */
            'temporary_password' =>
                $temporaryPassword,
        ]);
    }
}
