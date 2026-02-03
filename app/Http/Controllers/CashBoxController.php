<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashBoxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Si tienes permisos (Spatie o Gate), luego lo ajustas:
        // $this->middleware('permission:list_cashBox')->only(['index','list']);
        // $this->middleware('permission:create_cashBox')->only(['store']);
        // $this->middleware('permission:edit_cashBox')->only(['update']);
        // $this->middleware('permission:disable_cashBox')->only(['toggle']);
        // $this->middleware('permission:listCashMovementMy_cashBox')->only(['myIndex','myList']);
        // $this->middleware('permission:listCashMovementAdmin_cashBox')->only(['adminIndex','adminList']);

    }

    /**
     * Vista principal (Blade con modal + JS)
     */
    public function index()
    {
        // Si tu layout usa permisos en frontend, puedes mandarlos.
        // Si no los usas, puedes omitir esto.
        $permissions = [
            'can_create' => auth()->user()->can('create_cashBox'),
            'can_edit'   => auth()->user()->can('edit_cashBox'),
            'can_toggle' => auth()->user()->can('disable_cashBox') || auth()->user()->can('delete_cashBox'),
        ];

        return view('cashBox.index', compact('permissions'));
    }

    /**
     * Listado AJAX paginado + filtro
     * GET: ?q=texto&page=1
     */
    public function listar(Request $request)
    {
        // $this->authorize('list_cashBox'); // si usas policies

        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage <= 0 || $perPage > 100) $perPage = 10;

        $query = CashBox::query()
            ->orderBy('position')
            ->orderBy('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('bank_name', 'like', "%{$q}%")
                    ->orWhere('account_label', 'like', "%{$q}%")
                    ->orWhere('account_number_mask', 'like', "%{$q}%")
                    ->orWhere('currency', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * Crear caja (AJAX)
     */
    public function store(Request $request)
    {
        // $this->authorize('create_cashBox');

        $data = $this->validateCashBox($request, null);

        $box = CashBox::create($data);

        return response()->json([
            'message' => 'Caja creada correctamente.',
            'data' => $box
        ]);
    }

    /**
     * Actualizar caja (AJAX)
     * Recibe id en body (por tu modal)
     */
    public function update(Request $request)
    {
        // $this->authorize('edit_cashBox');

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['message' => 'ID requerido.'], 422);
        }

        $box = CashBox::findOrFail($id);

        $data = $this->validateCashBox($request, $box->id);

        $box->update($data);

        return response()->json([
            'message' => 'Caja actualizada correctamente.',
            'data' => $box->fresh()
        ]);
    }

    /**
     * Activar/Desactivar (AJAX)
     */
    public function toggle(Request $request)
    {
        // $this->authorize('disable_cashBox');

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['message' => 'ID requerido.'], 422);
        }

        $box = CashBox::findOrFail($id);

        // Safety: si quieres evitar desactivar si hay caja abierta, lo veríamos luego
        $box->is_active = !$box->is_active;
        $box->save();

        return response()->json([
            'message' => $box->is_active ? 'Caja activada.' : 'Caja desactivada.',
            'data' => $box
        ]);
    }

    /**
     * Validación y normalización centralizada
     */
    private function validateCashBox(Request $request, $ignoreId = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:cash,bank',

            // checkboxes llegan como "on" o null, por eso validamos nullable
            'uses_subtypes' => 'nullable',
            'is_active' => 'nullable',

            'position' => 'nullable|integer|min:0',

            'bank_name' => 'nullable|string|max:120',
            'account_label' => 'nullable|string|max:120',
            'account_number_mask' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
        ]);

        // Normalizar booleanos
        $data['uses_subtypes'] = $request->boolean('uses_subtypes');
        $data['is_active'] = $request->boolean('is_active');

        // Defaults
        $data['position'] = $data['position'] ?? 0;

        // Regla: efectivo NO usa subtypes y NO guarda datos bancarios
        if ($data['type'] === 'cash') {
            $data['uses_subtypes'] = false;
            $data['bank_name'] = null;
            $data['account_label'] = null;
            $data['account_number_mask'] = null;
        }

        // Regla: si es bank, moneda default PEN si viene vacío (opcional)
        if ($data['type'] === 'bank' && empty($data['currency'])) {
            $data['currency'] = 'PEN';
        }

        // Limpieza extra (evita strings vacíos)
        foreach (['bank_name','account_label','account_number_mask','currency'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = trim($data[$k]);
                if ($data[$k] === '') $data[$k] = null;
            }
        }

        // Opcional: forzar currency a mayúsculas
        if (!empty($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        return $data;
    }

    // =========================
    //  USER (mis movimientos)
    // =========================
    public function myIndex()
    {
        //$cashBoxes = CashBox::where('is_active', 1)->orderBy('type')->orderBy('name')->get();
        $userId = auth()->id();

        $cashBoxes = CashBox::where('is_active', 1)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        // IDs de cash_box con sesión abierta del usuario
        $openCashBoxIds = CashRegister::where('user_id', $userId)
            ->where('status', 1)
            ->whereNotNull('cash_box_id')
            ->pluck('cash_box_id')
            ->unique()
            ->values()
            ->toArray();

        // si ya pasabas subtypes, lo mantienes
        $subtypes  = CashBoxSubtype::where('is_active', 1)->orderBy('position')->orderBy('name')->get();

        $permissions = Auth::user()->getPermissionsViaRoles()->pluck('name')->toArray();

        //return view('cashMovement.my_index', compact('cashBoxes', 'subtypes', 'openCashBoxIds'));
        return view('cashBox.my_index', compact('permissions','cashBoxes', 'subtypes', 'openCashBoxIds'));
    }

    public function myList(Request $request)
    {
        $userId = auth()->id();
        return $this->buildListResponse($request, $userId, 0);
    }

    // =========================
    //  ADMIN (todos)
    // =========================
    public function adminIndex()
    {
        $userId = auth()->id();
        $cashBoxes = CashBox::where('is_active', true)->orderBy('position')->orderBy('name')->get();
        $openCashBoxIds = CashRegister::where('user_id', $userId)
            ->where('status', 1)
            ->whereNotNull('cash_box_id')
            ->pluck('cash_box_id')
            ->unique()
            ->values()
            ->toArray();

        $subtypes  = CashBoxSubtype::where('is_active', 1)->orderBy('position')->orderBy('name')->get();
        $users     = User::orderBy('name')->get();
        $permissions = Auth::user()->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('cashBox.admin_index', compact('openCashBoxIds','permissions','cashBoxes', 'subtypes', 'users'));
    }

    public function adminList(Request $request)
    {
        return $this->buildListResponse($request, null, 1);
    }

    // =========================
    //  Core: query + paginación
    // =========================
    private function buildListResponse(Request $request, $onlyUserId = null, $isAdmin = 0)
    {
        $q = trim((string) $request->get('q', ''));

        $cashBoxId = $request->get('cash_box_id', '');
        $subtypeId = $request->get('subtype_id', '');
        $type      = $request->get('type', ''); // sale|expense|income

        $dateFrom  = $request->get('date_from', ''); // YYYY-MM-DD
        $dateTo    = $request->get('date_to', '');   // YYYY-MM-DD

        $filterUserId = ($isAdmin == 1) ? $request->get('user_id', '') : null;

        $perPage = (int) $request->get('per_page', 10);
        if ($perPage <= 0 || $perPage > 100) $perPage = 10;

        $query = CashMovement::query()
            ->join('cash_registers', 'cash_registers.id', '=', 'cash_movements.cash_register_id')
            ->join('cash_boxes', 'cash_boxes.id', '=', 'cash_registers.cash_box_id')
            ->join('users', 'users.id', '=', 'cash_registers.user_id')
            ->leftJoin('cash_box_subtypes', 'cash_box_subtypes.id', '=', 'cash_movements.cash_box_subtype_id')
            ->select([
                'cash_movements.id',
                'cash_movements.type',
                'cash_movements.amount',
                'cash_movements.description',
                'cash_movements.sale_id',
                'cash_movements.created_at',
                'cash_movements.sale_id',
                'cash_movements.observation',
                'cash_movements.amount_regularize',
                'cash_movements.commission',
                'cash_movements.regularize',

                'cash_registers.id as cash_register_id',
                'cash_registers.user_id as register_user_id',
                'cash_registers.cash_box_id as register_cash_box_id',

                'cash_boxes.name as cash_box_name',
                'cash_boxes.type as cash_box_type',

                'users.name as user_name',

                'cash_box_subtypes.name as subtype_name',
                'cash_box_subtypes.code as subtype_code',
            ])
            ->orderBy('cash_movements.created_at', 'desc')
            ->orderBy('cash_movements.id', 'desc');

        // Scope usuario (si aplica)
        if (!is_null($onlyUserId)) {
            $query->where('cash_registers.user_id', $onlyUserId);
        }

        // Admin: filtro por usuario opcional
        if ($isAdmin == 1 && $filterUserId !== '' && $filterUserId !== null) {
            $query->where('cash_registers.user_id', $filterUserId);
        }

        // Filtro por caja
        if ($cashBoxId !== '' && $cashBoxId !== null) {
            $query->where('cash_registers.cash_box_id', $cashBoxId);
        }

        // Filtro por subtipo
        if ($subtypeId !== '' && $subtypeId !== null) {
            if ($subtypeId === 'none') {
                $query->whereNull('cash_movements.cash_box_subtype_id');
            } else {
                $query->where('cash_movements.cash_box_subtype_id', $subtypeId);
            }
        }

        // Filtro por type (validar enum)
        if ($type !== '' && $type !== null) {
            if (in_array($type, ['sale', 'expense', 'income'], true)) {
                $query->where('cash_movements.type', $type);
            }
        }

        // Filtro por fechas
        if ($dateFrom) {
            $query->whereDate('cash_movements.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('cash_movements.created_at', '<=', $dateTo);
        }

        // Búsqueda libre
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('cash_movements.description', 'like', "%{$q}%")
                    ->orWhere('cash_boxes.name', 'like', "%{$q}%")
                    ->orWhere('users.name', 'like', "%{$q}%")
                    ->orWhere('cash_box_subtypes.name', 'like', "%{$q}%")
                    ->orWhere('cash_box_subtypes.code', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            return [
                'id' => $row->id,
                'type_raw' => $row->type,                 // ✅ útil para UI
                'type' => $row->type,
                'regularize' => (int)$row->regularize,    // ✅ 0/1

                'amount' => $row->amount,
                'amount_regularize' => $row->amount_regularize,
                'commission' => $row->commission,

                'description' => $row->description,
                'observation' => $row->observation,

                'sale_id' => $row->sale_id,
                'created_at' => $row->created_at->format('d/m/Y h:i A'),

                'cash_box_id' => $row->register_cash_box_id,
                'cash_box_name' => $row->cash_box_name,
                'cash_box_type' => $row->cash_box_type,
                'cash_box_label' => $row->cash_box_name, // ✅ alias

                'subtype_name' => $row->subtype_name,
                'subtype_code' => $row->subtype_code,

                'user_name' => $row->user_name,
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }
}
