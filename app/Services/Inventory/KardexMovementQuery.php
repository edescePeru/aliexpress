<?php

namespace App\Services\Inventory;

use App\InventoryMovement;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KardexMovementQuery
{
    public function forStockItem(int $stockItemId, $warehouseIds, string $currency)
    {
        // La frontera se aplica aquí también: no confiar en el query del llamador.
        $scope = app(\App\Services\Materials\MaterialInventoryScope::class);
        $scope->context();
        $stockItem = \App\StockItem::query()->where('tenant_id', TenantContext::tenantId())->findOrFail($stockItemId);
        $scope->stockItem($stockItem);
        $warehouseIds = $scope->warehouses(DB::table('warehouses'))
            ->whereIn('warehouses.id', clone $warehouseIds)->select('warehouses.id');

        $ordinary = DB::table('inventory_movements_view')
            ->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->where('stock_item_id', $stockItemId)
            ->whereIn('warehouse_id', clone $warehouseIds)
            ->selectRaw('movement_id, movement_type, warehouse_id, location_id, movement_date,
                quantity, unit_cost, source_type, source_id, NULL AS receipt_id, NULL AS base_currency');

        $allocations = DB::table('warehouse_transfer_allocations as a')
            ->join('warehouse_transfer_lines as l', function ($join) {
                $join->on('l.id', '=', 'a.transfer_line_id')
                    ->on('l.tenant_id', '=', 'a.tenant_id')->on('l.company_id', '=', 'a.company_id');
            })
            ->join('warehouse_transfers as t', function ($join) {
                $join->on('t.id', '=', 'l.transfer_id')
                    ->on('t.tenant_id', '=', 'l.tenant_id')->on('t.company_id', '=', 'l.company_id');
            })
            ->join('stock_lots as source_lot', function ($j) {
                $j->on('source_lot.id', '=', 'a.source_stock_lot_id')->on('source_lot.stock_item_id', '=', 'l.stock_item_id')
                    ->on('source_lot.tenant_id', '=', 'a.tenant_id')->on('source_lot.company_id', '=', 'a.company_id')
                    ->on('source_lot.warehouse_id', '=', 't.source_warehouse_id')->on('source_lot.location_id', '=', 't.source_location_id');
            })
            ->join('warehouses as source_wh', function ($j) {
                $j->on('source_wh.id', '=', 't.source_warehouse_id')->on('source_wh.tenant_id', '=', 't.tenant_id')->on('source_wh.company_id', '=', 't.company_id');
            })
            ->join('warehouses as destination_wh', function ($j) {
                $j->on('destination_wh.id', '=', 't.destination_warehouse_id')->on('destination_wh.tenant_id', '=', 't.tenant_id')->on('destination_wh.company_id', '=', 't.company_id');
            })
            ->join('locations as source_loc', function ($j) {
                $j->on('source_loc.id', '=', 't.source_location_id')->on('source_loc.warehouse_id', '=', 't.source_warehouse_id')
                    ->on('source_loc.tenant_id', '=', 't.tenant_id')->on('source_loc.company_id', '=', 't.company_id');
            })
            ->join('locations as destination_loc', function ($j) {
                $j->on('destination_loc.id', '=', 't.destination_location_id')->on('destination_loc.warehouse_id', '=', 't.destination_warehouse_id')
                    ->on('destination_loc.tenant_id', '=', 't.tenant_id')->on('destination_loc.company_id', '=', 't.company_id');
            })
            ->where('t.tenant_id', TenantContext::tenantId())
            ->where('t.company_id', TenantContext::companyId())
            ->where('l.stock_item_id', $stockItemId)
            ->where('a.status', 'dispatched')
            ->whereNotNull('t.dispatched_at');

        $dispatches = (clone $allocations)->whereIn('t.source_warehouse_id', clone $warehouseIds)
            ->selectRaw("CONCAT('TRANSFER_OUT_', a.id), 'OUT', t.source_warehouse_id,
                t.source_location_id, t.dispatched_at, a.quantity, a.base_unit_cost,
                'transfer_dispatch', t.id, NULL, a.base_currency");

        $receipts = (clone $allocations)
            ->join('warehouse_transfer_receipt_lines as rl', function ($join) {
                $join->on('rl.allocation_id', '=', 'a.id')
                    ->on('rl.tenant_id', '=', 'a.tenant_id')->on('rl.company_id', '=', 'a.company_id');
            })
            ->join('warehouse_transfer_receipts as r', function ($join) {
                $join->on('r.id', '=', 'rl.receipt_id')->on('r.transfer_id', '=', 't.id')
                    ->on('r.tenant_id', '=', 'rl.tenant_id')->on('r.company_id', '=', 'rl.company_id');
            })
            ->join('stock_lots as destination_lot', function ($j) {
                $j->on('destination_lot.id', '=', 'rl.destination_stock_lot_id')->on('destination_lot.stock_item_id', '=', 'l.stock_item_id')
                    ->on('destination_lot.tenant_id', '=', 'rl.tenant_id')->on('destination_lot.company_id', '=', 'rl.company_id')
                    ->on('destination_lot.warehouse_id', '=', 't.destination_warehouse_id')->on('destination_lot.location_id', '=', 't.destination_location_id');
            })
            ->whereNotNull('r.received_at')
            ->whereIn('t.destination_warehouse_id', clone $warehouseIds)
            ->selectRaw("CONCAT('TRANSFER_IN_', rl.id), 'IN', t.destination_warehouse_id,
                t.destination_location_id, r.received_at, rl.quantity, a.base_unit_cost,
                'transfer_receipt', t.id, r.id, a.base_currency");

        $rows = DB::query()->fromSub($ordinary->unionAll($dispatches)->unionAll($receipts), 'movements')
            ->orderBy('movement_date')->orderBy('movement_type')->orderBy('movement_id')->get();

        foreach ($rows as $row) {
            if ($row->base_currency !== null && $row->base_currency !== $currency) {
                throw ValidationException::withMessages([
                    'currency' => 'Hay traslados valorizados en otra moneda base. Revise la moneda histórica antes de consultar este Kardex.',
                ]);
            }
        }

        return InventoryMovement::hydrate($rows->map(function ($row) {
            return (array) $row;
        })->all());
    }
}
