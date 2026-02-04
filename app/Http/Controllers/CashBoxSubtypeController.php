<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use Illuminate\Http\Request;

class CashBoxSubtypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // permisos si los usas:
        // $this->middleware('permission:list_cashBoxSubtype')->only(['index','listar']);
        // $this->middleware('permission:create_cashBoxSubtype')->only(['store']);
        // $this->middleware('permission:edit_cashBoxSubtype')->only(['update']);
        // $this->middleware('permission:disable_cashBoxSubtype')->only(['toggle']);
    }

    public function index()
    {
        $permissions = [
            'can_create' => auth()->user()->can('create_cashBoxSubtype'),
            'can_edit'   => auth()->user()->can('edit_cashBoxSubtype'),
            'can_toggle' => auth()->user()->can('disable_cashBoxSubtype') || auth()->user()->can('delete_cashBoxSubtype'),
        ];

        // Solo cajas bancarias usualmente (si quieres filtrar), pero por ahora todas.
        $cashBoxes = CashBox::orderBy('type')->orderBy('name')->get();

        return view('cashBoxSubtype.index', compact('permissions', 'cashBoxes'));
    }

    /**
     * GET /list?q=&cash_box_id=&page=
     */
    public function listar(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $cashBoxId = $request->get('cash_box_id', '');

        $perPage = (int) $request->get('per_page', 10);
        if ($perPage <= 0 || $perPage > 100) $perPage = 10;

        $query = CashBoxSubtype::with('cashBox')
            ->orderBy('position')
            ->orderBy('id');

        if ($cashBoxId !== '' && $cashBoxId !== null) {
            if ($cashBoxId === 'global') {
                $query->whereNull('cash_box_id');
            } else {
                $query->where('cash_box_id', $cashBoxId);
            }
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'cash_box_id' => $item->cash_box_id,
                'cash_box_name' => $item->cashBox ? $item->cashBox->name : 'GLOBAL',
                'code' => $item->code,
                'name' => $item->name,
                'position' => (int) $item->position,
                'is_active' => (bool) $item->is_active,

                // ✅ nuevos
                'is_deferred' => (bool) $item->is_deferred,
                'requires_commission' => (bool) $item->requires_commission,
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

    public function store(Request $request)
    {
        $data = $this->validateSubtype($request);

        $conflictMsg = $this->conflictRuleMessage($data['code'], $data['cash_box_id'], null);
        if ($conflictMsg) return response()->json(['message' => $conflictMsg], 422);

        if ($this->existsSameScopeDuplicate($data['code'], $data['cash_box_id'], null)) {
            return response()->json(['message' => 'Ya existe un subtipo con ese código en ese alcance.'], 422);
        }

        $sub = CashBoxSubtype::create($data);

        return response()->json([
            'message' => 'Subtype creado correctamente.',
            'data' => $sub
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        if (!$id) return response()->json(['message' => 'ID requerido.'], 422);

        $sub = CashBoxSubtype::findOrFail($id);

        $data = $this->validateSubtype($request);

        $conflictMsg = $this->conflictRuleMessage($data['code'], $data['cash_box_id'], $sub->id);
        if ($conflictMsg) return response()->json(['message' => $conflictMsg], 422);

        if ($this->existsSameScopeDuplicate($data['code'], $data['cash_box_id'], $sub->id)) {
            return response()->json(['message' => 'Ya existe un subtipo con ese código en ese alcance.'], 422);
        }

        $sub->update($data);

        return response()->json([
            'message' => 'Subtype actualizado correctamente.',
            'data' => $sub->fresh()
        ]);
    }

    public function toggle(Request $request)
    {
        $id = $request->input('id');
        if (!$id) return response()->json(['message' => 'ID requerido.'], 422);

        $sub = CashBoxSubtype::findOrFail($id);

        $sub->is_active = !$sub->is_active;
        $sub->save();

        return response()->json([
            'message' => $sub->is_active ? 'Subtype activado.' : 'Subtype desactivado.',
            'data' => $sub
        ]);
    }

    private function validateSubtype(Request $request): array
    {
        $data = $request->validate([
            'cash_box_id' => 'nullable',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:120',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable',

            // ✅ nuevos
            'is_deferred' => 'nullable',
            'requires_commission' => 'nullable',
        ]);

        // Alcance
        if (isset($data['cash_box_id']) && $data['cash_box_id'] === 'global') $data['cash_box_id'] = null;
        if (isset($data['cash_box_id']) && $data['cash_box_id'] === '') $data['cash_box_id'] = null;

        if (!is_null($data['cash_box_id'])) {
            $exists = CashBox::where('id', $data['cash_box_id'])->exists();
            if (!$exists) abort(response()->json(['message' => 'Caja no válida.'], 422));
        }

        // Normalizar code
        $data['code'] = strtolower(trim($data['code']));
        $data['code'] = preg_replace('/\s+/', '_', $data['code']);

        $data['name'] = trim($data['name']);
        $data['position'] = $data['position'] ?? 0;

        $data['is_active'] = $request->boolean('is_active');

        // ✅ booleanos nuevos
        $data['is_deferred'] = $request->boolean('is_deferred');
        $data['requires_commission'] = $request->boolean('requires_commission');

        // regla: si no es diferido, no debería requerir comisión
        if (!$data['is_deferred']) {
            $data['requires_commission'] = false;
        }

        return $data;
    }

    /**
     * Evita ambigüedad:
     * - Si GLOBAL: no puede existir en ninguna caja (cualquier cash_box_id != null)
     * - Si CAJA: no puede existir GLOBAL (cash_box_id null)
     */
    private function conflictRuleMessage(string $code, $cashBoxId, $ignoreId = null): ?string
    {
        if (is_null($cashBoxId)) {
            // creando/actualizando GLOBAL
            $q = CashBoxSubtype::where('code', $code)->whereNotNull('cash_box_id');
            if ($ignoreId) $q->where('id', '!=', $ignoreId);

            if ($q->exists()) {
                return "No se puede crear GLOBAL: el código '{$code}' ya existe como subtipo específico de una caja. Elimina/edita el específico o usa otro código.";
            }
        } else {
            // creando/actualizando para CAJA
            $q = CashBoxSubtype::where('code', $code)->whereNull('cash_box_id');
            if ($ignoreId) $q->where('id', '!=', $ignoreId);

            if ($q->exists()) {
                return "No se puede crear para caja: el código '{$code}' ya existe como subtipo GLOBAL. Edita el global o usa otro código.";
            }
        }

        return null;
    }

    /**
     * Duplicado dentro del mismo alcance:
     * - global vs global
     * - caja X vs caja X
     */
    private function existsSameScopeDuplicate(string $code, $cashBoxId, $ignoreId = null): bool
    {
        $q = CashBoxSubtype::where('code', $code);

        if (is_null($cashBoxId)) {
            $q->whereNull('cash_box_id');
        } else {
            $q->where('cash_box_id', $cashBoxId);
        }

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }
}
