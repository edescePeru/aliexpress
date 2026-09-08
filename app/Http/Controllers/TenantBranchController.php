<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Company;
use App\Http\Requests\StoreTenantBranchRequest;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateTenantBranchRequest;

class TenantBranchController extends Controller
{
    public function index($companyId)
    {
        $tenantId = TenantContext::tenantId();

        $company = Company::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($companyId);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        return view(
            'tenantBranch.index',
            compact(
                'company',
                'branches'
            )
        );
    }

    public function create($companyId)
    {
        $tenantId = TenantContext::tenantId();

        $company = Company::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail($companyId);

        return view(
            'tenantBranch.create',
            compact('company')
        );
    }

    public function store(StoreTenantBranchRequest $request) {
        $validated = $request->validated();

        $tenantId = TenantContext::tenantId();

        $user = Auth::user();


        if (
            !$user ||
            !$user->isTenantOwner() ||
            $user->isPlatformAdmin() ||
            (int) $user->tenant_id !== (int) $tenantId
        ) {
            abort(403);
        }


        /*
         * Segunda defensa:
         * nunca confiamos solamente en company_id del Request.
         */
        $company = Company::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail(
                $validated['company_id']
            );


        try {

            $branch = DB::transaction(
                function () use (
                    $validated,
                    $company,
                    $user
                ) {

                    $branch = Branch::create([
                        'company_id' =>
                            $company->id,

                        'code' =>
                            strtoupper(
                                trim(
                                    $validated['code']
                                )
                            ),

                        'name' =>
                            $validated['name'],

                        'address' =>
                            !empty($validated['address'])
                                ? $validated['address']
                                : $company->address,

                        'phone' =>
                            !empty($validated['phone'])
                                ? $validated['phone']
                                : $company->phone,

                        /*
                         * Es una sucursal adicional.
                         * No reemplaza la principal.
                         */
                        'is_main' =>
                            false,

                        'is_active' =>
                            true,
                    ]);


                    /*
                     * Asignar inmediatamente al Owner.
                     *
                     * No modificamos su Branch default actual.
                     */
                    $user->branches()
                        ->syncWithoutDetaching([
                            $branch->id => [
                                'is_default' =>
                                    false,

                                'is_active' =>
                                    true,
                            ],
                        ]);


                    return $branch;
                }
            );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo crear la sucursal. ' .
                    $e->getMessage(),
            ], 422);
        }


        return response()->json([
            'message' =>
                'Sucursal creada correctamente.',

            'branch' => [
                'id' =>
                    $branch->id,

                'company_id' =>
                    $branch->company_id,

                'code' =>
                    $branch->code,

                'name' =>
                    $branch->name,
            ],

            'url' =>
                route(
                    'tenantCompany.index'
                ),
        ], 200);
    }

    public function edit($id)
    {
        $tenantId = TenantContext::tenantId();

        $branch = Branch::query()
            ->whereHas('company', function ($query) use ($tenantId) {
                $query->where(
                    'tenant_id',
                    $tenantId
                );
            })
            ->with('company')
            ->findOrFail($id);

        return view(
            'tenantBranch.edit',
            compact('branch')
        );
    }

    public function update(UpdateTenantBranchRequest $request,$id) {
        $validated = $request->validated();

        $tenantId = TenantContext::tenantId();


        /*
         * Validar Branch dentro del Tenant actual.
         */
        $branch = Branch::query()
            ->whereHas('company', function ($query) use ($tenantId) {
                $query->where(
                    'tenant_id',
                    $tenantId
                );
            })
            ->findOrFail($id);


        /*
         * Evitar mover una Branch de una Company a otra
         * manipulando company_id.
         */
        if (
            (int) $branch->company_id !==
            (int) $validated['company_id']
        ) {
            return response()->json([
                'message' =>
                    'La sucursal no pertenece a la empresa indicada.',
            ], 422);
        }


        DB::beginTransaction();

        try {

            $branch->code =
                strtoupper(
                    trim(
                        $validated['code']
                    )
                );

            $branch->name =
                $validated['name'];

            $branch->address =
                $validated['address']
                ?? null;

            $branch->phone =
                $validated['phone']
                ?? null;

            /*
             * Deliberadamente NO tocamos:
             *
             * is_main
             * is_active
             * company_id
             */
            $branch->save();


            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo actualizar la sucursal.',
            ], 422);
        }


        return response()->json([
            'message' =>
                'Sucursal actualizada correctamente.',

            'url' =>
                route('tenantBranch.index', $branch->company_id),
        ], 200);
    }

    public function toggleStatus($id)
    {
        $tenantId = TenantContext::tenantId();
        $currentBranchId = TenantContext::branchId();

        try {

            $result = DB::transaction(
                function () use (
                    $id,
                    $tenantId,
                    $currentBranchId
                ) {

                    /*
                     * Branch no tiene tenant_id.
                     *
                     * Validamos que su Company pertenezca
                     * al Tenant actual.
                     */
                    $branch = Branch::query()
                        ->whereHas('company', function ($query) use ($tenantId) {
                            $query->where(
                                'tenant_id',
                                $tenantId
                            );
                        })
                        ->with('company')
                        ->where('id', $id)
                        ->lockForUpdate()
                        ->firstOrFail();


                    /*
                     * =====================================================
                     * ACTIVAR
                     * =====================================================
                     */

                    if (!$branch->is_active) {

                        /*
                         * No tiene sentido activar una sucursal
                         * si su Company está inactiva.
                         */
                        if (!$branch->company->is_active) {
                            throw new \RuntimeException(
                                'No puede activar la sucursal mientras su empresa se encuentre inactiva.'
                            );
                        }

                        $branch->is_active = true;
                        $branch->save();

                        return [
                            'branch' => $branch,
                            'action' => 'activated',
                        ];
                    }


                    /*
                     * =====================================================
                     * DESACTIVAR
                     * =====================================================
                     */


                    /*
                     * La Branch actualmente utilizada no puede
                     * desactivarse.
                     */
                    if (
                        (int) $branch->id ===
                        (int) $currentBranchId
                    ) {
                        throw new \RuntimeException(
                            'No puede desactivar la sucursal que está utilizando actualmente. Cambie primero a otra sucursal.'
                        );
                    }


                    /*
                     * Por ahora no permitiremos desactivar
                     * la sucursal principal.
                     *
                     * Más adelante, si implementamos "cambiar sucursal
                     * principal", primero deberá asignarse otra como principal.
                     */
                    if ($branch->is_main) {
                        throw new \RuntimeException(
                            'No puede desactivar la sucursal principal de la empresa.'
                        );
                    }


                    /*
                     * Defensa adicional:
                     * la Company siempre debe conservar al menos
                     * una Branch activa.
                     */
                    $otherActiveBranches = Branch::query()
                        ->where(
                            'company_id',
                            $branch->company_id
                        )
                        ->where(
                            'id',
                            '<>',
                            $branch->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->lockForUpdate()
                        ->count();


                    if ($otherActiveBranches === 0) {
                        throw new \RuntimeException(
                            'No puede desactivar la última sucursal activa de la empresa.'
                        );
                    }


                    /*
                     * No modificamos branch_user.
                     *
                     * Las asignaciones permanecen para una futura
                     * reactivación.
                     */
                    $branch->is_active = false;
                    $branch->save();


                    return [
                        'branch' => $branch,
                        'action' => 'deactivated',
                    ];
                }
            );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }


        return response()->json([
            'message' =>
                $result['action'] === 'activated'
                    ? 'Sucursal activada correctamente.'
                    : 'Sucursal desactivada correctamente.',

            'branch' => [
                'id' =>
                    $result['branch']->id,

                'is_active' =>
                    (bool) $result['branch']->is_active,
            ],
        ], 200);
    }
}