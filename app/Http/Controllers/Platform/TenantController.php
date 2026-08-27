<?php

namespace App\Http\Controllers\Platform;

use App\Branch;
use App\Company;
use App\Http\Controllers\Controller;
use App\Plan;
use App\Role;
use App\RoleTemplate;
use App\Services\PlatformAuditService;
use App\Services\TemporaryPasswordService;
use App\Services\TenantPlanService;
use App\Services\TenantRoleService;
use App\Tenant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index()
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->select(
                'id',
                'name',
                'code',
                'max_active_users'
            )
            ->orderBy('name')
            ->get();

        return view(
            'platform.tenants.index',
            compact('plans')
        );
    }

    public function data( Request $request, TenantPlanService $planService ) {
        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (
        !in_array(
            $perPage,
            [10, 25, 50],
            true
        )
        ) {
            $perPage = 10;
        }

        $query = Tenant::query()
            ->with([
                'plan',
            ])
            ->withCount([
                'companies',
            ])
            ->orderByDesc('id');

        /*
         * Búsqueda.
         */
        if ($request->filled('search')) {

            $search = trim(
                $request->get('search')
            );

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );

                }
            );
        }

        /*
         * Plan.
         */
        if ($request->filled('plan_id')) {

            $query->where(
                'plan_id',
                $request->get('plan_id')
            );
        }

        /*
         * Estado.
         */
        if ($request->filled('status')) {

            $query->where(
                'is_active',
                (int) $request->get('status')
            );
        }

        $tenants = $query->paginate(
            $perPage
        );

        $tenants->getCollection()
            ->transform(
                function ($tenant) use (
                    $planService
                ) {

                    $owner = User::query()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->where(
                            'is_tenant_owner',
                            true
                        )
                        ->where(
                            'is_platform_admin',
                            false
                        )
                        ->first();

                    $activeUsers =
                        $planService
                            ->activeUsersCount(
                                $tenant
                            );

                    $maxUsers =
                        $planService
                            ->maxActiveUsers(
                                $tenant
                            );

                    $availableUsers =
                        $planService
                            ->availableUsers(
                                $tenant
                            );

                    return [
                        'id' =>
                            $tenant->id,

                        'name' =>
                            $tenant->name,

                        'is_active' =>
                            (bool)
                            $tenant->is_active,

                        'plan' => [
                            'id' =>
                                optional(
                                    $tenant->plan
                                )->id,

                            'name' =>
                                optional(
                                    $tenant->plan
                                )->name,

                            'code' =>
                                optional(
                                    $tenant->plan
                                )->code,
                        ],

                        'users' => [
                            'active' =>
                                $activeUsers,

                            'max' =>
                                $maxUsers,

                            'available' =>
                                $availableUsers,
                        ],

                        'companies_count' =>
                            $tenant
                                ->companies_count,

                        'owner' => [
                            'id' =>
                                optional(
                                    $owner
                                )->id,

                            'name' =>
                                optional(
                                    $owner
                                )->name,

                            'email' =>
                                optional(
                                    $owner
                                )->email,
                        ],

                        'created_at' =>
                            optional(
                                $tenant
                                    ->created_at
                            )->format(
                                'd/m/Y'
                            ),
                    ];
                }
            );

        return response()->json(
            $tenants
        );
    }

    public function show( $id, TenantPlanService $planService ) {
        $tenant = Tenant::query()
            ->with([
                'plan',
                'companies',
            ])
            ->findOrFail($id);

        $owner = User::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_tenant_owner',
                true
            )
            ->where(
                'is_platform_admin',
                false
            )
            ->first();

        $activeUsers =
            $planService
                ->activeUsersCount(
                    $tenant
                );

        $maxUsers =
            $planService
                ->maxActiveUsers(
                    $tenant
                );

        $availableUsers =
            $planService
                ->availableUsers(
                    $tenant
                );

        $inactiveUsers = User::query()
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

        $usersCount = User::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_platform_admin',
                false
            )
            ->count();

        $plans = Plan::query()
            ->where('is_active', true)
            ->select(
                'id',
                'name',
                'max_active_users'
            )
            ->orderBy('name')
            ->get();

        return view(
            'platform.tenants.show',
            compact(
                'tenant',
                'owner',
                'activeUsers',
                'maxUsers',
                'availableUsers',
                'inactiveUsers',
                'usersCount',
                'plans'
            )
        );
    }

    public function create()
    {
        $plans = Plan::query()
            ->where(
                'is_active',
                true
            )
            ->select(
                'id',
                'code',
                'name',
                'max_active_users'
            )
            ->orderBy('name')
            ->get();

        return view(
            'platform.tenants.create',
            compact(
                'plans'
            )
        );
    }

    public function store(
        Request $request,
        TenantRoleService $tenantRoleService,
        TenantPlanService $planService,
        TemporaryPasswordService $passwordService,
        PlatformAuditService $auditService
    ) {
        $validated = $request->validate([
            'tenant_name' => [
                'required',
                'string',
                'max:150',
                'unique:tenants,name',
            ],

            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],

            'company_business_name' => [
                'required',
                'string',
                'max:200',
            ],

            'company_trade_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'company_ruc' => [
                'required',
                'digits:11',
                'unique:companies,ruc',
            ],

            'branch_name' => [
                'required',
                'string',
                'max:150',
            ],

            'branch_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'owner_name' => [
                'required',
                'string',
                'max:255',
            ],

            'owner_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
        ]);

        $plan = Plan::query()
            ->where(
                'id',
                $validated['plan_id']
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$plan) {
            return response()->json([
                'message' =>
                    'El plan seleccionado no está disponible.',
            ], 422);
        }


        $ownerTemplate = RoleTemplate::query()
            ->where(
                'code',
                'owner'
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$ownerTemplate) {
            return response()->json([
                'message' =>
                    'No existe una plantilla Owner activa.',
            ], 422);
        }

        $temporaryPassword =
            $passwordService->generate();

        try {

            $result = DB::transaction(
                function () use (
                    $validated,
                    $plan,
                    $ownerTemplate,
                    $temporaryPassword,
                    $tenantRoleService,
                    $planService,
                    $auditService
                ) {

                    $slug = Str::slug(
                        $validated['tenant_name']
                    );

                    $tenant = Tenant::create([
                        'name' =>
                            $validated[
                            'tenant_name'
                            ],

                        'slug' =>
                            $slug,

                        'plan_id' =>
                            $plan->id,

                        'is_active' =>
                            true,
                    ]);


                    $company = Company::create([
                        'tenant_id' =>
                            $tenant->id,

                        'business_name' =>
                            $validated[
                            'company_business_name'
                            ],

                        'trade_name' =>
                            $validated[
                            'company_trade_name'
                            ] ?? null,

                        'ruc' =>
                            $validated[
                            'company_ruc'
                            ],

                        'is_active' =>
                            true,
                    ]);

                    /** @var \App\Services\Inventory\CompanyInventoryStructureService $inventoryStructureService */
                    $inventoryStructureService =
                        app(
                            \App\Services\Inventory\CompanyInventoryStructureService::class
                        );

                    $inventoryStructureService
                        ->provision(
                            $company
                        );


                    $branchCode =
                        !empty(
                        $validated[
                        'branch_code'
                        ]
                        )
                            ? strtoupper(
                            $validated[
                            'branch_code'
                            ]
                        )
                            : 'PRINCIPAL';


                    $branch = Branch::create([
                        'company_id' =>
                            $company->id,

                        'name' =>
                            $validated[
                            'branch_name'
                            ],

                        'code' =>
                            $branchCode,

                        'is_main' =>
                            true,

                        'is_active' =>
                            true,
                    ]);


                    /*
                     * Crear roles estándar
                     * a partir de templates.
                     */
                    $tenantRoleService
                        ->syncTenant(
                            $tenant
                        );


                    $ownerRole = Role::query()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->where(
                            'role_template_id',
                            $ownerTemplate->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();


                    if (!$ownerRole) {

                        throw new \RuntimeException(
                            'No se pudo generar el perfil Owner para el nuevo Tenant.'
                        );
                    }


                    /*
                     * El Owner ya consume un cupo.
                     */
                    $planService
                        ->ensureCanActivateUser(
                            $tenant
                        );


                    $owner = User::create([
                        'tenant_id' =>
                            $tenant->id,

                        'name' =>
                            $validated[
                            'owner_name'
                            ],

                        'email' =>
                            $validated[
                            'owner_email'
                            ],

                        'password' =>
                            Hash::make(
                                $temporaryPassword
                            ),

                        'image' =>
                            'no_image.png',

                        'enable' =>
                            true,

                        'is_platform_admin' =>
                            false,

                        'is_tenant_owner' =>
                            true,

                        'owner' =>
                            false,

                        'must_change_password' =>
                            true,
                    ]);


                    $owner->syncRoles([
                        $ownerRole
                    ]);


                    $owner->companies()
                        ->sync([
                            $company->id => [
                                'is_default' =>
                                    true,

                                'is_active' =>
                                    true,
                            ],
                        ]);


                    $owner->branches()
                        ->sync([
                            $branch->id => [
                                'is_default' =>
                                    true,

                                'is_active' =>
                                    true,
                            ],
                        ]);


                    $auditService->log(
                        'tenant.created',
                        $tenant,
                        [
                            'tenant_id' =>
                                $tenant->id,

                            'tenant_name' =>
                                $tenant->name,

                            'new' => [

                                'tenant' => [
                                    'id' =>
                                        $tenant->id,

                                    'name' =>
                                        $tenant->name,

                                    'plan_id' =>
                                        $plan->id,

                                    'plan_name' =>
                                        $plan->name,

                                    'is_active' =>
                                        true,
                                ],

                                'company' => [
                                    'id' =>
                                        $company->id,

                                    'business_name' =>
                                        $company
                                            ->business_name,

                                    'trade_name' =>
                                        $company
                                            ->trade_name,

                                    'ruc' =>
                                        $company->ruc,
                                ],

                                'branch' => [
                                    'id' =>
                                        $branch->id,

                                    'name' =>
                                        $branch->name,

                                    'code' =>
                                        $branch->code,
                                ],

                                'owner' => [
                                    'id' =>
                                        $owner->id,

                                    'name' =>
                                        $owner->name,

                                    'email' =>
                                        $owner->email,

                                    'role_id' =>
                                        $ownerRole->id,
                                ],
                            ],
                        ]
                    );


                    return [
                        'tenant' =>
                            $tenant,

                        'owner' =>
                            $owner,
                    ];
                }
            );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo completar el alta del Tenant.',
            ], 422);
        }


        return response()->json([
            'message' =>
                'Tenant creado correctamente.',

            'tenant' => [
                'id' =>
                    $result[
                    'tenant'
                    ]->id,

                'name' =>
                    $result[
                    'tenant'
                    ]->name,
            ],

            'owner' => [
                'id' =>
                    $result[
                    'owner'
                    ]->id,

                'name' =>
                    $result[
                    'owner'
                    ]->name,

                'email' =>
                    $result[
                    'owner'
                    ]->email,
            ],

            'temporary_password' =>
                $temporaryPassword,
        ]);
    }

    public function resetOwnerPassword(
        $tenantId,
        TemporaryPasswordService $passwordService,
        PlatformAuditService $auditService
    ) {
        $tenant =
            Tenant::findOrFail(
                $tenantId
            );

        $owner = User::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_tenant_owner',
                true
            )
            ->where(
                'is_platform_admin',
                false
            )
            ->firstOrFail();


        $temporaryPassword =
            $passwordService->generate();


        DB::transaction(
            function () use (
                $tenant,
                $owner,
                $temporaryPassword,
                $auditService
            ) {

                $owner->password =
                    Hash::make(
                        $temporaryPassword
                    );

                $owner->must_change_password =
                    true;

                $owner->remember_token =
                    null;

                $owner->save();


                $auditService->log(
                    'tenant_owner.password_reset',
                    $owner,
                    [
                        'tenant_id' =>
                            $tenant->id,

                        'tenant_name' =>
                            $tenant->name,

                        'owner_id' =>
                            $owner->id,

                        'owner_email' =>
                            $owner->email,
                    ]
                );
            }
        );


        return response()->json([
            'message' =>
                'La contraseña del propietario fue reseteada correctamente.',

            'temporary_password' =>
                $temporaryPassword,
        ]);
    }

    public function editData($id)
    {
        $tenant = Tenant::findOrFail($id);

        return response()->json([
            'id' =>
                $tenant->id,

            'name' =>
                $tenant->name,

            'plan_id' =>
                $tenant->plan_id,

            'is_active' =>
                (bool)
                $tenant->is_active,
        ]);
    }

    public function update( Request $request, $id, PlatformAuditService $auditService ) {
        $tenant = Tenant::findOrFail($id);

        $tenant->load('plan');

        $oldValues = [
            'name' =>
                $tenant->name,

            'plan_id' =>
                $tenant->plan_id,

            'plan_name' =>
                optional(
                    $tenant->plan
                )->name,

            'is_active' =>
                (bool)
                $tenant->is_active,
        ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'tenants',
                    'name'
                )->ignore(
                    $tenant->id
                ),
            ],

            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],
        ], [
            'name.required' =>
                'El nombre del tenant es obligatorio.',

            'name.unique' =>
                'Ya existe un tenant con ese nombre.',

            'plan_id.required' =>
                'Seleccione un plan.',
        ]);

        $plan = Plan::query()
            ->where(
                'id',
                $validated['plan_id']
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$plan) {
            return response()->json([
                'message' =>
                    'El plan seleccionado no está disponible.'
            ], 422);
        }

        DB::transaction(function () use (
            $tenant,
            $validated,
            $auditService,
            $oldValues
        ) {

            $tenant->update([
                'name' =>
                    $validated['name'],

                'plan_id' =>
                    $validated['plan_id'],
            ]);

            $tenant->refresh();

            $newValues = [
                'name' =>
                    $tenant->name,

                'plan_id' =>
                    $tenant->plan_id,

                'plan_name' =>
                    optional(
                        $tenant->plan
                    )->name,

                'is_active' =>
                    (bool)
                    $tenant->is_active,
            ];

            if ($oldValues !== $newValues) {

                $auditService->log(
                    'tenant.updated',
                    $tenant,
                    [
                        'tenant_id' =>
                            $tenant->id,

                        'tenant_name' =>
                            $tenant->name,

                        'old' =>
                            $oldValues,

                        'new' =>
                            $newValues,
                    ]
                );
            }
        });

        return response()->json([
            'message' =>
                'Tenant actualizado correctamente.',
        ]);
    }

    public function toggleStatus( $id, PlatformAuditService $auditService ) {
        $tenant = Tenant::findOrFail($id);

        DB::transaction(function () use (
            $tenant,
            $auditService
        ) {

            $oldStatus =
                (bool)
                $tenant->is_active;

            $tenant->is_active =
                !$tenant->is_active;

            $tenant->save();

            $auditService->log(
                'tenant.status_changed',
                $tenant,
                [
                    'tenant_id' =>
                        $tenant->id,

                    'tenant_name' =>
                        $tenant->name,

                    'old' => [
                        'is_active' =>
                            $oldStatus,
                    ],

                    'new' => [
                        'is_active' =>
                            (bool)
                            $tenant->is_active,
                    ],
                ]
            );
        });

        return response()->json([
            'message' =>
                $tenant->is_active
                    ? 'Tenant habilitado correctamente.'
                    : 'Tenant inhabilitado correctamente.',
        ]);
    }

}
