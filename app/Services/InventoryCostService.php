<?php

namespace App\Services;

use App\InventoryMovement;
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
        $dateStr = $date->toDateString();

        // Movimientos hasta la fecha para todos los materiales
        $movements = $this->movements()->whereIn('inventory_movements_view.material_id', $materialIds)
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
        $query = $this->movements()->whereIn('inventory_movements_view.stock_item_id', $stockItemIds);

        if ($upToDate) {
            $date = $upToDate instanceof Carbon
                ? $upToDate
                : Carbon::parse($upToDate);

            $query->where(
                'movement_date',
                '<=',
                $date->copy()->endOfDay()
            );
        }

        $movements = $query
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
            $sid =
                (int) $m->stock_item_id;

            if (!array_key_exists($sid, $avg)) {
                continue;
            }

            $qty =
                (float) $m->quantity;

            if ($qty <= 0) {
                continue;
            }

            if ($m->movement_type === 'IN') {
                $uc =
                    (float) $m->unit_cost;

                $imp =
                    $qty * $uc;

                $saldoQty[$sid] += $qty;
                $saldoImp[$sid] += $imp;

                $avg[$sid] =
                    $saldoQty[$sid] > 0
                        ? $saldoImp[$sid] /
                        $saldoQty[$sid]
                        : 0.0;

            } else {
                $uc =
                    (float) (
                        $m->unit_cost
                        ?? $avg[$sid]
                    );

                $imp =
                    $qty * $uc;

                $saldoQty[$sid] -= $qty;
                $saldoImp[$sid] -= $imp;
            }
        }

        return $avg;
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

    private function movements()
    {
        return InventoryMovement::query()->where('inventory_movements_view.tenant_id', TenantContext::tenantId())
            ->where('inventory_movements_view.company_id', TenantContext::companyId())
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('warehouses as cost_warehouse')
                    ->whereColumn('cost_warehouse.id','inventory_movements_view.warehouse_id')
                    ->where('cost_warehouse.tenant_id',TenantContext::tenantId())->where('cost_warehouse.company_id',TenantContext::companyId());
            })
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('locations as cost_location')
                    ->whereColumn('cost_location.id','inventory_movements_view.location_id')
                    ->whereColumn('cost_location.warehouse_id','inventory_movements_view.warehouse_id')
                    ->where('cost_location.tenant_id',TenantContext::tenantId())->where('cost_location.company_id',TenantContext::companyId());
            });
    }

}
