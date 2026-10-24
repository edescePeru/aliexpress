<?php
namespace App\Http\Controllers;

use App\Http\Requests\WarehouseTransferOperationRequest;
use App\Services\Transfers\{WarehouseTransferAccess, WarehouseTransferRequestService, WarehouseTransferPreparationService, WarehouseTransferMovementService};
use App\Support\TenantContext;
use App\Services\Inventory\WarehouseAvailabilityService;
use Illuminate\Validation\Rule;
use App\Transfers\WarehouseTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseTransferController extends Controller
{
    private $access;
    public function __construct(WarehouseTransferAccess $access) { $this->access = $access; }

    public function index(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:requested,prepared,dispatched,partially_received,received,cancelled', 'q' => 'nullable|string|max:60']);
        $query = $this->access->scopeVisible(WarehouseTransfer::query())->with(['sourceWarehouse', 'destinationWarehouse']);
        if (!empty($filters['status'])) { $query->where('status', $filters['status']); }
        if (!empty($filters['q'])) {
            $id = preg_replace('/^TR-/i', '', trim($filters['q']));
            $query->where('id', ctype_digit($id) && strlen($id) <= 18 ? (int) $id : 0);
        }
        $transfers = $query->orderByDesc('id')->paginate(20)->appends($filters);
        return view('warehouseTransfer.index', ['transfers' => $transfers, 'filters' => $filters, 'labels' => $this->labels()]);
    }

    public function create(Request $request, WarehouseAvailabilityService $availability)
    {
        $this->access->context();
        $warehouses = $this->warehouses();
        $destinations = $this->access->managesCompany() ? $warehouses : $warehouses->filter(function ($row) { return (int) $row->branch_id === TenantContext::branchId(); });
        $prefill = null;
        if ($request->hasAny(['source_warehouse_id', 'stock_item_id'])) {
            $request->validate([
                'source_warehouse_id' => 'required|integer|min:1', 'stock_item_id' => 'required|integer|min:1',
                'form_tenant_id' => ['required', 'integer', Rule::in([TenantContext::tenantId()])],
                'form_company_id' => ['required', 'integer', Rule::in([TenantContext::companyId()])],
                'form_branch_id' => ['required', 'integer', Rule::in([TenantContext::branchId()])],
            ], ['form_tenant_id.*' => 'El tenant cambió. Vuelve a consultar el producto.',
                'form_company_id.*' => 'La empresa cambió. Vuelve a consultar el producto.',
                'form_branch_id.*' => 'El local cambió. Vuelve a consultar el producto.']);
            $origin = $this->access->warehouse((int) $request->input('source_warehouse_id'));
            $snapshot = $availability->snapshot((int) $request->input('stock_item_id'));
            $product = $snapshot['product']; $product['available'] = '0.00';
            foreach ($snapshot['groups'] as $group) {
                foreach ($group['warehouses'] as $warehouse) {
                    if ($warehouse['id'] === (int) $origin->id) { $product['available'] = $warehouse['available'] ?? 'Por revisar'; }
                }
            }
            $prefill = ['source_warehouse_id' => (int) $origin->id, 'product' => $product];
        }
        return view('warehouseTransfer.create', ['warehouses' => $warehouses, 'destinations' => $destinations,
            'prefill' => $prefill, 'requestKey' => (string) Str::uuid()]);
    }

    public function products(WarehouseTransferOperationRequest $request)
    {
        $warehouse = $this->access->warehouse((int) $request->input('source_warehouse_id'));
        $query = DB::table('stock_items as s')->join('company_stock_items as c', 'c.stock_item_id', '=', 's.id')
            ->join('materials as m', 'm.id', '=', 's.material_id')
            ->where('s.tenant_id', TenantContext::tenantId())->where('m.tenant_id', TenantContext::tenantId())->whereNull('m.deleted_at')
            ->where('c.tenant_id', TenantContext::tenantId())->where('c.company_id', TenantContext::companyId())
            ->where('s.is_active', true)->where('s.tracks_inventory', true)->where('c.is_active', true);
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') { $query->where(function ($search) use ($q) { $search->where('s.display_name', 'like', '%'.$q.'%')->orWhere('s.sku', 'like', '%'.$q.'%')->orWhere('s.barcode', 'like', '%'.$q.'%'); }); }
        $rows = $query->select('s.id', 's.sku', 's.display_name', 'm.tipo_venta_id')->orderBy('s.display_name')->orderBy('s.id')
            ->offset(((int) $request->input('page', 1) - 1) * 25)->limit(26)->get();
        $more = $rows->count() > 25; $rows = $rows->take(25);
        $lots = DB::table('stock_lots')->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
            ->where('warehouse_id', $warehouse->id)->whereIn('stock_item_id', $rows->pluck('id'))
            ->select('stock_item_id')->selectRaw('SUM(qty_on_hand - qty_reserved) AS available')->groupBy('stock_item_id')->get()->keyBy('stock_item_id');
        return response()->json(['results' => $rows->map(function ($row) use ($lots) {
            $available = $lots->has($row->id) && $lots->get($row->id)->available !== null ? (string) $lots->get($row->id)->available : '0.00';
            return ['id' => $row->id, 'text' => $row->display_name.' · '.$row->sku, 'available' => $available, 'tracks_items' => (int) $row->tipo_venta_id === 3];
        })->values(), 'pagination' => ['more' => $more]]);
    }

    public function store(WarehouseTransferOperationRequest $request, WarehouseTransferRequestService $service)
    {
        return DB::transaction(function () use ($request, $service) {
            $this->lockCompany();
            $this->access->authorizeCreate((int) $request->input('source_warehouse_id'), (int) $request->input('destination_warehouse_id'));
            $transfer = $service->create($request->only(['request_key', 'source_warehouse_id', 'destination_warehouse_id', 'notes', 'lines']));
            return response()->json(['message' => 'Solicitud de traslado guardada correctamente.', 'url' => route('warehouseTransfer.show', $transfer->id)]);
        }, 3);
    }

    public function show($id, WarehouseTransferMovementService $service)
    {
        $transfer = $this->access->scopeVisible(WarehouseTransfer::query())->with(['sourceWarehouse', 'destinationWarehouse', 'receipts.lines.allocation.line', 'lines'])->findOrFail($id);
        $this->access->authorize('view', $transfer);
        $actions = [];
        foreach (['prepare', 'dispatch', 'receive', 'cancel'] as $action) { $actions[$action] = $this->access->allows($action, $transfer); }
        return view('warehouseTransfer.show', ['transfer' => $transfer, 'summary' => $service->summary((int) $id),
            'labels' => $this->labels(), 'actions' => $actions, 'receiptKey' => (string) Str::uuid()]);
    }

    public function prepare(WarehouseTransferOperationRequest $request, $id, WarehouseTransferPreparationService $service)
    {
        return $this->operate('prepare', (int) $id, function () use ($service, $id) { $service->prepare((int) $id); }, 'Traslado preparado. Las existencias quedaron reservadas.');
    }
    public function dispatchTransfer(WarehouseTransferOperationRequest $request, $id, WarehouseTransferMovementService $service)
    {
        return $this->operate('dispatch', (int) $id, function () use ($service, $id) { $service->dispatch((int) $id); }, 'Traslado despachado correctamente.');
    }
    public function receive(WarehouseTransferOperationRequest $request, $id, WarehouseTransferMovementService $service)
    {
        return $this->operate('receive', (int) $id, function () use ($service, $id, $request) { $service->receive((int) $id, $request->only(['request_key', 'notes', 'lines'])); }, 'Recepción guardada correctamente.');
    }
    public function cancel(WarehouseTransferOperationRequest $request, $id, WarehouseTransferPreparationService $service)
    {
        return $this->operate('cancel', (int) $id, function () use ($service, $id, $request) { $service->cancel((int) $id, (string) $request->input('reason')); }, 'Traslado cancelado correctamente.');
    }

    private function operate(string $action, int $id, callable $operation, string $message)
    {
        return DB::transaction(function () use ($action, $id, $operation, $message) {
            $this->lockCompany();
            $transfer = WarehouseTransfer::query()->lockForUpdate()->findOrFail($id);
            $this->access->authorize($action, $transfer); // Autorizar el estado bloqueado, no una lectura anterior.
            $operation();
            return response()->json(['message' => $message, 'url' => route('warehouseTransfer.show', $id)]);
        }, 3);
    }
    private function lockCompany(): void
    {
        $this->access->context();
        DB::table('companies')->where('id', TenantContext::companyId())->where('tenant_id', TenantContext::tenantId())->lockForUpdate()->first();
    }
    private function warehouses()
    {
        return DB::table('warehouses as w')->leftJoin('branches as b', 'b.id', '=', 'w.branch_id')
            ->where('w.tenant_id', TenantContext::tenantId())->where('w.company_id', TenantContext::companyId())
            ->where(function ($q) { $q->whereNull('w.branch_id')->orWhere(function ($q) { $q->where('b.company_id', TenantContext::companyId())->where('b.is_active', true); }); })
            ->select('w.id', 'w.name', 'w.branch_id', 'w.is_default', 'b.name as branch_name')->orderByDesc('w.is_default')->orderBy('w.name')->get();
    }
    private function labels(): array
    {
        return ['requested' => 'Solicitado', 'prepared' => 'Preparado', 'dispatched' => 'Despachado',
            'partially_received' => 'Recepción parcial', 'received' => 'Recibido', 'cancelled' => 'Cancelado'];
    }
}
