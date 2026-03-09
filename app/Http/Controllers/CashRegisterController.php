<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
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
        $data = $request->validate(
            [
                'cash_box_id'      => 'required|exists:cash_boxes,id',
                'opening_balance'  => 'nullable|numeric|min:0',
            ],
            [
                // cash_box_id
                'cash_box_id.required' => 'Debe seleccionar una caja.',
                'cash_box_id.exists'  => 'La caja seleccionada no es válida o no existe.',

                // opening_balance
                'opening_balance.numeric' => 'El saldo inicial debe ser un valor numérico.',
                'opening_balance.min'     => 'El saldo inicial no puede ser negativo.',
            ],
            [
                // Nombres amigables (opcional pero recomendado)
                'cash_box_id'     => 'caja',
                'opening_balance' => 'saldo inicial',
            ]
        );

        $userId = auth()->id();
        $cashBox = CashBox::findOrFail($data['cash_box_id']);

        // Si ya existe una sesión abierta para ese usuario y esa caja, devolverla (idempotente)
        $existing = CashRegister::where('user_id', $userId)
            ->where('cash_box_id', $cashBox->id)
            ->where('status', 1)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Ya tienes una sesión abierta en esta caja.',
                'cash_register_id' => $existing->id,
                'cash_box_id' => $cashBox->id,
            ]);
        }

        // opening_balance: solo efectivo normalmente
        $openingBalance = 0.00;
        if ($cashBox->type === 'cash') {
            $openingBalance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0.00;
        }

        $now = Carbon::now();

        // Compatibilidad con tu campo viejo type (opcional pero útil mientras conviven)
        $legacyType = ($cashBox->type === 'cash') ? 'efectivo' : 'bancario';

        $cr = CashRegister::create([
            'cash_box_id' => $cashBox->id,
            'user_id' => $userId,

            'type' => $legacyType, // opcional

            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,

            'closing_balance' => 0,
            'total_sales' => 0,
            'total_incomes' => 0,
            'total_expenses' => 0,

            'opening_time' => $now,
            'closing_time' => null,
            'status' => 1,
        ]);

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'cash_register_id' => $cr->id,
            'cash_box_id' => $cashBox->id,
        ]);
    }

    // GET /cash-registers/session/{cashBox}
    public function session(CashBox $cashBox)
    {
        $user = Auth::user();
        $userId = $user->id;

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $cashRegister = CashRegister::where('cash_box_id', $cashBox->id)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->latest()
            ->first();

        if (!$cashRegister) {
            return redirect()->route('cashMovement.my.index')
                ->with('warning', 'No tienes una sesión abierta en esa caja.');
        }

        $balance_total = $cashRegister->current_balance;
        $active = $cashBox->name;

        $state = '<div class="col-md-4 col-6">
                <div class="small-box bg-success">
                  <div class="inner"><h4>Abierta</h4></div>
                  <div class="icon"><i class="fas fa-lock-open" style="font-size: 40px"></i></div>
                  <a href="#" id="btn-arqueoCashRegister" class="small-box-footer">
                    Arqueo de Caja <i class="fas fa-clipboard-check"></i>
                  </a>
                </div>
              </div>';

        // ✅ Subtypes para bancario (si usa subtypes)
        $subtypes = collect();
        if ($cashBox->type === 'bank' && $cashBox->uses_subtypes) {
            // Por tu regla actual: usas globales (cash_box_id null)
            $subtypes = CashBoxSubtype::whereNull('cash_box_id')
                ->where('is_active', 1)
                ->orderBy('position')
                ->orderBy('name')
                ->get();
        }

        return view('cashRegister.session', compact(
            'balance_total',
            'permissions',
            'active',
            'state',
            'cashRegister',
            'cashBox',
            'subtypes'
        ));
    }

    public function getSessionMovements(Request $request, CashRegister $cashRegister, $pageNumber = 1)
    {
        // Seguridad: solo dueño de la sesión o admin (si luego lo habilitas)
        if ($cashRegister->user_id != Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Solo sesiones abiertas (porque esta vista es sesión actual)
        if ((int)$cashRegister->status !== 1) {
            return response()->json(['message' => 'La sesión ya está cerrada.'], 422);
        }

        $perPage = 10;
        $pageNumber = (int)$pageNumber;
        if ($pageNumber <= 0) $pageNumber = 1;

        $query = CashMovement::with('cashBoxSubtype')
            ->where('cash_register_id', $cashRegister->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $totalFilteredRecords = $query->count();
        $totalPages = (int) ceil($totalFilteredRecords / $perPage);

        $startRecord = $totalFilteredRecords ? (($pageNumber - 1) * $perPage + 1) : 0;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $movements = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ($movements as $movement) {

            // Etiqueta de tipo (igual que tu lógica)
            if ($movement->type === 'income') {
                $tipo = ((int)$movement->regularize === 0) ? 'Regularizar' : 'Ingreso';
            } elseif ($movement->type === 'expense') {
                $tipo = 'Egreso';
            } elseif ($movement->type === 'sale' && (int)$movement->regularize === 0) {
                $tipo = 'Regularizar';
            } else {
                $tipo = 'Venta';
            }

            $array[] = [
                'id' => $movement->id,
                'type' => $tipo,

                // nuevo: raw type + estado
                'type_raw' => $movement->type, // sale|income|expense
                'regularize' => (int)$movement->regularize,
                'status_label' => ((int)$movement->regularize === 1) ? 'Confirmado' : 'Pendiente',

                // montos
                'amount' => (string) $movement->amount,
                'amount_regularize' => $movement->amount_regularize !== null ? (string)$movement->amount_regularize : null,
                'commission' => $movement->commission !== null ? (string)$movement->commission : null,

                // subtipo
                'subtype' => $movement->cashBoxSubtype ? $movement->cashBoxSubtype->name : null,
                'subtype_code' => $movement->cashBoxSubtype ? $movement->cashBoxSubtype->code : null,

                // auditoría
                'origin_id' => $movement->cash_movement_origin_id,
                'regularize_id' => $movement->cash_movement_regularize_id,

                // texto
                'description' => $movement->description,
                'observation' => $movement->observation,

                'date' => $movement->created_at ? $movement->created_at->format('d/m/Y h:i A') : '',
                'sale_id' => $movement->sale_id,
                'subtype_is_deferred' => $movement->cashBoxSubtype ? (bool)$movement->cashBoxSubtype->is_deferred : false,

            ];
        }

        $pagination = [
            'currentPage' => $pageNumber,
            'totalPages' => $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return response()->json(['data' => $array, 'pagination' => $pagination]);
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

    public function incomeO(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate(
                [
                    'cash_register_id'     => 'required|integer|exists:cash_registers,id',
                    'amount'               => 'required|numeric|min:0.01',
                    'description'          => 'required|string|max:255',
                    'cash_box_subtype_id'  => 'nullable|exists:cash_box_subtypes,id',
                ],
                [
                    // cash_register_id
                    'cash_register_id.required' => 'Debe seleccionar una caja.',
                    'cash_register_id.integer'  => 'La caja seleccionada no es válida.',
                    'cash_register_id.exists'   => 'La caja seleccionada no existe o no está activa.',

                    // amount
                    'amount.required' => 'Debe ingresar un monto.',
                    'amount.numeric'  => 'El monto debe ser un valor numérico.',
                    'amount.min'      => 'El monto debe ser mayor a 0.',

                    // description
                    'description.required' => 'Debe ingresar una descripción del movimiento.',
                    'description.string'   => 'La descripción no es válida.',
                    'description.max'      => 'La descripción no debe superar los 255 caracteres.',

                    // subtype
                    'cash_box_subtype_id.exists' => 'El canal / subtipo seleccionado no es válido.',
                ]
            );

            /** @var CashRegister $cashRegister */
            $cashRegister = CashRegister::lockForUpdate()->findOrFail($data['cash_register_id']);

            // Seguridad: solo dueño de la sesión
            if ((int)$cashRegister->user_id !== (int)Auth::id()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            // Debe estar abierta
            if ((int)$cashRegister->status !== 1) {
                return response()->json(['message' => 'No se puede hacer un ingreso a una caja cerrada.'], 422);
            }

            $cashBox = $cashRegister->cashBox; // relación cashBox()
            if (!$cashBox) {
                return response()->json(['message' => 'La sesión no tiene caja asociada.'], 422);
            }

            $amount = (float)$data['amount'];
            $subtypeId = $data['cash_box_subtype_id'] ?? null;

            // ===== Reglas por tipo de caja =====
            $regularize = 1; // default: confirmado

            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {

                // En bancario con subtypes: subtype es obligatorio
                if (!$subtypeId) {
                    return response()->json(['message' => 'Debe seleccionar un subtipo (Yape/Plin/POS/Transferencia).'], 422);
                }

                $subtype = CashBoxSubtype::findOrFail($subtypeId);

                // regularize según BD (is_deferred)
                $regularize = $subtype->is_deferred ? 0 : 1;

            } else {
                // En efectivo o bancario sin subtypes: no debe venir subtype
                $subtypeId = null;
                $regularize = 1;
            }

            // 1) Crear movimiento
            CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'type' => 'income',
                'amount' => $amount,

                // sugerencia: description corto y observation el texto libre
                'description' => 'Ingreso manual',
                'observation' => $data['description'],

                'cash_box_subtype_id' => $subtypeId,
                'regularize' => $regularize,
            ]);

            // 2) Actualizar caja (solo si confirmado)
            if ($regularize == 1) {
                $cashRegister->current_balance = (float)$cashRegister->current_balance + $amount;
                $cashRegister->total_incomes   = (float)$cashRegister->total_incomes + $amount;
                $cashRegister->save();
            }

            DB::commit();

            return response()->json([
                'message' => $regularize == 1
                    ? 'Ingreso registrado con éxito.'
                    : 'Ingreso registrado como pendiente (diferido).',
                'balance_total' => $cashRegister->current_balance,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Validación fallida.',
                'errors'  => $e->errors(), // ✅ aquí vienen tus mensajes personalizados
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
    public function income(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate(
                [
                    'cash_register_id'     => 'required|integer|exists:cash_registers,id',
                    'amount'               => 'required|numeric|min:0.01',
                    'description'          => 'required|string|max:255',
                    'cash_box_subtype_id'  => 'nullable|exists:cash_box_subtypes,id',
                ],
                [
                    'cash_register_id.required' => 'Debe seleccionar una caja.',
                    'cash_register_id.integer'  => 'La caja seleccionada no es válida.',
                    'cash_register_id.exists'   => 'La caja seleccionada no existe o no está activa.',

                    'amount.required' => 'Debe ingresar un monto.',
                    'amount.numeric'  => 'El monto debe ser un valor numérico.',
                    'amount.min'      => 'El monto debe ser mayor a 0.',

                    'description.required' => 'Debe ingresar una descripción del movimiento.',
                    'description.string'   => 'La descripción no es válida.',
                    'description.max'      => 'La descripción no debe superar los 255 caracteres.',

                    'cash_box_subtype_id.exists' => 'El canal / subtipo seleccionado no es válido.',
                ]
            );

            /** @var CashRegister $cashRegister */
            $cashRegister = CashRegister::lockForUpdate()->findOrFail($data['cash_register_id']);

            if ((int)$cashRegister->user_id !== (int)Auth::id()) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['No autorizado para operar esta caja.']
                ]);
            }

            if ((int)$cashRegister->status !== 1) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['No se puede hacer un ingreso a una caja cerrada.']
                ]);
            }

            $cashBox = $cashRegister->cashBox;
            if (!$cashBox) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['La sesión no tiene caja asociada.']
                ]);
            }

            $amount = round((float)$data['amount'], 2);
            $description = trim($data['description']);
            $subtypeId = $data['cash_box_subtype_id'] ?? null;
            $regularize = 1;

            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                if (!$subtypeId) {
                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => ['Debe seleccionar un subtipo (Yape/Plin/POS/Transferencia).']
                    ]);
                }

                $subtype = CashBoxSubtype::findOrFail($subtypeId);
                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                $subtypeId = null;
                $regularize = 1;
            }

            // Protección anti-duplicado
            $alreadyExists = $this->hasRecentDuplicateMovement(
                $cashRegister->id,
                'income',
                $amount,
                $description,
                $subtypeId,
                3
            );

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'duplicate' => ['Ya se registró un ingreso igual hace unos segundos. Espere un momento antes de intentar nuevamente.']
                ]);
            }

            CashMovement::create([
                'cash_register_id'     => $cashRegister->id,
                'type'                 => 'income',
                'amount'               => $amount,
                'description'          => 'Ingreso manual',
                'observation'          => $description,
                'cash_box_subtype_id'  => $subtypeId,
                'regularize'           => $regularize,
            ]);

            if ($regularize == 1) {
                $cashRegister->current_balance = (float)$cashRegister->current_balance + $amount;
                $cashRegister->total_incomes   = (float)$cashRegister->total_incomes + $amount;
                $cashRegister->save();
            }

            DB::commit();

            return response()->json([
                'message' => $regularize == 1
                    ? 'Ingreso registrado con éxito.'
                    : 'Ingreso registrado como pendiente (diferido).',
                'balance_total' => $cashRegister->current_balance,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Validación fallida.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al registrar el ingreso.',
                //'error' => $e->getMessage(), // solo si estás en desarrollo
            ], 500);
        }
    }

    public function expenseO(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate(
                [
                    'cash_register_id'     => 'required|integer|exists:cash_registers,id',
                    'amount'               => 'required|numeric|min:0.01',
                    'description'          => 'required|string|max:255',
                    'cash_box_subtype_id'  => 'nullable|exists:cash_box_subtypes,id',
                ],
                [
                    // cash_register_id
                    'cash_register_id.required' => 'Debe seleccionar una caja abierta.',
                    'cash_register_id.integer'  => 'La caja seleccionada no es válida.',
                    'cash_register_id.exists'   => 'La caja seleccionada no existe o fue cerrada.',

                    // amount
                    'amount.required' => 'Debe ingresar un monto.',
                    'amount.numeric'  => 'El monto debe ser un valor numérico.',
                    'amount.min'      => 'El monto debe ser mayor a 0.',

                    // description
                    'description.required' => 'Debe ingresar una descripción del movimiento.',
                    'description.string'   => 'La descripción no es válida.',
                    'description.max'      => 'La descripción no puede exceder los 255 caracteres.',

                    // cash_box_subtype_id
                    'cash_box_subtype_id.exists' => 'El canal o subtipo seleccionado no es válido.',
                ]
            );

            /** @var CashRegister $cashRegister */
            $cashRegister = CashRegister::lockForUpdate()->findOrFail($data['cash_register_id']);

            // Seguridad: solo dueño de la sesión
            if ((int)$cashRegister->user_id !== (int)Auth::id()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            // Debe estar abierta
            if ((int)$cashRegister->status !== 1) {
                return response()->json(['message' => 'No se puede hacer un egreso de una caja cerrada.'], 422);
            }

            $cashBox = $cashRegister->cashBox;
            if (!$cashBox) {
                return response()->json(['message' => 'La sesión no tiene caja asociada.'], 422);
            }

            $amount = (float)$data['amount'];
            $subtypeId = $data['cash_box_subtype_id'] ?? null;

            // ===== Reglas por tipo de caja =====
            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                // Bancario con subtypes: subtype obligatorio
                if (!$subtypeId) {
                    return response()->json(['message' => 'Debe seleccionar un subtipo (Yape/Plin/POS/Transferencia).'], 422);
                }

                // Validar que exista (ya validado por request), si quieres usar info del subtype:
                // $subtype = CashBoxSubtype::findOrFail($subtypeId);

            } else {
                // Efectivo o bancario sin subtypes: subtype NO aplica
                $subtypeId = null;
            }

            // Validar saldo suficiente (recomendación: siempre)
            if ((float)$cashRegister->current_balance < $amount) {
                return response()->json(['message' => 'No hay suficiente saldo en la caja para realizar este egreso.'], 422);
            }

            // 1) Crear movimiento
            CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'type' => 'expense',
                'amount' => $amount,

                // recomendado: etiquetas estándar
                'description' => 'Egreso manual',
                'observation' => $data['description'],

                'regularize' => 1, // egreso manual siempre confirmado
                'cash_box_subtype_id' => $subtypeId,
            ]);

            // 2) Actualizar caja
            $cashRegister->current_balance = (float)$cashRegister->current_balance - $amount;
            $cashRegister->total_expenses  = (float)$cashRegister->total_expenses + $amount;
            $cashRegister->save();

            DB::commit();

            return response()->json([
                'message' => 'Egreso registrado con éxito.',
                'balance_total' => $cashRegister->current_balance,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Validación fallida.',
                'errors'  => $e->errors(), // ✅ aquí vienen tus mensajes personalizados
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
    public function expense(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate(
                [
                    'cash_register_id'     => 'required|integer|exists:cash_registers,id',
                    'amount'               => 'required|numeric|min:0.01',
                    'description'          => 'required|string|max:255',
                    'cash_box_subtype_id'  => 'nullable|exists:cash_box_subtypes,id',
                ],
                [
                    'cash_register_id.required' => 'Debe seleccionar una caja abierta.',
                    'cash_register_id.integer'  => 'La caja seleccionada no es válida.',
                    'cash_register_id.exists'   => 'La caja seleccionada no existe o fue cerrada.',

                    'amount.required' => 'Debe ingresar un monto.',
                    'amount.numeric'  => 'El monto debe ser un valor numérico.',
                    'amount.min'      => 'El monto debe ser mayor a 0.',

                    'description.required' => 'Debe ingresar una descripción del movimiento.',
                    'description.string'   => 'La descripción no es válida.',
                    'description.max'      => 'La descripción no puede exceder los 255 caracteres.',

                    'cash_box_subtype_id.exists' => 'El canal o subtipo seleccionado no es válido.',
                ]
            );

            /** @var CashRegister $cashRegister */
            $cashRegister = CashRegister::lockForUpdate()->findOrFail($data['cash_register_id']);

            if ((int)$cashRegister->user_id !== (int)Auth::id()) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['No autorizado para operar esta caja.']
                ]);
            }

            if ((int)$cashRegister->status !== 1) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['No se puede hacer un egreso de una caja cerrada.']
                ]);
            }

            $cashBox = $cashRegister->cashBox;
            if (!$cashBox) {
                throw ValidationException::withMessages([
                    'cash_register_id' => ['La sesión no tiene caja asociada.']
                ]);
            }

            $amount = round((float)$data['amount'], 2);
            $description = trim($data['description']);
            $subtypeId = $data['cash_box_subtype_id'] ?? null;

            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                if (!$subtypeId) {
                    throw ValidationException::withMessages([
                        'cash_box_subtype_id' => ['Debe seleccionar un subtipo (Yape/Plin/POS/Transferencia).']
                    ]);
                }
            } else {
                $subtypeId = null;
            }

            if ((float)$cashRegister->current_balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['No hay suficiente saldo en la caja para realizar este egreso.']
                ]);
            }

            // Protección anti-duplicado
            $alreadyExists = $this->hasRecentDuplicateMovement(
                $cashRegister->id,
                'expense',
                $amount,
                $description,
                $subtypeId,
                3
            );

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'duplicate' => ['Ya se registró un egreso igual hace unos segundos. Espere un momento antes de intentar nuevamente.']
                ]);
            }

            CashMovement::create([
                'cash_register_id'     => $cashRegister->id,
                'type'                 => 'expense',
                'amount'               => $amount,
                'description'          => 'Egreso manual',
                'observation'          => $description,
                'regularize'           => 1,
                'cash_box_subtype_id'  => $subtypeId,
            ]);

            $cashRegister->current_balance = (float)$cashRegister->current_balance - $amount;
            $cashRegister->total_expenses  = (float)$cashRegister->total_expenses + $amount;
            $cashRegister->save();

            DB::commit();

            return response()->json([
                'message' => 'Egreso registrado con éxito.',
                'balance_total' => $cashRegister->current_balance,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Validación fallida.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al registrar el egreso.',
                //'error' => $e->getMessage(), // solo si estás en desarrollo
            ], 500);
        }
    }

    public function arqueo(Request $request, CashRegister $cashRegister)
    {
        $request->validate([
            'observation' => 'nullable|string|max:1000',
            'counted' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        // ✅ Regla: owner no puede arquear
        if ((int)$user->owner === 1) {
            return response()->json([
                'message' => 'No se puede realizar arqueo en una sesión central (owner).'
            ], 422);
        }

        // ✅ Seguridad: solo el dueño de la sesión
        if ((int)$cashRegister->user_id !== (int)$user->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // ✅ Debe estar abierta
        if ((int)$cashRegister->status !== 1) {
            return response()->json(['message' => 'La sesión ya está cerrada.'], 422);
        }

        DB::beginTransaction();
        try {
            // Bloquear sesión del cajero
            $cashRegister = CashRegister::lockForUpdate()->findOrFail($cashRegister->id);

            // Cargar CashBox (asumiendo relación cashBox() en CashRegister)
            $cashBox = CashBox::findOrFail($cashRegister->cash_box_id);

            // Obtener usuario owner (central)
            $ownerUser = User::where('owner', 1)->first();
            if (!$ownerUser) {
                return response()->json(['message' => 'No existe un usuario central (owner=1).'], 422);
            }

            // Obtener/crear sesión central abierta para este CashBox
            $central = CashRegister::where('user_id', $ownerUser->id)
                ->where('cash_box_id', $cashBox->id)
                ->where('status', 1)
                ->lockForUpdate()
                ->first();

            if (!$central) {
                // Crear sesión central (se supone que no se cierra)
                $central = CashRegister::create([
                    'cash_box_id' => $cashBox->id,
                    'user_id' => $ownerUser->id,
                    'type' => ($cashBox->type === 'cash') ? 'efectivo' : 'bancario', // compat
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'closing_balance' => 0,
                    'total_sales' => 0,
                    'total_incomes' => 0,
                    'total_expenses' => 0,
                    'opening_time' => now(),
                    'closing_time' => null,
                    'status' => 1,
                ]);

                // Lock recién creado
                $central = CashRegister::where('id', $central->id)->lockForUpdate()->first();
            }

            // ============ EFECTIVO ============
            if ($cashBox->type === 'cash') {
                $this->arqueoEfectivo($cashRegister, $central, $request->input('counted'), $request->input('observation'));
            }
            // ============ BANCARIO ============
            else {
                $this->arqueoBancario($cashRegister, $central, $request->input('observation'));
            }

            // Cerrar sesión del cajero
            $cashRegister->status = 0;
            $cashRegister->closing_time = now();
            $cashRegister->closing_balance = $cashRegister->current_balance; // luego de movimientos debería quedar 0 (o fondo fijo)
            $cashRegister->save();

            DB::commit();

            return response()->json([
                'message' => 'Arqueo realizado y sesión cerrada correctamente.',
                'cash_register_id' => $cashRegister->id,
                'central_cash_register_id' => $central->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function arqueoEfectivo(CashRegister $cajero, CashRegister $central, $counted = null, $observation = null)
    {
        $montoTeorico = (float)$cajero->current_balance;

        // Si quieres usar contado para observar diferencia:
        $obs = $observation ? trim($observation) : '';
        if (!is_null($counted)) {
            $counted = (float)$counted;
            $diff = $counted - $montoTeorico;
            $obsExtra = "Contado: {$counted}. Teórico: {$montoTeorico}. Diferencia: {$diff}.";
            $obs = $obs ? ($obs . " | " . $obsExtra) : $obsExtra;
        }

        // Expense en cajero (sale del cajero hacia central)
        $movExpense = CashMovement::create([
            'cash_register_id' => $cajero->id,
            'type' => 'expense',
            'amount' => $montoTeorico,
            'description' => 'Entrega a administración (Arqueo)',
            'observation' => $obs ?: null,
            'regularize' => 1,
            'cash_box_subtype_id' => null,
            'arqueo' => true
        ]);

        // Income en central
        CashMovement::create([
            'cash_register_id' => $central->id,
            'type' => 'income',
            'amount' => $montoTeorico,
            'description' => 'Arqueo de cajero ' . Auth::user()->name,
            'observation' => $obs ?: null,
            'regularize' => 1,
            'cash_box_subtype_id' => null,
            'cash_movement_origin_id' => $movExpense->id,
            'arqueo' => true
        ]);

        // Actualizar balances
        $cajero->current_balance = (float)$cajero->current_balance - $montoTeorico;
        $cajero->total_expenses  = (float)$cajero->total_expenses + $montoTeorico;
        $cajero->save();

        $central->current_balance = (float)$central->current_balance + $montoTeorico;
        $central->total_incomes   = (float)$central->total_incomes + $montoTeorico;
        $central->save();
    }

    private function arqueoBancario(CashRegister $cajero, CashRegister $central, $observation = null)
    {
        $userName = Auth::user()->name;

        // Movimientos confirmados del cajero (regularize=1)
        $confirmados = CashMovement::where('cash_register_id', $cajero->id)
            ->where('regularize', 1)
            ->whereIn('type', ['sale', 'income']) // lo que suma a bancario
            ->lockForUpdate()
            ->get();

        $totalConfirmado = (float) $confirmados->sum('amount');

        // Movimientos diferidos del cajero (regularize=0) que aún NO se han espejado
        $diferidos = CashMovement::where('cash_register_id', $cajero->id)
            ->where('regularize', 0)
            ->whereIn('type', ['sale', 'income'])
            ->whereNull('cash_movement_regularize_id') // NO duplicados
            ->lockForUpdate()
            ->get();

        $totalDiferido = (float) $diferidos->sum('amount');

        // 1) Transferir confirmados como expense (cajero) + income (central)
        if ($totalConfirmado > 0) {

            $desc = "Arqueo del cajero {$userName}: confirmado S/ " . number_format($totalConfirmado, 2) .
                ", diferido S/ " . number_format($totalDiferido, 2);

            if ($observation) {
                $desc .= " | " . trim($observation);
            }

            // expense en cajero
            $movExpense = CashMovement::create([
                'cash_register_id' => $cajero->id,
                'type' => 'expense',
                'amount' => $totalConfirmado,
                'description' => "Arqueo del cajero {$userName}",
                'observation' => $desc,
                'regularize' => 1,
                'cash_box_subtype_id' => null,
                'arqueo' => true
            ]);

            // income en central
            CashMovement::create([
                'cash_register_id' => $central->id,
                'type' => 'income',
                'amount' => $totalConfirmado,
                'description' => "Arqueo del cajero {$userName}",
                'observation' => $desc,
                'regularize' => 1,
                'cash_box_subtype_id' => null,
                'cash_movement_origin_id' => $movExpense->id,
                'arqueo' => true
            ]);

            // actualizar balances
            $cajero->current_balance = (float)$cajero->current_balance - $totalConfirmado;
            $cajero->total_expenses  = (float)$cajero->total_expenses + $totalConfirmado;
            $cajero->save();

            $central->current_balance = (float)$central->current_balance + $totalConfirmado;
            $central->total_incomes   = (float)$central->total_incomes + $totalConfirmado;
            $central->save();
        }

        // 2) Clonar diferidos como espejos en central (regularize=0)
        foreach ($diferidos as $mov) {
            $obs = "Movimiento diferido (pendiente) del cajero {$userName}. Origen ID: {$mov->id}.";
            if (!empty($mov->observation)) {
                $obs .= " | Obs origen: " . trim($mov->observation);
            }

            $mirror = CashMovement::create([
                'cash_register_id' => $central->id,
                'type' => $mov->type, // sale o income
                'amount' => $mov->amount,

                // Mantener descripción original
                'description' => "Arqueo: ".$mov->description,

                // ✅ Observación generada para trazabilidad rápida
                'observation' => $obs,

                'regularize' => 0,
                'cash_box_subtype_id' => $mov->cash_box_subtype_id,
                'cash_movement_origin_id' => $mov->id,
                'sale_id' => $mov->sale_id,
                'arqueo' => true

            ]);

            // marcar en el cajero que ya fue enviado al central (evita duplicados)
            $mov->cash_movement_regularize_id = $mirror->id;
            $mov->observation = trim(($mov->observation ?: '') . " | Enviado a central. Mirror ID: {$mirror->id}.");
            $mov->save();
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
