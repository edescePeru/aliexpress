<?php

namespace App\Services\Inventory;

use App\InventoryMovement;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

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

        $rows = app(InventoryTimelineQuery::class)->rows([$stockItemId], null, $warehouseIds, null, $currency);

        return InventoryMovement::hydrate($rows->map(function ($row) {
            return (array) $row;
        })->all());
    }
}
