<?php

namespace App\Services;

use App\InventoryMovement;
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
        $materialIds = array_values(array_unique(array_map('intval', $materialIds)));

        if (empty($materialIds)) {
            return [];
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $date->toDateString();

        // Movimientos hasta la fecha para todos los materiales
        $movements = InventoryMovement::whereIn('material_id', $materialIds)
            ->whereDate('movement_date', '<=', $dateStr)
            ->orderBy('material_id')
            ->orderBy('movement_date')
            ->orderBy('movement_id') // orden estable
            ->get();

        // Inicializar acumuladores por material
        $avg = [];
        $saldoQty = [];
        $saldoImp = [];

        foreach ($materialIds as $id) {
            $avg[$id] = 0.0;
            $saldoQty[$id] = 0.0;
            $saldoImp[$id] = 0.0;
        }

        foreach ($movements as $m) {
            $mid = (int) $m->material_id;

            if (!array_key_exists($mid, $avg)) {
                continue;
            }

            $qty = (float) $m->quantity;

            if ($qty <= 0) {
                continue;
            }

            if ($m->movement_type === 'IN') {
                $uc = (float) $m->unit_cost;
                $imp = $qty * $uc;

                $saldoQty[$mid] += $qty;
                $saldoImp[$mid] += $imp;

                $avg[$mid] = $saldoQty[$mid] > 0 ? ($saldoImp[$mid] / $saldoQty[$mid]) : 0.0;
            } else {
                // OUT se valúa al promedio vigente
                $uc = (float) $avg[$mid];
                $imp = $qty * $uc;

                $saldoQty[$mid] -= $qty;
                $saldoImp[$mid] -= $imp;

                // opcional: clamp
                // $saldoQty[$mid] = max(0.0, $saldoQty[$mid]);
                // $saldoImp[$mid] = max(0.0, $saldoImp[$mid]);
            }
        }

        return $avg;
    }

    /**
     * Helper: devuelve el costo promedio vigente para un material a una fecha.
     */
    public function getAverageCostUpToDate($materialId, $date)
    {
        $result = $this->getAverageCostsUpToDate([(int)$materialId], $date);
        return (float) ($result[$materialId] ?? 0.0);
    }

    public function getAverageCostsByStockItem(array $stockItemIds): array
    {
        $stockItemIds = array_values(array_unique(array_map('intval', $stockItemIds)));

        if (empty($stockItemIds)) {
            return [];
        }

        $movements = InventoryMovement::whereIn('stock_item_id', $stockItemIds)
            ->orderBy('stock_item_id')
            ->orderBy('movement_date')
            ->orderBy('movement_id')
            ->get();

        $avg = [];
        $saldoQty = [];
        $saldoImp = [];

        foreach ($stockItemIds as $id) {
            $avg[$id] = 0.0;
            $saldoQty[$id] = 0.0;
            $saldoImp[$id] = 0.0;
        }

        foreach ($movements as $m) {
            $sid = (int) $m->stock_item_id;

            if (!array_key_exists($sid, $avg)) {
                continue;
            }

            $qty = (float) $m->quantity;

            if ($qty <= 0) {
                continue;
            }

            if ($m->movement_type === 'IN') {
                $uc = (float) $m->unit_cost;
                $imp = $qty * $uc;

                $saldoQty[$sid] += $qty;
                $saldoImp[$sid] += $imp;

                $avg[$sid] = $saldoQty[$sid] > 0
                    ? $saldoImp[$sid] / $saldoQty[$sid]
                    : 0.0;
            } else {
                $uc = (float) ($m->unit_cost ?? $avg[$sid]);
                $imp = $qty * $uc;

                $saldoQty[$sid] -= $qty;
                $saldoImp[$sid] -= $imp;
            }
        }

        return $avg;
    }

    public function getAverageCostByStockItem($stockItemId): float
    {
        $result = $this->getAverageCostsByStockItem([(int) $stockItemId]);

        return (float) ($result[$stockItemId] ?? 0.0);
    }
}
