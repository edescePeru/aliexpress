<?php
namespace App\Services\Inventory;

use App\Services\Transfers\WarehouseTransferAccess;
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class WarehouseAvailabilityService
{
    private $access;
    public function __construct(WarehouseTransferAccess $access) { $this->access = $access; }

    public function catalog()
    {
        $this->access->context();
        return DB::table('stock_items as s')->join('materials as m', 'm.id', '=', 's.material_id')
            ->where('s.tenant_id', TenantContext::tenantId())->where('m.tenant_id', TenantContext::tenantId())
            ->where('s.is_active', true)->where('s.tracks_inventory', true)->whereNull('m.deleted_at')
            ->whereExists(function ($q) { $q->selectRaw('1')->from('company_stock_items as c')->whereColumn('c.stock_item_id', 's.id')
                ->where('c.tenant_id', TenantContext::tenantId())->where('c.company_id', TenantContext::companyId())->where('c.is_active', true); });
    }

    public function product(int $id): array
    {
        $row = $this->catalog()->where('s.id', $id)->select('s.id', 's.sku', 's.display_name', 'm.tipo_venta_id')->first();
        if (!$row) { throw ValidationException::withMessages(['stock_item_id' => 'El producto no está habilitado para inventario en la empresa actual.']); }
        return ['id' => (int) $row->id, 'text' => $row->display_name.' · '.$row->sku, 'sku' => $row->sku, 'tracks_items' => (int) $row->tipo_venta_id === 3];
    }

    public function snapshot(int $stock): array
    {
        return DB::transaction(function () use ($stock) {
            $product = $this->product($stock);
            $branches = DB::table('branches')->where('company_id', TenantContext::companyId())->where('is_active', true)->orderBy('name')->orderBy('id')->get();
            $warehouses = $this->owned('warehouses')->orderByDesc('is_default')->orderBy('name')->orderBy('id')->get();
            $warehouses = $warehouses->filter(function ($w) use ($branches) { return $w->branch_id === null || $branches->contains('id', $w->branch_id); });
            $ids = $warehouses->pluck('id')->all();
            $locations = $this->owned('locations')->whereIn('warehouse_id', $ids)->get()->groupBy('warehouse_id');
            $levels = $this->owned('inventory_levels')->where('stock_item_id', $stock)->whereIn('warehouse_id', $ids)->get()->groupBy('warehouse_id');
            $lots = $this->owned('stock_lots')->where('stock_item_id', $stock)->whereIn('warehouse_id', $ids)->get()->groupBy('warehouse_id');
            $items = DB::table('items as i')->join('stock_lots as sl', 'sl.id', '=', 'i.stock_lot_id')
                ->where('i.tenant_id', TenantContext::tenantId())->where('i.company_id', TenantContext::companyId())
                ->where('sl.tenant_id', TenantContext::tenantId())->where('sl.company_id', TenantContext::companyId())
                ->where('i.stock_item_id', $stock)->where('sl.stock_item_id', $stock)->whereIn('i.warehouse_id', $ids)
                ->whereColumn('i.warehouse_id', 'sl.warehouse_id')->whereColumn('i.location_id', 'sl.location_id')->where('i.state_item', 'entered')
                ->select('i.warehouse_id')->selectRaw('COUNT(*) AS quantity')->groupBy('i.warehouse_id')->get()->keyBy('warehouse_id');
            $transit = $this->transit($stock);
            $currentDestinations = $warehouses->filter(function ($w) { return (int) $w->branch_id === TenantContext::branchId(); });
            $groups = [['key' => 'current', 'title' => (optional($branches->firstWhere('id', TenantContext::branchId()))->name ?: 'Local actual'), 'current' => true, 'warehouses' => []],
                ['key' => 'general', 'title' => 'Almacenes generales', 'current' => false, 'warehouses' => []]];
            foreach ($branches as $branch) { if ((int) $branch->id !== TenantContext::branchId()) { $groups[] = ['key' => 'branch-'.$branch->id, 'title' => $branch->name, 'current' => false, 'warehouses' => []]; } }
            foreach ($warehouses as $warehouse) {
                $whLevels = $levels->get($warehouse->id, collect()); $whLots = $lots->get($warehouse->id, collect());
                $level = $this->totals($whLevels); $lot = $this->totals($whLots);
                $hasLevel = $whLevels->count() === 1;
                $balances = $hasLevel ? $level : ($whLevels->isEmpty() ? $lot : $level);
                $locationRows = $locations->get($warehouse->id, collect());
                $traceable = $locationRows->count() === 1 && $level['valid'] && $lot['valid']
                    && (($hasLevel && $level['on_hand'] === $lot['on_hand'] && $level['reserved'] === $lot['reserved']) || ($whLevels->isEmpty() && $whLots->isEmpty()));
                if ($traceable && $hasLevel) {
                    $location = (int) $locationRows->first()->id;
                    foreach ($whLevels->merge($whLots) as $row) { if ((int) $row->location_id !== $location) { $traceable = false; } }
                }
                $available = $balances['valid'] ? bcsub($balances['on_hand'], $balances['reserved'], 2) : null;
                if ($product['tracks_items'] && $available !== null && bccomp((string) ($items->get($warehouse->id)->quantity ?? 0), $available, 2) !== 0) { $traceable = false; }
                $canRequest = $available !== null && bccomp($available, '0', 2) > 0 && $traceable
                    && Auth::user()->can('list_transfer') && Auth::user()->can('create_transfer')
                    && $currentDestinations->contains(function ($destination) use ($warehouse) { return (int) $destination->id !== (int) $warehouse->id; });
                $requestUrl = $canRequest ? route('warehouseTransfer.create', ['source_warehouse_id' => $warehouse->id, 'stock_item_id' => $stock,
                    'form_tenant_id' => TenantContext::tenantId(), 'form_company_id' => TenantContext::companyId(), 'form_branch_id' => TenantContext::branchId()]) : null;
                $card = ['id' => (int) $warehouse->id, 'name' => $warehouse->name,
                    'on_hand' => $balances['valid'] ? $balances['on_hand'] : null, 'reserved' => $balances['valid'] ? $balances['reserved'] : null,
                    'available' => $available, 'requires_review' => !$traceable,
                    'incoming' => $transit[$warehouse->id]['incoming'] ?? '0.00', 'outgoing' => $transit[$warehouse->id]['outgoing'] ?? '0.00', 'request_url' => $requestUrl];
                $key = $warehouse->branch_id === null ? 'general' : ((int) $warehouse->branch_id === TenantContext::branchId() ? 'current' : 'branch-'.$warehouse->branch_id);
                foreach ($groups as &$group) { if ($group['key'] === $key) { $group['warehouses'][] = $card; break; } } unset($group);
            }
            return ['product' => $product, 'groups' => $groups, 'checked_at' => now()->format('d/m/Y H:i:s')];
        });
    }

    private function transit(int $stock): array
    {
        $receipts = $this->owned('warehouse_transfer_receipt_lines')->select('allocation_id')->selectRaw('SUM(quantity) AS quantity')->groupBy('allocation_id');
        $allocations = DB::table('warehouse_transfer_allocations as a')->join('warehouse_transfer_lines as l', 'l.id', '=', 'a.transfer_line_id')
            ->join('warehouse_transfers as t', 't.id', '=', 'l.transfer_id')->leftJoinSub($receipts, 'r', function ($join) { $join->on('r.allocation_id', '=', 'a.id'); })
            ->where('a.tenant_id', TenantContext::tenantId())->where('a.company_id', TenantContext::companyId())
            ->where('l.tenant_id', TenantContext::tenantId())->where('l.company_id', TenantContext::companyId())
            ->where('t.tenant_id', TenantContext::tenantId())->where('t.company_id', TenantContext::companyId())
            ->where('l.stock_item_id', $stock)->where('a.status', 'dispatched')
            ->select('a.quantity', 't.source_warehouse_id', 't.destination_warehouse_id', 'r.quantity as received')->get();
        $result = [];
        foreach ($allocations as $row) {
            $quantity = bcsub((string) $row->quantity, (string) ($row->received ?? '0'), 2);
            if (bccomp($quantity, '0', 2) < 0) { throw ValidationException::withMessages(['stock_item_id' => 'Hay recepciones de traslado que requieren conciliación.']); }
            foreach (['source_warehouse_id' => 'outgoing', 'destination_warehouse_id' => 'incoming'] as $field => $direction) {
                $result[$row->$field][$direction] = bcadd($result[$row->$field][$direction] ?? '0', $quantity, 2);
            }
        }
        return $result;
    }
    private function totals($rows): array
    {
        $onHand = '0.00'; $reserved = '0.00'; $valid = true;
        foreach ($rows as $row) {
            foreach (['qty_on_hand', 'qty_reserved'] as $field) {
                if (!is_scalar($row->$field) || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', (string) $row->$field)) { $valid = false; }
            }
            if (!$valid) { continue; }
            if (bccomp((string) $row->qty_reserved, (string) $row->qty_on_hand, 2) > 0) { $valid = false; continue; }
            $onHand = bcadd($onHand, (string) $row->qty_on_hand, 2); $reserved = bcadd($reserved, (string) $row->qty_reserved, 2);
        }
        return ['on_hand' => $onHand, 'reserved' => $reserved, 'valid' => $valid];
    }
    private function owned(string $table)
    {
        return DB::table($table)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
    }
}
