<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Plan;
use App\Services\PlatformAuditService;
use App\Services\TenantPlanService;
use App\Tenant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
