<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\CashSettlement;
use App\Support\TenantContext;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    public function indexCashRegister($type)
    {
        $user = Auth::user();
        $user_id = $user->id;

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $cashRegister = CashRegister::where('type', $type)
            ->where('user_id', $user_id)->latest()->first();

        // TODO: Puede pasar 3 cosas: Que no exista, que exista abierta, que exista cerrada

        $balance_total = 0;
        $buttons = [];
        $state = '<div class="col-md-4 col-6">
            <div class="small-box bg-secondary">
              <div class="inner">
                <h4>Cerrada</h4>
              </div>
              <div class="icon">
                <i class="fas fa-lock" style="font-size: 40px"></i>
              </div>
              <a href="#" id="btn-openCashRegister" class="small-box-footer">Abrir Caja <i class="fas fa-door-open"></i></a>
            </div>
          </div>';

        if ( !isset($cashRegister) )
        {
            // TODO: No existe
            array_push($buttons, ['open']);

        } else {
            // TODO: Existe

            if ( $cashRegister->status == 1 ) // abierta
            {
                $balance_total = $cashRegister->current_balance;
                array_push($buttons, ['close']);
                $state = '<div class="col-md-4 col-6">
                            <div class="small-box bg-success">
                              <div class="inner">
                                <h4>Abierta</h4>
                              </div>
                              <div class="icon">
                                <i class="fas fa-lock-open" style="font-size: 40px"></i>
                              
                              </div>
                              <a href="#" id="btn-closeCashRegister" class="small-box-footer">Cerrar Caja <i class="fas fa-door-closed"></i></a>
                            </div>
                          </div>';
            } else {
                // cerrada
                $balance_total = $cashRegister->closing_balance;
                array_push($buttons, ['open']);
                $state = '<div class="col-md-4 col-6">
                            <div class="small-box bg-danger">
                              <div class="inner">
                                <h4>Cerrada</h4>
                              </div>
                              <div class="icon">
                                <i class="fas fa-lock" style="font-size: 40px"></i>
                              </div>
                              <a href="#" id="btn-openCashRegister" class="small-box-footer">Abrir Caja <i class="fas fa-door-open"></i></a>
                            </div>
                          </div>';
            }

        }

        $active = '';
        if ( $type == 'efectivo' )
        {
            $active = 'Efectivo';
        } elseif ( $type == 'yape' ) {
            $active = 'Yape';
        } elseif ( $type == 'plin' ) {
            $active = 'Plin';
        } elseif ( $type == 'bancario' ) {
            $active = 'Bancario';
        }

        return view('cashRegister.index', compact( 'balance_total','buttons', 'permissions', 'active', 'state'));
    }

    public function openCashRegister( Request $request )
    {
        DB::beginTransaction();
        try {

            $type = $request->get('type');
            $balance_total = $request->get('balance_total');

            $caja = CashRegister::create([
                'opening_balance' => $balance_total,
                'current_balance' => $balance_total,
                'opening_time' => Carbon::now('America/Lima'),
                'type' => strtolower($type),
                'status' => 1,
                'user_id' => Auth::user()->id
            ]);

            $state = '<div class="col-md-4 col-6">
                            <div class="small-box bg-success">
                              <div class="inner">
                                <h4>Abierta</h4>
                              </div>
                              <div class="icon">
                                <i class="fas fa-lock-open" style="font-size: 40px"></i>
                              
                              </div>
                              <a href="#" id="btn-closeCashRegister" class="small-box-footer">Cerrar Caja <i class="fas fa-door-closed"></i></a>
                            </div>
                          </div>';

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Caja aperturada con éxito.',
            'state' => $state,
            'balance_total' => $caja->current_balance
        ], 200);
    }

    public function closeCashRegister( Request $request )
    {
        DB::beginTransaction();
        try {

            $type = $request->get('type');
            $balance_total = $request->get('balance_total');

            $cashRegister = CashRegister::where('type', $type)
                ->where('user_id', Auth::user()->id)->latest()->first();

            if ( !isset($cashRegister) )
            {
                // TODO: No existe
                return response()->json(['message' => "No existe una caja creada"], 422);
            } else {
                // TODO: Existe

                if ( $cashRegister->status == 0 )
                {
                    // cerrada
                    return response()->json(['message' => "Ya está cerrada la caja"], 422);
                }

            }

            $cashRegister->closing_balance = $balance_total;
            $cashRegister->current_balance = $balance_total;
            $cashRegister->closing_time = Carbon::now('America/Lima');
            $cashRegister->status = 0;
            $cashRegister->save();

            $state = '<div class="col-md-4 col-6">
                            <div class="small-box bg-danger">
                              <div class="inner">
                                <h4>Cerrada</h4>
                              </div>
                              <div class="icon">
                                <i class="fas fa-lock" style="font-size: 40px"></i>
                              </div>
                              <a href="#" id="btn-openCashRegister" class="small-box-footer">Abrir Caja <i class="fas fa-door-open"></i></a>
                            </div>
                          </div>';

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Caja cerrada con éxito.',
            'state' => $state,
            'balance_total' => $cashRegister->current_balance
        ], 200);
    }

    public function incomeCashRegister( Request $request )
    {
        DB::beginTransaction();
        try {
            $type = $request->get('type');
            $balance_total = $request->get('balance_total');
            $amount = $request->get('amount');
            $description = $request->get('description');

            $cashRegister = CashRegister::where('type', $type)
                ->where('user_id', Auth::user()->id)->latest()->first();

            if ( !isset($cashRegister) )
            {
                // TODO: No existe
                return response()->json(['message' => "No se puede hacer un ingreso a una caja inexistente."], 422);
            } else {
                // TODO: Existe

                if ( $cashRegister->status == 1 )
                {
                    // abierta

                    // 1. Registrar el movimiento en la tabla `CashMovement`
                    CashMovement::create([
                        'cash_register_id' => $cashRegister->id,
                        'type' => 'income', // Tipo de movimiento: ingreso
                        'amount' => $amount,
                        'description' => $description,
                    ]);

                    // 2. Actualizar los datos de `CashRegister`
                    $cashRegister->current_balance += $amount; // Actualizamos el saldo actual
                    $cashRegister->total_incomes += $amount; // Actualizamos el total de ingresos

                    // Guardar los cambios en la caja
                    $cashRegister->save();
                } else {
                    // cerrada
                    return response()->json(['message' => "No se puede hacer un ingreso a una caja cerrada."], 422);
                }

            }

            DB::commit();

            return response()->json([
                'message' => 'Ingreso registrado con éxito.',
                'balance_total' => $cashRegister->current_balance
            ], 200);

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

    }

    public function expenseCashRegister(Request $request)
    {
        DB::beginTransaction();
        try {
            $type = $request->get('type');
            $amount = $request->get('amount');
            $description = $request->get('description');

            // Buscar la caja abierta del usuario actual
            $cashRegister = CashRegister::where('type', $type)
                ->where('user_id', Auth::user()->id)
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                // Caja no encontrada
                return response()->json(['message' => "No se puede hacer un egreso de una caja inexistente."], 422);
            } else {
                if ($cashRegister->status == 1) {
                    // Caja abierta

                    // Validar que hay suficiente saldo en la caja
                    if ($cashRegister->current_balance < $amount) {
                        return response()->json(['message' => "No hay suficiente saldo en la caja para realizar este egreso."], 422);
                    }

                    // 1. Registrar el movimiento en la tabla `CashMovement`
                    CashMovement::create([
                        'cash_register_id' => $cashRegister->id,
                        'type' => 'expense', // Tipo de movimiento: egreso
                        'amount' => $amount,
                        'description' => $description,
                    ]);

                    // 2. Actualizar los datos de `CashRegister`
                    $cashRegister->current_balance -= $amount; // Restamos el monto del saldo actual
                    $cashRegister->total_expenses += $amount; // Actualizamos el total de egresos

                    // Guardar los cambios en la caja
                    $cashRegister->save();

                } else {
                    // Caja cerrada
                    return response()->json(['message' => "No se puede hacer un egreso de una caja cerrada."], 422);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Egreso registrado con éxito.',
                'balance_total' => $cashRegister->current_balance
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function getDataMovements(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $type = strtolower($request->input('type'));

        /*$cashRegister = CashRegister::where('type', $type)
            ->where('user_id', Auth::user()->id)->get();*/
        $cashRegisterIds = CashRegister::where('type', $type)
            ->where('user_id', Auth::user()->id)
            ->pluck('id'); // Devuelve una colección de IDs de CashRegister

        $array = [];
        $pagination = [];

        if ( isset($cashRegisterIds) )
        {
            //$query = CashMovement::where('cash_register_id', $cashRegister->id)->orderBy('id', 'desc');
            $query = CashMovement::whereIn('cash_register_id', $cashRegisterIds)
                ->orderBy('created_at', 'desc'); // Asegúrate de que haya un campo de fecha para ordenar

            $totalFilteredRecords = $query->count();
            $totalPages = ceil($totalFilteredRecords / $perPage);

            $startRecord = ($pageNumber - 1) * $perPage + 1;
            $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

            $movements = $query->skip(($pageNumber - 1) * $perPage)
                ->take($perPage)
                ->get();

            foreach ( $movements as $movement )
            {
                if ( $movement->type == 'income')
                {
                    $tipo = 'Ingreso';
                } elseif ( $movement->type == 'sale' && $movement->regularize == 0 ) {
                    $tipo = 'Regularizar';
                } elseif ( $movement->type == 'sale' && $movement->regularize == 1 ) {
                    $tipo = 'Venta';
                } elseif ( $movement->type == 'expense' ) {
                    $tipo = 'Egreso';
                } else {
                    $tipo = 'Venta';
                }

                array_push($array, [
                    "id" => $movement->id,
                    "type" => $tipo,
                    "amount" => $movement->amount,
                    "description" => $movement->description,
                    "date" => $movement->created_at->format('d/m/Y h:i A'),
                    "sale_id" => $movement->sale_id,
                ]);
            }

            $pagination = [
                'currentPage' => (int)$pageNumber,
                'totalPages' => (int)$totalPages,
                'startRecord' => $startRecord,
                'endRecord' => $endRecord,
                'totalRecords' => $totalFilteredRecords,
                'totalFilteredRecords' => $totalFilteredRecords
            ];
        }

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function regularizeCashRegister( Request $request )
    {
        //dd($request);
        DB::beginTransaction();
        try {
            $type = strtolower($request->get('type'));
            $cash_movement_id = $request->get('cash_movement_id');
            $amount = round((float)$request->get('amount'), 2);

            $cashRegister = CashRegister::where('type', $type)->latest()->first();

            if ( !isset($cashRegister) )
            {
                // TODO: No existe
                return response()->json(['message' => "No se puede hacer un ingreso a una caja inexistente."], 422);
            } else {
                // TODO: Existe

                if ( $cashRegister->status == 1 )
                {
                    // abierta

                    // 1. Registrar el movimiento en la tabla `CashMovement`
                    $cashMovement = CashMovement::find($cash_movement_id);
                    $cashMovement->amount = $amount;
                    $cashMovement->regularize = 1;
                    $cashMovement->save();

                    // 2. Actualizar los datos de `CashRegister`
                    $cashRegister->current_balance += $amount; // Actualizamos el saldo actual
                    $cashRegister->total_sales += $amount; // Actualizamos el total de ingresos

                    // Guardar los cambios en la caja
                    $cashRegister->save();
                } else {
                    // cerrada
                    return response()->json(['message' => "No se puede hacer un ingreso a una caja cerrada."], 422);
                }

            }

            DB::commit();

            return response()->json([
                'message' => 'Regularización registrada con éxito.',
                'balance_total' => round($cashRegister->current_balance, 2)
            ], 200);

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

    }

    public function store(Request $request)
    {
        //
    }

    public function show(CashRegister $cashRegister)
    {
        //
    }

    public function edit(CashRegister $cashRegister)
    {
        //
    }

    public function update(Request $request, CashRegister $cashRegister)
    {
        //
    }

    public function destroy(CashRegister $cashRegister)
    {
        //
    }

    public function open(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
             * =========================================================
             * 1. CONTEXTO ACTUAL
             * =========================================================
             */

            $tenantId =
                TenantContext::tenantId();

            $companyId =
                TenantContext::companyId();

            $branchId =
                TenantContext::branchId();

            $userId =
                Auth::id();

            /*
             * =========================================================
             * 2. VALIDAR PAYLOAD
             * =========================================================
             */

            $data =
                $request->validate(
                    [
                        'cash_box_id' =>
                            'required|integer',

                        'opening_balance' =>
                            'nullable|numeric|min:0',
                    ],
                    [
                        'cash_box_id.required' =>
                            'Debe seleccionar una caja.',

                        'cash_box_id.integer' =>
                            'La caja seleccionada no es válida.',

                        'opening_balance.numeric' =>
                            'El saldo inicial debe ser un valor numérico.',

                        'opening_balance.min' =>
                            'El saldo inicial no puede ser negativo.',
                    ],
                    [
                        'cash_box_id' =>
                            'caja',

                        'opening_balance' =>
                            'saldo inicial',
                    ]
                );

            /*
             * =========================================================
             * 3. RESOLVER CASHBOX DE FORMA SEGURA
             * =========================================================
             *
             * BelongsToTenant protege Tenant.
             *
             * Company se valida explícitamente.
             *
             * Branch:
             *
             * - branch_id NULL:
             *   CashBox disponible para toda la Company.
             *
             * - branch_id = branch actual:
             *   CashBox específica de esta sucursal.
             *
             * - branch_id de otra sucursal:
             *   no puede utilizarse.
             */

            $cashBox =
                CashBox::query()
                    ->where(
                        'id',
                        (int) $data['cash_box_id']
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->where(
                        function ($query) use (
                            $branchId
                        ) {

                            $query
                                ->whereNull(
                                    'branch_id'
                                )
                                ->orWhere(
                                    'branch_id',
                                    $branchId
                                );
                        }
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$cashBox) {

                throw ValidationException::withMessages([
                    'cash_box_id' => [
                        'La caja seleccionada no está disponible para la empresa y sucursal actual.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 4. VALIDAR SESIÓN ABIERTA EXISTENTE
             * =========================================================
             *
             * Un usuario sí puede tener, por ejemplo:
             *
             * - Efectivo abierto
             * - BCP abierto
             * - Interbank abierto
             *
             * al mismo tiempo.
             *
             * Lo que no permitimos es dos sesiones abiertas
             * del mismo usuario sobre la misma CashBox
             * en la misma Branch.
             */

            $existing =
                CashRegister::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'cash_box_id',
                        $cashBox->id
                    )
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'status',
                        1
                    )
                    ->lockForUpdate()
                    ->first();

            if ($existing) {

                DB::commit();

                return response()->json([
                    'message' =>
                        'Ya tienes una sesión abierta en esta caja.',

                    'cash_register_id' =>
                        $existing->id,

                    'cash_box_id' =>
                        $cashBox->id,
                ], 200);
            }

            /*
             * =========================================================
             * 5. SALDO DE APERTURA
             * =========================================================
             *
             * Por ahora conservamos tu regla:
             *
             * cash:
             * puede recibir saldo inicial.
             *
             * bank / other:
             * inicia en 0.
             *
             * IMPORTANTE:
             *
             * opening_balance NO se registra como CashMovement.
             *
             * No es un ingreso del negocio.
             * Es el saldo con el que inicia la sesión.
             */

            $openingBalance =
                0.00;

            if (
                $cashBox->type ===
                'cash'
            ) {

                $openingBalance =
                    isset(
                        $data[
                        'opening_balance'
                        ]
                    )
                        ? round(
                        (float)
                        $data[
                        'opening_balance'
                        ],
                        2
                    )
                        : 0.00;
            }

            /*
             * =========================================================
             * 6. TYPE LEGACY
             * =========================================================
             *
             * CashRegister.type queda temporalmente
             * únicamente por compatibilidad.
             *
             * La fuente moderna es:
             *
             * CashRegister -> CashBox -> type
             */

            $legacyType =
                $cashBox->type ===
                'cash'
                    ? 'efectivo'
                    : 'bancario';

            /*
             * =========================================================
             * 7. CREAR SESIÓN
             * =========================================================
             */

            $cashRegister =
                CashRegister::create([
                    /*
                     * BelongsToTenant también puede asignarlo
                     * automáticamente, pero lo dejamos explícito
                     * porque estamos construyendo una entidad
                     * financiera sensible.
                     */
                    'tenant_id' =>
                        $tenantId,

                    'company_id' =>
                        $companyId,

                    'branch_id' =>
                        $branchId,

                    'cash_box_id' =>
                        $cashBox->id,

                    'user_id' =>
                        $userId,

                    /*
                     * Legacy temporal.
                     */
                    'type' =>
                        $legacyType,

                    /*
                     * Saldos.
                     */
                    'opening_balance' =>
                        $openingBalance,

                    'current_balance' =>
                        $openingBalance,

                    'closing_balance' =>
                        0,

                    /*
                     * Acumuladores operativos.
                     *
                     * El opening_balance NO cuenta como income.
                     */
                    'total_sales' =>
                        0,

                    'total_incomes' =>
                        0,

                    'total_expenses' =>
                        0,

                    /*
                     * Sesión.
                     */
                    'opening_time' =>
                        Carbon::now(
                            'America/Lima'
                        ),

                    'closing_time' =>
                        null,

                    'status' =>
                        1,
                ]);

            DB::commit();

            return response()->json([
                'message' =>
                    'Sesión iniciada correctamente.',

                'cash_register_id' =>
                    $cashRegister->id,

                'cash_box_id' =>
                    $cashBox->id,

                'branch_id' =>
                    $branchId,

                'balance_total' =>
                    $cashRegister
                        ->current_balance,
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Validación fallida.',

                'errors' =>
                    $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    // GET /cash-registers/session/{cashBox}
    public function session(CashBox $cashBox)
    {
        /*
         * =========================================================
         * 1. CONTEXTO ACTUAL
         * =========================================================
         */

        $user =
            Auth::user();

        $userId =
            $user->id;

        $companyId =
            TenantContext::companyId();

        $branchId =
            TenantContext::branchId();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        /*
         * =========================================================
         * 2. VALIDAR CASHBOX EN EL CONTEXTO ACTUAL
         * =========================================================
         *
         * BelongsToTenant protege Tenant.
         *
         * Company:
         * debe coincidir con la Company activa.
         *
         * Branch:
         *
         * - cashBox.branch_id NULL:
         *   disponible para toda la Company.
         *
         * - cashBox.branch_id = branch actual:
         *   disponible para esta Branch.
         */

        $cashBox =
            CashBox::query()
                ->where(
                    'id',
                    $cashBox->id
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->where(
                    function ($query) use (
                        $branchId
                    ) {

                        $query
                            ->whereNull(
                                'branch_id'
                            )
                            ->orWhere(
                                'branch_id',
                                $branchId
                            );
                    }
                )
                ->firstOrFail();

        /*
         * =========================================================
         * 3. SESIÓN ABIERTA DEL USUARIO
         * =========================================================
         *
         * CRÍTICO:
         *
         * Una sesión pertenece a:
         *
         * Tenant
         * Company
         * Branch
         * CashBox
         * User
         */

        $cashRegister =
            CashRegister::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->where(
                    'cash_box_id',
                    $cashBox->id
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'status',
                    1
                )
                ->latest(
                    'id'
                )
                ->first();

        if (!$cashRegister) {

            return redirect()
                ->route(
                    'cashMovement.my.index'
                )
                ->with(
                    'warning',
                    'No tienes una sesión abierta en esa caja para la empresa y sucursal actual.'
                );
        }

        /*
         * =========================================================
         * 4. DATOS DE LA SESIÓN
         * =========================================================
         */

        $balance_total =
            $cashRegister
                ->current_balance;

        $active =
            $cashBox->name;

        $state =
            '<div class="col-md-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h4>Abierta</h4>
                </div>

                <div class="icon">
                    <i class="fas fa-lock-open" style="font-size: 40px"></i>
                </div>

                <a
                    href="#"
                    id="btn-arqueoCashRegister"
                    class="small-box-footer"
                >
                    Arqueo de Caja
                    <i class="fas fa-clipboard-check"></i>
                </a>
            </div>
        </div>';

        /*
         * =========================================================
         * 5. SUBTIPOS DISPONIBLES
         * =========================================================
         *
         * Regla CASH-03:
         *
         * cash_box_id = NULL
         * → disponible para todas las cajas de la Company.
         *
         * cash_box_id = ID actual
         * → disponible únicamente para esta caja.
         */

        $subtypes =
            collect();

        if (
            $cashBox->type === 'bank' &&
            (int) $cashBox->uses_subtypes === 1
        ) {

            $subtypes =
                CashBoxSubtype::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->availableForCashBox(
                        $cashBox->id
                    )
                    ->orderBy(
                        'position'
                    )
                    ->orderBy(
                        'name'
                    )
                    ->get();
        }

        /*
         * =========================================================
         * 6. VISTA
         * =========================================================
         */

        return view(
            'cashRegister.session',
            compact(
                'balance_total',
                'permissions',
                'active',
                'state',
                'cashRegister',
                'cashBox',
                'subtypes'
            )
        );
    }

    public function getSessionMovements(
        Request $request,
        CashRegister $cashRegister,
        $pageNumber = 1
    ) {
        /*
         * =========================================================
         * 1. CONTEXTO ACTUAL
         * =========================================================
         */

        $companyId =
            TenantContext::companyId();

        $branchId =
            TenantContext::branchId();

        $userId =
            Auth::id();

        /*
         * =========================================================
         * 2. VALIDAR CASHREGISTER EN CONTEXTO ACTUAL
         * =========================================================
         *
         * Aunque el route-model binding ya resolvió CashRegister,
         * volvemos a consultar explícitamente por:
         *
         * - Company
         * - Branch
         * - User
         *
         * BelongsToTenant protege Tenant.
         */

        $cashRegister =
            CashRegister::query()
                ->where(
                    'id',
                    $cashRegister->id
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->first();

        if (!$cashRegister) {

            return response()->json([
                'message' =>
                    'No autorizado para consultar esta sesión de caja.',
            ], 403);
        }

        /*
         * =========================================================
         * 3. SOLO SESIÓN ABIERTA
         * =========================================================
         */

        if (
            (int) $cashRegister->status !== 1
        ) {

            return response()->json([
                'message' =>
                    'La sesión ya está cerrada.',
            ], 422);
        }

        /*
         * =========================================================
         * 4. PAGINACIÓN
         * =========================================================
         */

        $perPage =
            10;

        $pageNumber =
            (int) $pageNumber;

        if (
            $pageNumber <= 0
        ) {
            $pageNumber = 1;
        }

        /*
         * =========================================================
         * 5. MOVIMIENTOS DE LA SESIÓN
         * =========================================================
         *
         * CashMovement también usa BelongsToTenant.
         *
         * Además filtramos explícitamente por Company/Branch
         * para evitar lecturas cruzadas dentro del mismo Tenant.
         */

        $query =
            CashMovement::query()
                ->with([
                    'cashBoxSubtype',
                ])
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->where(
                    'cash_register_id',
                    $cashRegister->id
                )
                ->orderBy(
                    'created_at',
                    'desc'
                )
                ->orderBy(
                    'id',
                    'desc'
                );

        $totalFilteredRecords =
            $query->count();

        $totalPages =
            (int) ceil(
                $totalFilteredRecords /
                $perPage
            );

        $startRecord =
            $totalFilteredRecords
                ? (
                ($pageNumber - 1)
                *
                $perPage
                +
                1
            )
                : 0;

        $endRecord =
            min(
                $totalFilteredRecords,
                $pageNumber *
                $perPage
            );

        $movements =
            $query
                ->skip(
                    ($pageNumber - 1)
                    *
                    $perPage
                )
                ->take(
                    $perPage
                )
                ->get();

        /*
         * =========================================================
         * 6. RESPONSE
         * =========================================================
         */

        $array = [];

        foreach (
            $movements
            as $movement
        ) {

            /*
             * Etiqueta visible.
             *
             * Conservamos por ahora la semántica legacy:
             *
             * regularize = 0
             * → pendiente
             *
             * regularize = 1
             * → confirmado
             */

            if (
                $movement->type ===
                'income'
            ) {

                $tipo =
                    (int) $movement->regularize === 0
                        ? 'Regularizar'
                        : 'Ingreso';

            } elseif (
                $movement->type ===
                'expense'
            ) {

                $tipo =
                    'Egreso';

            } elseif (
                $movement->type ===
                'sale'
                &&
                (int) $movement->regularize === 0
            ) {

                $tipo =
                    'Regularizar';

            } else {

                $tipo =
                    'Venta';
            }

            $array[] = [
                'id' =>
                    $movement->id,

                'type' =>
                    $tipo,

                /*
                 * Raw type / estado.
                 */
                'type_raw' =>
                    $movement->type,

                'regularize' =>
                    (int) $movement->regularize,

                'status_label' =>
                    (int) $movement->regularize === 1
                        ? 'Confirmado'
                        : 'Pendiente',

                /*
                 * Montos.
                 */
                'amount' =>
                    (string) $movement->amount,

                'amount_regularize' =>
                    $movement->amount_regularize !== null
                        ? (string)
                    $movement->amount_regularize
                        : null,

                'commission' =>
                    $movement->commission !== null
                        ? (string)
                    $movement->commission
                        : null,

                /*
                 * Subtipo.
                 */
                'subtype' =>
                    $movement->cashBoxSubtype
                        ? $movement
                        ->cashBoxSubtype
                        ->name
                        : null,

                'subtype_code' =>
                    $movement->cashBoxSubtype
                        ? $movement
                        ->cashBoxSubtype
                        ->code
                        : null,

                'subtype_is_deferred' =>
                    $movement->cashBoxSubtype
                        ? (bool)
                    $movement
                        ->cashBoxSubtype
                        ->is_deferred
                        : false,

                /*
                 * Auditoría / relaciones.
                 */
                'origin_id' =>
                    $movement
                        ->cash_movement_origin_id,

                'regularize_id' =>
                    $movement
                        ->cash_movement_regularize_id,

                'cash_settlement_id' =>
                    $movement
                        ->cash_settlement_id,

                /*
                 * Texto.
                 */
                'description' =>
                    $movement->description,

                'observation' =>
                    $movement->observation,

                /*
                 * Referencias.
                 */
                'sale_id' =>
                    $movement->sale_id,

                'entry_id' =>
                    $movement->entry_id,

                /*
                 * Fecha.
                 */
                'date' =>
                    $movement->created_at
                        ? $movement
                        ->created_at
                        ->format(
                            'd/m/Y h:i A'
                        )
                        : '',
            ];
        }

        /*
         * =========================================================
         * 7. PAGINACIÓN RESPONSE
         * =========================================================
         */

        $pagination = [
            'currentPage' =>
                $pageNumber,

            'totalPages' =>
                $totalPages,

            'startRecord' =>
                $startRecord,

            'endRecord' =>
                $endRecord,

            'totalRecords' =>
                $totalFilteredRecords,

            'totalFilteredRecords' =>
                $totalFilteredRecords,
        ];

        return response()->json([
            'data' =>
                $array,

            'pagination' =>
                $pagination,
        ]);
    }

    private function hasRecentDuplicateMovement(
        int $cashRegisterId,
        string $type,
        float $amount,
        string $observation,
        ?int $subtypeId = null,
        int $seconds = 3
    ): bool {
        $query = CashMovement::where('cash_register_id', $cashRegisterId)
            ->where('type', $type)
            ->where('amount', $amount)
            ->where('observation', $observation)
            ->where('created_at', '>=', now()->subSeconds($seconds));

        if ($subtypeId) {
            $query->where('cash_box_subtype_id', $subtypeId);
        } else {
            $query->whereNull('cash_box_subtype_id');
        }

        return $query->exists();
    }

    public function income(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
             * =========================================================
             * 1. CONTEXTO ACTUAL
             * =========================================================
             */

            $tenantId =
                TenantContext::tenantId();

            $companyId =
                TenantContext::companyId();

            $branchId =
                TenantContext::branchId();

            $userId =
                Auth::id();

            /*
             * =========================================================
             * 2. VALIDAR PAYLOAD
             * =========================================================
             */

            $data =
                $request->validate(
                    [
                        'cash_register_id' =>
                            'required|integer',

                        'amount' =>
                            'required|numeric|min:0.01',

                        'description' =>
                            'required|string|max:255',

                        'cash_box_subtype_id' =>
                            'nullable|integer',
                    ],
                    [
                        'cash_register_id.required' =>
                            'Debe seleccionar una caja.',

                        'cash_register_id.integer' =>
                            'La caja seleccionada no es válida.',

                        'amount.required' =>
                            'Debe ingresar un monto.',

                        'amount.numeric' =>
                            'El monto debe ser un valor numérico.',

                        'amount.min' =>
                            'El monto debe ser mayor a 0.',

                        'description.required' =>
                            'Debe ingresar una descripción del movimiento.',

                        'description.string' =>
                            'La descripción no es válida.',

                        'description.max' =>
                            'La descripción no debe superar los 255 caracteres.',

                        'cash_box_subtype_id.integer' =>
                            'El canal / subtipo seleccionado no es válido.',
                    ]
                );

            /*
             * =========================================================
             * 3. OBTENER CASHREGISTER DE FORMA SEGURA
             * =========================================================
             *
             * BelongsToTenant protege Tenant.
             *
             * Además exigimos:
             *
             * - Company actual
             * - Branch actual
             * - User actual
             * - sesión abierta
             */

            $cashRegister =
                CashRegister::query()
                    ->where(
                        'id',
                        (int) $data[
                        'cash_register_id'
                        ]
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'status',
                        1
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$cashRegister) {

                throw ValidationException::withMessages([
                    'cash_register_id' => [
                        'No existe una sesión abierta válida para la empresa y sucursal actual.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 4. VALIDAR CASHBOX
             * =========================================================
             */

            $cashBox =
                CashBox::query()
                    ->where(
                        'id',
                        $cashRegister
                            ->cash_box_id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->where(
                        function ($query) use (
                            $branchId
                        ) {

                            $query
                                ->whereNull(
                                    'branch_id'
                                )
                                ->orWhere(
                                    'branch_id',
                                    $branchId
                                );
                        }
                    )
                    ->first();

            if (!$cashBox) {

                throw ValidationException::withMessages([
                    'cash_register_id' => [
                        'La caja asociada a esta sesión ya no está disponible en el contexto actual.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 5. MONTO / DESCRIPCIÓN
             * =========================================================
             */

            $amount =
                round(
                    (float) $data[
                    'amount'
                    ],
                    2
                );

            $description =
                trim(
                    $data[
                    'description'
                    ]
                );

            $subtypeId =
                $data[
                'cash_box_subtype_id'
                ]
                ?? null;

            /*
             * Por defecto el movimiento impacta saldo inmediatamente.
             */
            $regularize = 1;

            /*
             * =========================================================
             * 6. VALIDAR SUBTIPO
             * =========================================================
             *
             * Solo las cajas bancarias que utilizan subtipos
             * pueden recibir cash_box_subtype_id.
             */

            if (
                $cashBox->type === 'bank' &&
                (int) $cashBox->uses_subtypes === 1
            ) {

                if (!$subtypeId) {

                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => [
                            'Debe seleccionar un subtipo bancario.'
                        ],
                    ]);
                }

                /*
                 * Debe pertenecer:
                 *
                 * - al Tenant actual por BelongsToTenant
                 * - a la Company actual
                 * - ser global o específico de esta CashBox
                 * - estar activo
                 */

                $subtype =
                    CashBoxSubtype::query()
                        ->where(
                            'id',
                            (int) $subtypeId
                        )
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->where(
                            'is_active',
                            1
                        )
                        ->availableForCashBox(
                            $cashBox->id
                        )
                        ->first();

                if (!$subtype) {

                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => [
                            'El subtipo seleccionado no está disponible para esta caja.'
                        ],
                    ]);
                }

                /*
                 * Diferido:
                 *
                 * POS u otro canal que todavía
                 * no representa dinero disponible.
                 */
                $regularize =
                    $subtype->is_deferred
                        ? 0
                        : 1;

                $subtypeId =
                    $subtype->id;

            } else {

                /*
                 * Efectivo / caja que no utiliza subtipos.
                 *
                 * Ignoramos cualquier ID enviado manualmente
                 * desde frontend.
                 */
                $subtypeId =
                    null;

                $regularize =
                    1;
            }

            /*
             * =========================================================
             * 7. PROTECCIÓN ANTI-DUPLICADO
             * =========================================================
             */

            $alreadyExists =
                $this->hasRecentDuplicateMovement(
                    (int) $cashRegister->id,
                    'income',
                    $amount,
                    $description,
                    $subtypeId
                        ? (int) $subtypeId
                        : null,
                    3
                );

            if ($alreadyExists) {

                throw ValidationException::withMessages([
                    'duplicate' => [
                        'Ya se registró un ingreso igual hace unos segundos. Espere un momento antes de intentar nuevamente.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 8. CREAR CASHMOVEMENT
             * =========================================================
             *
             * Este es un movimiento OPERATIVO,
             * por lo tanto sí pertenece a un CashRegister.
             */

            $cashMovement =
                CashMovement::create([
                    'tenant_id' =>
                        $tenantId,

                    'company_id' =>
                        $companyId,

                    'branch_id' =>
                        $branchId,

                    'cash_box_id' =>
                        $cashBox->id,

                    'cash_register_id' =>
                        $cashRegister->id,

                    'user_id' =>
                        $userId,

                    'cash_settlement_id' =>
                        null,

                    'type' =>
                        'income',

                    'amount' =>
                        $amount,

                    /*
                     * Tipo funcional del movimiento.
                     */
                    'description' =>
                        'Ingreso manual',

                    /*
                     * Texto ingresado por el usuario.
                     */
                    'observation' =>
                        $description,

                    'cash_box_subtype_id' =>
                        $subtypeId,

                    /*
                     * Semántica legacy:
                     *
                     * 1 = confirmado / disponible
                     * 0 = pendiente de regularización
                     */
                    'regularize' =>
                        $regularize,

                    'amount_regularize' =>
                        null,

                    'commission' =>
                        null,

                    'arqueo' =>
                        false,
                ]);

            /*
             * =========================================================
             * 9. ACTUALIZAR CASHREGISTER
             * =========================================================
             *
             * Solo los movimientos confirmados afectan el saldo.
             *
             * Los diferidos permanecen como pendientes.
             */

            if (
                $regularize === 1
            ) {

                $cashRegister->current_balance =
                    round(
                        (float)
                        $cashRegister
                            ->current_balance
                        +
                        $amount,
                        2
                    );

                $cashRegister->total_incomes =
                    round(
                        (float)
                        $cashRegister
                            ->total_incomes
                        +
                        $amount,
                        2
                    );

                $cashRegister->save();
            }

            /*
             * =========================================================
             * 10. CASHBOX
             * =========================================================
             *
             * Todavía NO actualizamos CashBox.current_balance.
             *
             * El dinero de la sesión se consolidará en CashBox
             * durante el CashSettlement.
             */

            DB::commit();

            return response()->json([
                'message' =>
                    $regularize === 1
                        ? 'Ingreso registrado con éxito.'
                        : 'Ingreso registrado como pendiente de regularización.',

                'cash_movement_id' =>
                    $cashMovement->id,

                'regularize' =>
                    $regularize,

                'balance_total' =>
                    $cashRegister
                        ->current_balance,
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Validación fallida.',

                'errors' =>
                    $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function expense(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
             * =========================================================
             * 1. CONTEXTO ACTUAL
             * =========================================================
             */

            $tenantId =
                TenantContext::tenantId();

            $companyId =
                TenantContext::companyId();

            $branchId =
                TenantContext::branchId();

            $userId =
                Auth::id();

            /*
             * =========================================================
             * 2. VALIDAR PAYLOAD
             * =========================================================
             */

            $data =
                $request->validate(
                    [
                        'cash_register_id' =>
                            'required|integer',

                        'amount' =>
                            'required|numeric|min:0.01',

                        'description' =>
                            'required|string|max:255',

                        'cash_box_subtype_id' =>
                            'nullable|integer',
                    ],
                    [
                        'cash_register_id.required' =>
                            'Debe seleccionar una caja abierta.',

                        'cash_register_id.integer' =>
                            'La caja seleccionada no es válida.',

                        'amount.required' =>
                            'Debe ingresar un monto.',

                        'amount.numeric' =>
                            'El monto debe ser un valor numérico.',

                        'amount.min' =>
                            'El monto debe ser mayor a 0.',

                        'description.required' =>
                            'Debe ingresar una descripción del movimiento.',

                        'description.string' =>
                            'La descripción no es válida.',

                        'description.max' =>
                            'La descripción no puede exceder los 255 caracteres.',

                        'cash_box_subtype_id.integer' =>
                            'El canal o subtipo seleccionado no es válido.',
                    ]
                );

            /*
             * =========================================================
             * 3. OBTENER CASHREGISTER DE FORMA SEGURA
             * =========================================================
             */

            $cashRegister =
                CashRegister::query()
                    ->where(
                        'id',
                        (int) $data[
                        'cash_register_id'
                        ]
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'status',
                        1
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$cashRegister) {

                throw ValidationException::withMessages([
                    'cash_register_id' => [
                        'No existe una sesión abierta válida para la empresa y sucursal actual.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 4. VALIDAR CASHBOX
             * =========================================================
             */

            $cashBox =
                CashBox::query()
                    ->where(
                        'id',
                        $cashRegister
                            ->cash_box_id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->where(
                        function ($query) use (
                            $branchId
                        ) {

                            $query
                                ->whereNull(
                                    'branch_id'
                                )
                                ->orWhere(
                                    'branch_id',
                                    $branchId
                                );
                        }
                    )
                    ->first();

            if (!$cashBox) {

                throw ValidationException::withMessages([
                    'cash_register_id' => [
                        'La caja asociada a esta sesión ya no está disponible en el contexto actual.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 5. MONTO / DESCRIPCIÓN
             * =========================================================
             */

            $amount =
                round(
                    (float) $data[
                    'amount'
                    ],
                    2
                );

            $description =
                trim(
                    $data[
                    'description'
                    ]
                );

            $subtypeId =
                $data[
                'cash_box_subtype_id'
                ]
                ?? null;

            /*
             * =========================================================
             * 6. VALIDAR SUBTIPO
             * =========================================================
             *
             * Para cajas bancarias que utilizan subtipos:
             *
             * el subtipo debe ser:
             *
             * - de la Company actual
             * - activo
             * - global o específico de esta CashBox
             *
             * IMPORTANTE:
             *
             * is_deferred NO cambia el comportamiento del egreso.
             *
             * Un egreso siempre se descuenta del saldo disponible
             * inmediatamente.
             */

            if (
                $cashBox->type === 'bank' &&
                (int) $cashBox->uses_subtypes === 1
            ) {

                if (!$subtypeId) {

                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => [
                            'Debe seleccionar un subtipo bancario.'
                        ],
                    ]);
                }

                $subtype =
                    CashBoxSubtype::query()
                        ->where(
                            'id',
                            (int) $subtypeId
                        )
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->where(
                            'is_active',
                            1
                        )
                        ->availableForCashBox(
                            $cashBox->id
                        )
                        ->first();

                if (!$subtype) {

                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => [
                            'El subtipo seleccionado no está disponible para esta caja.'
                        ],
                    ]);
                }

                $subtypeId =
                    $subtype->id;

            } else {

                /*
                 * Efectivo / caja que no utiliza subtipos.
                 */
                $subtypeId =
                    null;
            }

            /*
             * =========================================================
             * 7. VALIDAR SALDO DISPONIBLE
             * =========================================================
             */

            if (
                (float)
                $cashRegister
                    ->current_balance
                <
                $amount
            ) {

                throw ValidationException::withMessages([
                    'amount' => [
                        'No hay suficiente saldo en la caja para realizar este egreso.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 8. PROTECCIÓN ANTI-DUPLICADO
             * =========================================================
             */

            $alreadyExists =
                $this->hasRecentDuplicateMovement(
                    (int) $cashRegister->id,
                    'expense',
                    $amount,
                    $description,
                    $subtypeId
                        ? (int) $subtypeId
                        : null,
                    3
                );

            if ($alreadyExists) {

                throw ValidationException::withMessages([
                    'duplicate' => [
                        'Ya se registró un egreso igual hace unos segundos. Espere un momento antes de intentar nuevamente.'
                    ],
                ]);
            }

            /*
             * =========================================================
             * 9. CREAR CASHMOVEMENT
             * =========================================================
             */

            $cashMovement =
                CashMovement::create([
                    'tenant_id' =>
                        $tenantId,

                    'company_id' =>
                        $companyId,

                    'branch_id' =>
                        $branchId,

                    'cash_box_id' =>
                        $cashBox->id,

                    'cash_register_id' =>
                        $cashRegister->id,

                    'user_id' =>
                        $userId,

                    'cash_settlement_id' =>
                        null,

                    'type' =>
                        'expense',

                    'amount' =>
                        $amount,

                    'description' =>
                        'Egreso manual',

                    'observation' =>
                        $description,

                    'cash_box_subtype_id' =>
                        $subtypeId,

                    /*
                     * Un egreso siempre impacta inmediatamente.
                     */
                    'regularize' =>
                        1,

                    'amount_regularize' =>
                        null,

                    'commission' =>
                        null,

                    'arqueo' =>
                        false,
                ]);

            /*
             * =========================================================
             * 10. ACTUALIZAR CASHREGISTER
             * =========================================================
             */

            $cashRegister->current_balance =
                round(
                    (float)
                    $cashRegister
                        ->current_balance
                    -
                    $amount,
                    2
                );

            $cashRegister->total_expenses =
                round(
                    (float)
                    $cashRegister
                        ->total_expenses
                    +
                    $amount,
                    2
                );

            $cashRegister->save();

            /*
             * =========================================================
             * 11. CASHBOX
             * =========================================================
             *
             * Todavía NO modificamos CashBox.current_balance.
             *
             * Ese saldo se consolidará durante CashSettlement.
             */

            DB::commit();

            return response()->json([
                'message' =>
                    'Egreso registrado con éxito.',

                'cash_movement_id' =>
                    $cashMovement->id,

                'balance_total' =>
                    $cashRegister
                        ->current_balance,
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Validación fallida.',

                'errors' =>
                    $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function arqueo(
        Request $request,
        CashRegister $cashRegister
    ) {
        /*
         * =========================================================
         * 1. VALIDAR PAYLOAD
         * =========================================================
         */

        $data =
            $request->validate(
                [
                    'observation' =>
                        'nullable|string|max:1000',

                    'counted' =>
                        'nullable|numeric|min:0',
                ],
                [
                    'observation.string' =>
                        'La observación no es válida.',

                    'observation.max' =>
                        'La observación no puede superar los 1000 caracteres.',

                    'counted.numeric' =>
                        'El monto contado debe ser numérico.',

                    'counted.min' =>
                        'El monto contado no puede ser negativo.',
                ]
            );

        /*
         * =========================================================
         * 2. CONTEXTO ACTUAL
         * =========================================================
         */

        $tenantId =
            TenantContext::tenantId();

        $companyId =
            TenantContext::companyId();

        $branchId =
            TenantContext::branchId();

        $userId =
            Auth::id();

        DB::beginTransaction();

        try {

            /*
             * =====================================================
             * 3. BLOQUEAR Y VALIDAR CASHREGISTER
             * =====================================================
             *
             * CashRegister debe pertenecer exactamente a:
             *
             * - Tenant actual por BelongsToTenant
             * - Company actual
             * - Branch actual
             * - Usuario actual
             * - sesión abierta
             */

            $cashRegister =
                CashRegister::query()
                    ->where(
                        'id',
                        $cashRegister->id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$cashRegister) {

                throw ValidationException::withMessages([
                    'cash_register' => [
                        'No autorizado para arquear esta sesión.'
                    ],
                ]);
            }

            if (
                (int) $cashRegister->status !== 1
            ) {

                throw ValidationException::withMessages([
                    'cash_register' => [
                        'La sesión ya se encuentra cerrada.'
                    ],
                ]);
            }

            /*
             * =====================================================
             * 4. EVITAR DOBLE ARQUEO
             * =====================================================
             *
             * Además del UNIQUE en cash_settlements.cash_register_id,
             * hacemos la validación funcional antes.
             */

            $alreadySettled =
                CashSettlement::query()
                    ->where(
                        'cash_register_id',
                        $cashRegister->id
                    )
                    ->exists();

            if ($alreadySettled) {

                throw ValidationException::withMessages([
                    'cash_register' => [
                        'Esta sesión ya fue arqueada anteriormente.'
                    ],
                ]);
            }

            /*
             * =====================================================
             * 5. VALIDAR CASHBOX
             * =====================================================
             */

            $cashBox =
                CashBox::query()
                    ->where(
                        'id',
                        $cashRegister->cash_box_id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->where(
                        function ($query) use (
                            $branchId
                        ) {

                            $query
                                ->whereNull(
                                    'branch_id'
                                )
                                ->orWhere(
                                    'branch_id',
                                    $branchId
                                );
                        }
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$cashBox) {

                throw ValidationException::withMessages([
                    'cash_box' => [
                        'La caja asociada a esta sesión no está disponible en el contexto actual.'
                    ],
                ]);
            }

            /*
             * =====================================================
             * 6. MOVIMIENTOS DE LA SESIÓN
             * =====================================================
             */

            $movements =
                CashMovement::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->where(
                        'cash_box_id',
                        $cashBox->id
                    )
                    ->where(
                        'cash_register_id',
                        $cashRegister->id
                    )
                    ->lockForUpdate()
                    ->get();

            /*
             * Una sesión abierta no debería contener movimientos
             * pertenecientes previamente a otro arqueo.
             */
            $movementAlreadySettled =
                $movements->first(
                    function ($movement) {

                        return
                            !is_null(
                                $movement->cash_settlement_id
                            );
                    }
                );

            if ($movementAlreadySettled) {

                throw new \RuntimeException(
                    'La sesión contiene movimientos que ya pertenecen a un arqueo anterior.'
                );
            }

            /*
             * =====================================================
             * 7. CALCULAR ARQUEO
             * =====================================================
             */

            $expectedAmount =
                0.00;

            $confirmedAmount =
                0.00;

            $deferredAmount =
                0.00;

            $countedAmount =
                null;

            $differenceAmount =
                0.00;

            /*
             * -----------------------------------------------------
             * EFECTIVO
             * -----------------------------------------------------
             */

            if (
                $cashBox->type === 'cash'
            ) {

                /*
                 * Saldo teórico disponible de la sesión.
                 */
                $expectedAmount =
                    round(
                        (float)
                        $cashRegister
                            ->current_balance,
                        2
                    );

                /*
                 * Si no se envía conteo físico,
                 * asumimos que coincide con lo esperado.
                 */
                $countedAmount =
                    array_key_exists(
                        'counted',
                        $data
                    )
                    &&
                    $data['counted'] !== null
                        ? round(
                        (float)
                        $data['counted'],
                        2
                    )
                        : $expectedAmount;

                $differenceAmount =
                    round(
                        $countedAmount -
                        $expectedAmount,
                        2
                    );

                /*
                 * El CashBox maestro recibe lo realmente contado.
                 */
                $confirmedAmount =
                    $countedAmount;

                $deferredAmount =
                    0.00;


                /*
                 * -----------------------------------------------------
                 * BANCARIO / OTROS
                 * -----------------------------------------------------
                 */

            } else {

                /*
                 * current_balance ya contiene solamente
                 * movimientos que impactaron saldo.
                 *
                 * Es decir:
                 *
                 * ingresos/ventas confirmadas
                 * - egresos
                 *
                 * Los diferidos no fueron añadidos al saldo.
                 */
                $confirmedAmount =
                    round(
                        (float)
                        $cashRegister
                            ->current_balance,
                        2
                    );

                /*
                 * Pendientes de regularización.
                 *
                 * Solo sale/income incrementan disponibilidad futura.
                 */
                $deferredAmount =
                    round(
                        (float)
                        $movements
                            ->filter(
                                function ($movement) {

                                    return
                                        (int)
                                        $movement
                                            ->regularize
                                        === 0
                                        &&
                                        in_array(
                                            $movement->type,
                                            [
                                                'sale',
                                                'income',
                                            ],
                                            true
                                        );
                                }
                            )
                            ->sum(
                                'amount'
                            ),
                        2
                    );

                /*
                 * Total económico rendido.
                 *
                 * Ejemplo:
                 *
                 * BCP confirmado neto = 200
                 * POS diferido         = 100
                 *
                 * expected             = 300
                 */
                $expectedAmount =
                    round(
                        $confirmedAmount +
                        $deferredAmount,
                        2
                    );

                $countedAmount =
                    null;

                $differenceAmount =
                    0.00;
            }

            /*
             * =====================================================
             * 8. CREAR CASHSETTLEMENT
             * =====================================================
             */

            $settlement =
                CashSettlement::create([
                    'tenant_id' =>
                        $tenantId,

                    'company_id' =>
                        $companyId,

                    'branch_id' =>
                        $branchId,

                    'cash_box_id' =>
                        $cashBox->id,

                    'cash_register_id' =>
                        $cashRegister->id,

                    'user_id' =>
                        $userId,

                    'expected_amount' =>
                        $expectedAmount,

                    'confirmed_amount' =>
                        $confirmedAmount,

                    'deferred_amount' =>
                        $deferredAmount,

                    'counted_amount' =>
                        $countedAmount,

                    'difference_amount' =>
                        $differenceAmount,

                    'observation' =>
                        !empty(
                        $data['observation']
                        )
                            ? trim(
                            $data['observation']
                        )
                            : null,

                    'settled_at' =>
                        Carbon::now(
                            'America/Lima'
                        ),
                ]);

            /*
             * =====================================================
             * 9. ASOCIAR MOVIMIENTOS AL ARQUEO
             * =====================================================
             *
             * CRÍTICO:
             *
             * NO creamos nuevos CashMovement.
             *
             * Tampoco:
             *
             * - expense de arqueo
             * - income del owner
             * - mirrors diferidos
             *
             * Los movimientos originales continúan siendo
             * la fuente histórica.
             */

            if (
            $movements->isNotEmpty()
            ) {

                CashMovement::query()
                    ->whereIn(
                        'id',
                        $movements
                            ->pluck('id')
                            ->all()
                    )
                    ->update([
                        'cash_settlement_id' =>
                            $settlement->id,
                    ]);
            }

            /*
             * =====================================================
             * 10. CONSOLIDAR CASHBOX
             * =====================================================
             *
             * CashBox recibe únicamente dinero realmente disponible.
             *
             * Efectivo:
             * monto contado.
             *
             * Bancario:
             * saldo confirmado neto.
             *
             * Diferidos:
             * NO ingresan todavía.
             */

            $cashBox->current_balance =
                round(
                    (float)
                    $cashBox
                        ->current_balance
                    +
                    $confirmedAmount,
                    2
                );

            $cashBox->save();

            /*
             * =====================================================
             * 11. CERRAR CASHREGISTER
             * =====================================================
             *
             * closing_balance:
             * representa lo realmente consolidado/rendido.
             *
             * current_balance:
             * queda en cero porque la sesión ya terminó.
             */

            $cashRegister->closing_balance =
                $confirmedAmount;

            $cashRegister->current_balance =
                0;

            $cashRegister->closing_time =
                Carbon::now(
                    'America/Lima'
                );

            $cashRegister->status =
                0;

            $cashRegister->save();

            /*
             * =====================================================
             * 12. COMMIT
             * =====================================================
             */

            DB::commit();

            return response()->json([
                'message' =>
                    'Arqueo realizado y sesión cerrada correctamente.',

                'cash_register_id' =>
                    $cashRegister->id,

                'cash_settlement_id' =>
                    $settlement->id,

                'cash_box_id' =>
                    $cashBox->id,

                'cash_box_balance' =>
                    $cashBox
                        ->current_balance,

                'expected_amount' =>
                    $settlement
                        ->expected_amount,

                'confirmed_amount' =>
                    $settlement
                        ->confirmed_amount,

                'deferred_amount' =>
                    $settlement
                        ->deferred_amount,

                'difference_amount' =>
                    $settlement
                        ->difference_amount,
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Validación fallida.',

                'errors' =>
                    $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function regularize(Request $request)
    {
        $data = $request->validate(
            [
                'cash_movement_id' => 'required|integer|exists:cash_movements,id',
                'amount_regularize' => 'required|numeric|min:0.01',
                'observation' => 'nullable|string|max:1000',
            ],
            [
                // cash_movement_id
                'cash_movement_id.required' => 'No se envió el movimiento a regularizar.',
                'cash_movement_id.integer'  => 'El identificador del movimiento no es válido.',
                'cash_movement_id.exists'   => 'El movimiento seleccionado no existe o ya fue eliminado.',

                // amount_regularize
                'amount_regularize.required' => 'Debe ingresar el monto neto recibido.',
                'amount_regularize.numeric'  => 'El monto neto debe ser un número válido.',
                'amount_regularize.min'      => 'El monto neto debe ser mayor a 0.',

                // observation
                'observation.string' => 'La observación contiene caracteres inválidos.',
                'observation.max'    => 'La observación no puede superar los 1000 caracteres.',
            ],
            [
                // nombres amigables (MUY importante para toastr)
                'cash_movement_id'  => 'movimiento',
                'amount_regularize' => 'monto neto',
                'observation'       => 'observación',
            ]
        );

        // ✅ Solo admin/owner (según tu regla)
        // Si prefieres permiso en vez de owner, reemplaza este bloque.
        if ((int)Auth::user()->owner !== 1) {
            return response()->json(['message' => 'No autorizado para regularizar.'], 403);
        }

        DB::beginTransaction();
        try {
            // Bloquear movimiento central
            $movement = CashMovement::lockForUpdate()->findOrFail($data['cash_movement_id']);

            // Debe estar pendiente
            if ((int)$movement->regularize === 1) {
                return response()->json(['message' => 'Este movimiento ya está regularizado.'], 422);
            }

            // Debe ser sale o income
            if (!in_array($movement->type, ['sale', 'income'], true)) {
                return response()->json(['message' => 'Solo se pueden regularizar movimientos de tipo venta o ingreso.'], 422);
            }

            // Debe tener subtipo
            if (!$movement->cash_box_subtype_id) {
                return response()->json(['message' => 'Este movimiento no tiene subtipo bancario.'], 422);
            }

            $subtype = CashBoxSubtype::findOrFail($movement->cash_box_subtype_id);

            // Debe ser diferido
            if (!$subtype->is_deferred) {
                return response()->json(['message' => 'Este subtipo no requiere regularización.'], 422);
            }

            $amount = (float)$movement->amount;
            $amountReg = (float)$data['amount_regularize'];

            if ($amountReg > $amount) {
                return response()->json(['message' => 'El monto regularizado no puede ser mayor al monto original.'], 422);
            }

            // Calcular comisión
            $commission = $amount - $amountReg;

            // Si no requiere comisión, forzar valores estándar
            if (!$subtype->requires_commission) {
                $amountReg = $amount;
                $commission = 0;
            }

            // Obtener cashRegister central del movimiento
            $centralRegister = CashRegister::lockForUpdate()->findOrFail($movement->cash_register_id);

            // Validación extra: central debe estar abierto
            if ((int)$centralRegister->status !== 1) {
                return response()->json(['message' => 'La sesión central está cerrada.'], 422);
            }

            // ✅ Actualizar movimiento central
            $obs = $movement->observation ? trim($movement->observation) : '';
            if (!empty($data['observation'])) {
                $extra = trim($data['observation']);
                $obs = $obs ? ($obs . ' | ' . $extra) : $extra;
            }
            $obs = $obs ?: null;

            $movement->amount_regularize = $amountReg;
            $movement->commission = $commission;
            $movement->regularize = 1;
            $movement->observation = $obs;
            $movement->save();

            // ✅ Actualizar balance central (solo ahora que se confirma)
            $centralRegister->current_balance = (float)$centralRegister->current_balance + $amountReg;
            $centralRegister->total_incomes   = (float)$centralRegister->total_incomes + $amountReg;
            $centralRegister->save();

            // ✅ Actualizar origen del cajero si existe
            if ($movement->cash_movement_origin_id) {
                $origin = CashMovement::lockForUpdate()->find($movement->cash_movement_origin_id);
                if ($origin) {
                    $origin->amount_regularize = $amountReg;
                    $origin->commission = $commission;
                    $origin->regularize = 1;

                    // vínculo al regularizador (central)
                    $origin->cash_movement_regularize_id = $movement->id;

                    // observation trazable
                    $oobs = $origin->observation ? trim($origin->observation) : '';
                    $oExtra = "Regularizado en central. ID: {$movement->id}. Neto: {$amountReg}. Comisión: {$commission}.";
                    $origin->observation = $oobs ? ($oobs . ' | ' . $oExtra) : $oExtra;

                    $origin->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Regularización aplicada correctamente.',
                'balance_total' => $centralRegister->current_balance,
                'movement_id' => $movement->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function financialSummary(Request $request)
    {
        // 1) Usuario "central" (owner=true)
        $ownerUser = User::where('owner', 1)->firstOrFail();

        // 2) CashBoxes activos (pueden ser varios bancos)
        $cashBoxes = CashBox::where('is_active', 1)
            ->orderBy('position')
            ->get(['id', 'name', 'type', 'uses_subtypes']);

        // 3) CashRegisters "centrales": últimas sesiones ABIERTAS del owner, 1 por cash_box_id
        //    (blindaje por si existieran duplicados abiertos por error)
        $sub = DB::table('cash_registers')
            ->selectRaw('MAX(id) as id')
            ->where('user_id', $ownerUser->id)
            ->where('status', 1) // abierta
            ->groupBy('cash_box_id');

        $registers = CashRegister::query()
            ->joinSub($sub, 'cr2', function ($join) {
                $join->on('cr2.id', '=', 'cash_registers.id');
            })
            ->select('cash_registers.*')
            ->get()
            ->keyBy('cash_box_id');

        $registerIds = $registers->pluck('id')->values()->all();

        // 4) Comisiones CONFIRMADAS (regularize=1)
        $commissionsConfirmed = 0.0;
        if (!empty($registerIds)) {
            $commissionsConfirmed = (float) CashMovement::whereIn('cash_register_id', $registerIds)
                ->where('regularize', 1)
                ->whereNotNull('commission')
                ->where('commission', '>', 0)
                ->sum('commission');
        }

        // 5) Pendientes por regularizar (solo bancarios, regularize=0)
        $pendingByRegisterId = [];
        if (!empty($registerIds)) {
            $pendingByRegisterId = CashMovement::selectRaw('cash_register_id, COALESCE(SUM(amount),0) as pending')
                ->whereIn('cash_register_id', $registerIds)
                ->where('regularize', 0)
                ->whereIn('type', ['sale', 'income'])
                ->groupBy('cash_register_id')
                ->pluck('pending', 'cash_register_id')
                ->toArray();
        }

        $pendingTotal = 0.0;

        if (!empty($pendingByRegisterId)) {
            foreach ($pendingByRegisterId as $p) {
                $pendingTotal += (float)$p;
            }
        }

        // 6) Armar filas por cada CashBox
        $rows = [];
        $totalBalances = 0.0;

        foreach ($cashBoxes as $box) {
            $reg = $registers->get($box->id);

            $balance = $reg ? (float) $reg->current_balance : 0.0;
            $totalBalances += $balance;

            $pending = 0.0;
            if ($box->type === 'bank' && $reg) {
                $pending = (float)($pendingByRegisterId[$reg->id] ?? 0.0);
            }

            $rows[] = [
                'label'         => $box->name, // ✅ aquí
                'cash_box_id'   => $box->id,
                'cash_box_name' => $box->name,
                'cash_box_type' => $box->type, // cash | bank | other
                'balance'       => $balance,
                'pending'       => $pending,
                'has_register'  => (bool) $reg,
                'url_admin'     => route('cashRegister.session', $box->id)
            ];
        }

        $grandTotal = $totalBalances + $commissionsConfirmed + $pendingTotal;

        // Links (ajústalos a tus rutas reales)
        $urlDiferidos     = url('/dashboard/cash-movements/deferred');  // ejemplo

        return view('cashRegister.summary', compact(
            'ownerUser',
            'rows',
            'commissionsConfirmed',
            'totalBalances',
            'grandTotal',
            'urlDiferidos',
            'pendingTotal'
        ));
    }
}
