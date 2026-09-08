<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Company;
use App\Http\Requests\StoreTenantCompanyRequest;
use App\Services\Inventory\CompanyInventoryStructureService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateTenantCompanyRequest;

class TenantCompanyController extends Controller
{
    public function index()
    {
        $tenantId = TenantContext::tenantId();

        $companies = Company::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->with([
                'branches' => function ($query) {
                    $query
                        ->orderByDesc('is_main')
                        ->orderBy('name');
                }
            ])
            ->withCount('branches')
            ->orderByDesc('is_active')
            ->orderBy('business_name')
            ->get();

        return view(
            'tenantCompany.index',
            compact('companies')
        );
    }

    public function create()
    {
        return view(
            'tenantCompany.create'
        );
    }

    public function store(StoreTenantCompanyRequest $request, CompanyInventoryStructureService $inventoryStructureService) {
        $validated = $request->validated();

        $user = Auth::user();

        $tenantId =
            TenantContext::tenantId();


        /*
         * Defensa adicional.
         *
         * Aunque la ruta ya está protegida por tenant.owner,
         * aquí comprobamos nuevamente que:
         *
         * - sea Tenant Owner;
         * - no sea Platform Admin;
         * - pertenezca al Tenant del contexto.
         */
        if (
            !$user ||
            !$user->isTenantOwner() ||
            $user->isPlatformAdmin() ||
            (int) $user->tenant_id !== (int) $tenantId
        ) {
            abort(403);
        }


        try {

            $result = DB::transaction(
                function () use (
                    $validated,
                    $user,
                    $tenantId,
                    $inventoryStructureService
                ) {

                    /*
                     * ==================================================
                     * 1. CREAR COMPANY
                     * ==================================================
                     */

                    $company = Company::create([
                        'tenant_id' =>
                            $tenantId,

                        'ruc' =>
                            $validated['ruc'],

                        'business_name' =>
                            $validated['business_name'],

                        'trade_name' =>
                            $validated['trade_name']
                            ?? null,

                        'address' =>
                            $validated['address']
                            ?? null,

                        'phone' =>
                            $validated['phone']
                            ?? null,

                        'email' =>
                            $validated['email']
                            ?? null,

                        'is_active' =>
                            true,
                    ]);


                    /*
                     * ==================================================
                     * 2. RESOLVER DEFAULT DE COMPANY PARA EL OWNER
                     * ==================================================
                     *
                     * Si ya tiene una Company activa asignada:
                     *
                     * nueva Company => is_default = false
                     *
                     * No cambiamos su contexto/default actual.
                     */

                    $hasActiveCompany =
                        $user->companies()
                            ->wherePivot(
                                'is_active',
                                true
                            )
                            ->exists();

                    $companyIsDefault =
                        !$hasActiveCompany;


                    /*
                     * ==================================================
                     * 3. ASIGNAR OWNER A COMPANY
                     * ==================================================
                     *
                     * NO usar sync(), porque eliminaría las asignaciones
                     * anteriores.
                     */

                    $user->companies()
                        ->syncWithoutDetaching([
                            $company->id => [
                                'is_default' =>
                                    $companyIsDefault,

                                'is_active' =>
                                    true,
                            ],
                        ]);


                    /*
                     * ==================================================
                     * 4. CREAR BRANCH PRINCIPAL
                     * ==================================================
                     */

                    $branchCode =
                        strtoupper(
                            trim(
                                $validated['branch_code']
                            )
                        );


                    $branch = Branch::create([
                        'company_id' =>
                            $company->id,

                        'code' =>
                            $branchCode,

                        'name' =>
                            $validated['branch_name'],

                        /*
                         * Si no se proporcionó una dirección distinta,
                         * heredamos la dirección de Company.
                         */
                        'address' =>
                            !empty(
                            $validated['branch_address']
                            )
                                ? $validated['branch_address']
                                : $company->address,

                        /*
                         * Mismo criterio para teléfono.
                         */
                        'phone' =>
                            !empty(
                            $validated['branch_phone']
                            )
                                ? $validated['branch_phone']
                                : $company->phone,

                        'is_main' =>
                            true,

                        'is_active' =>
                            true,
                    ]);


                    /*
                     * ==================================================
                     * 5. RESOLVER DEFAULT DE BRANCH PARA EL OWNER
                     * ==================================================
                     */

                    $hasActiveBranch =
                        $user->branches()
                            ->wherePivot(
                                'is_active',
                                true
                            )
                            ->exists();

                    $branchIsDefault =
                        !$hasActiveBranch;


                    /*
                     * ==================================================
                     * 6. ASIGNAR OWNER A BRANCH
                     * ==================================================
                     */

                    $user->branches()
                        ->syncWithoutDetaching([
                            $branch->id => [
                                'is_default' =>
                                    $branchIsDefault,

                                'is_active' =>
                                    true,
                            ],
                        ]);


                    /*
                     * ==================================================
                     * 7. PROVISIONAR INVENTARIO GENERAL
                     * ==================================================
                     *
                     * Reutilizamos exactamente el mismo Service usado
                     * por Platform Admin.
                     */

                    $inventory =
                        $inventoryStructureService
                            ->provision(
                                $company
                            );


                    return [
                        'company' =>
                            $company,

                        'branch' =>
                            $branch,

                        'inventory' =>
                            $inventory,
                    ];
                }
            );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo crear la empresa. ' .
                    $e->getMessage(),
            ], 422);
        }


        return response()->json([
            'message' =>
                'Empresa creada correctamente.',

            'company' => [
                'id' =>
                    $result['company']->id,

                'ruc' =>
                    $result['company']->ruc,

                'business_name' =>
                    $result['company']->business_name,

                'trade_name' =>
                    $result['company']->trade_name,
            ],

            'branch' => [
                'id' =>
                    $result['branch']->id,

                'name' =>
                    $result['branch']->name,

                'code' =>
                    $result['branch']->code,
            ],

            /*
             * Importante:
             *
             * No modificamos TenantContext/session.
             * El Owner continúa trabajando en su Company actual.
             */
            'url' =>
                route(
                    'tenantCompany.index'
                ),
        ], 200);
    }

    public function edit($id)
    {
        $tenantId =
            TenantContext::tenantId();

        /*
         * Company no tiene TenantScope.
         *
         * Por eso el tenant_id es obligatorio
         * en todas las consultas de este módulo.
         */
        $company = Company::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->findOrFail($id);

        return view(
            'tenantCompany.edit',
            compact('company')
        );
    }

    public function update(UpdateTenantCompanyRequest $request, $id) {
        $validated =
            $request->validated();

        $tenantId =
            TenantContext::tenantId();


        $company = Company::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->findOrFail($id);


        DB::beginTransaction();

        try {

            $company->ruc =
                $validated['ruc'];

            $company->business_name =
                $validated['business_name'];

            $company->trade_name =
                $validated['trade_name']
                ?? null;

            $company->address =
                $validated['address']
                ?? null;

            $company->phone =
                $validated['phone']
                ?? null;

            $company->email =
                $validated['email']
                ?? null;

            $company->save();


            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo actualizar la empresa.',
            ], 422);
        }


        return response()->json([
            'message' =>
                'Empresa actualizada correctamente.',

            'url' =>
                route('tenantCompany.index'),
        ], 200);
    }

    public function toggleStatus($id)
    {
        $tenantId = TenantContext::tenantId();
        $currentCompanyId = TenantContext::companyId();

        try {

            $result = DB::transaction(
                function () use (
                    $id,
                    $tenantId,
                    $currentCompanyId
                ) {

                    /*
                     * Company no tiene TenantScope.
                     *
                     * Siempre filtramos explícitamente por Tenant.
                     */
                    $company = Company::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $id)
                        ->lockForUpdate()
                        ->firstOrFail();


                    /*
                     * =====================================================
                     * ACTIVAR
                     * =====================================================
                     */

                    if (!$company->is_active) {

                        $company->is_active = true;
                        $company->save();

                        return [
                            'company' => $company,
                            'action' => 'activated',
                        ];
                    }


                    /*
                     * =====================================================
                     * DESACTIVAR
                     * =====================================================
                     */


                    /*
                     * No permitimos desactivar la Company
                     * utilizada actualmente en TenantContext.
                     *
                     * El usuario debe cambiar primero de empresa.
                     */
                    if (
                        (int) $company->id ===
                        (int) $currentCompanyId
                    ) {
                        throw new \RuntimeException(
                            'No puede desactivar la empresa que está utilizando actualmente. Cambie primero a otra empresa.'
                        );
                    }


                    /*
                     * El Tenant siempre debe conservar al menos
                     * una Company activa.
                     *
                     * Bloqueamos también las demás Companies activas
                     * para evitar condiciones de carrera.
                     */
                    $otherActiveCompanies = Company::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', '<>', $company->id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->count();


                    if ($otherActiveCompanies === 0) {
                        throw new \RuntimeException(
                            'No puede desactivar la última empresa activa del grupo empresarial.'
                        );
                    }


                    /*
                     * No tocamos:
                     *
                     * - branches
                     * - company_user
                     * - branch_user
                     * - warehouses
                     * - locations
                     *
                     * La Company simplemente queda fuera de operación.
                     */
                    $company->is_active = false;
                    $company->save();


                    return [
                        'company' => $company,
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
                    ? 'Empresa activada correctamente.'
                    : 'Empresa desactivada correctamente.',

            'company' => [
                'id' =>
                    $result['company']->id,

                'is_active' =>
                    (bool) $result['company']->is_active,
            ],
        ], 200);
    }


}