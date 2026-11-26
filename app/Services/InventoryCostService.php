<?php

namespace App\Services;

use App\Support\TenantContext;
use Carbon\Carbon;

class InventoryCostService
{
    /**
     * Devuelve el costo promedio ponderado vigente hasta una fecha (inclusive),
     * para una lista de materiales.
     *
     * @param int[] $materialIds
     * @param Carbon|string $date
     * @return array<int, float> [material_id => avg_cost]
     */
    public function getAverageCostsUpToDate(array $materialIds, $date): array
    {
        $this->context();
        $materialIds = $this->ids($materialIds);
        $this->materials($materialIds);

        if (empty($materialIds)) {
            return [];
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $movements = app(\App\Services\Inventory\InventoryTimelineQuery::class)->rows(
            null, $materialIds, null, $date->copy()->endOfDay(), $this->currency()
        );
        return $this->average($movements, 'material_id', $materialIds);
    }

    /**
     * Helper: devuelve el costo promedio vigente para un material a una fecha.
     */
    public function getAverageCostUpToDate($materialId, $date)
    {
        $result = $this->getAverageCostsUpToDate([$materialId], $date);
        return (float) ($result[$materialId] ?? 0.0);
    }

    public function getAverageCostsByStockItem(
        array $stockItemIds,
        $upToDate = null
    ): array
    {
        $this->context();
        $stockItemIds = $this->ids($stockItemIds);
        if (empty($stockItemIds)) { return []; }
        $valid = \Illuminate\Support\Facades\DB::table('stock_items as cost_stock')
            ->join('materials as cost_material', function ($j) {
                $j->on('cost_material.id', '=', 'cost_stock.material_id')->where('cost_material.tenant_id', TenantContext::tenantId());
            })->where('cost_stock.tenant_id', TenantContext::tenantId())->whereIn('cost_stock.id', $stockItemIds)->count();
        if ($valid !== count($stockItemIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['stock_item_id'=>'Hay productos que no pertenecen al tenant actual o tienen un material incompatible.']);
        }
        $until = $upToDate ? ($upToDate instanceof Carbon ? $upToDate->copy() : Carbon::parse($upToDate))->endOfDay() : null;
        $movements = app(\App\Services\Inventory\InventoryTimelineQuery::class)->rows(
            $stockItemIds, null, null, $until, $this->currency()
        );
        return $this->average($movements, 'stock_item_id', $stockItemIds);
    }

    private function average($movements, string $field, array $ids): array
    {
        $avg = $quantity = $value = array_fill_keys($ids, 0.0);
        foreach ($movements as $movement) {
            $id = (int) $movement->$field;
            $units = (float) $movement->quantity;
            if (!array_key_exists($id, $avg) || $units <= 0) { continue; }
            $cost = $movement->unit_cost === null ? $avg[$id] : (float) $movement->unit_cost;
            $sign = $movement->movement_type === 'IN' ? 1 : -1;
            $quantity[$id] += $sign * $units;
            $value[$id] += $sign * $units * $cost;
            $avg[$id] = $quantity[$id] > 0 ? $value[$id] / $quantity[$id] : 0.0;
        }
        return $avg;
    }

    private function currency(): string
    {
        return strtoupper((string) app(SettingService::class)->get('finance.base_currency'));
    }

    public function getAverageCostByStockItem($stockItemId): float
    {
        $result = $this->getAverageCostsByStockItem([$stockItemId]);

        return (float) ($result[$stockItemId] ?? 0.0);
    }
    private function context(): void
    {
        // El promedio de este servicio sigue siendo de empresa, no de una sucursal.
        app(\App\Services\Materials\MaterialParameters::class)->context();
    }

    private function ids(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/D', (string)$id)
                || filter_var($id, FILTER_VALIDATE_INT) === false) {
                throw \Illuminate\Validation\ValidationException::withMessages(['ids'=>'Los identificadores deben ser enteros positivos válidos.']);
            }
            $result[(int)$id] = (int)$id;
        }
        return array_values($result);
    }

    private function materials(array $ids): void
    {
        if (!$ids) { return; }
        $count = \Illuminate\Support\Facades\DB::table('materials')->where('tenant_id', TenantContext::tenantId())->whereIn('id',$ids)->count();
        if ($count !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['material_id'=>'Hay materiales que no pertenecen al tenant actual.']);
        }
    }

}
