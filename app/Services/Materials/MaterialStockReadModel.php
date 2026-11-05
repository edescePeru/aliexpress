<?php
namespace App\Services\Materials;

use App\StockItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class MaterialStockReadModel
{
    public static function available($level): float
    {
        return round((float) $level->qty_on_hand - (float) $level->qty_reserved, 10);
    }

    private function enabled($query, string $column)
    {
        return $query->whereExists(function ($q) use ($column) {
            $q->selectRaw('1')->from('company_stock_items as indicator_enabled')
                ->whereColumn('indicator_enabled.stock_item_id', $column)
                ->where('indicator_enabled.tenant_id', TenantContext::tenantId())
                ->where('indicator_enabled.company_id', TenantContext::companyId())
                ->where('indicator_enabled.is_active', true);
        });
    }

    public function items(array $materialIds)
    {
        app(MaterialInventoryScope::class)->context();
        return $this->enabled(StockItem::query()->where('stock_items.tenant_id', TenantContext::tenantId())
            ->where('stock_items.is_active', true)->whereIn('stock_items.material_id', $materialIds), 'stock_items.id')
            ->get(['stock_items.id', 'stock_items.material_id', 'stock_items.variant_id']);
    }

    public function rows(array $materialIds = null)
    {
        $query = app(MaterialInventoryScope::class)->levels(DB::table('inventory_levels'))
            ->join('stock_items as indicator_item', 'indicator_item.id', '=', 'inventory_levels.stock_item_id')
            ->join('materials as indicator_material', 'indicator_material.id', '=', 'indicator_item.material_id')
            ->join('warehouses as indicator_warehouse', 'indicator_warehouse.id', '=', 'inventory_levels.warehouse_id')
            ->leftJoin('variants as indicator_variant', function ($join) {
                $join->on('indicator_variant.id', '=', 'indicator_item.variant_id')
                    ->on('indicator_variant.material_id', '=', 'indicator_item.material_id')
                    ->where('indicator_variant.tenant_id', '=', TenantContext::tenantId());
            })
            ->where('indicator_item.tenant_id', TenantContext::tenantId())->where('indicator_item.is_active', true)
            ->where('indicator_material.tenant_id', TenantContext::tenantId())->whereNull('indicator_material.deleted_at')
            ->where('indicator_material.enable_status', 1);
        $this->enabled($query, 'indicator_item.id');
        if ($materialIds !== null) { $query->whereIn('indicator_item.material_id', $materialIds); }
        return $query->orderBy('indicator_item.material_id')->orderBy('indicator_item.id')->orderBy('inventory_levels.id')
            ->get(['inventory_levels.*', 'indicator_item.material_id', 'indicator_item.sku as stock_item_sku',
                'indicator_item.variant_id', 'indicator_material.full_name', 'indicator_warehouse.name as warehouse_name',
                'indicator_warehouse.branch_id as warehouse_branch_id', 'indicator_variant.attribute_summary as variant_text'])
            ->map(function ($row) {
                $row->qty_available = self::available($row);
                $row->estado = $row->qty_available <= 0 ? 'desabastecido'
                    : ((float) $row->min_alert > 0 && $row->qty_available <= (float) $row->min_alert ? 'por_desabastecer' : 'ok');
                return $row;
            });
    }

    public function metrics($items, $rows): array
    {
        $simple = $items->count() === 1 && $items->first()->variant_id === null;
        $local = $rows->filter(function ($row) {
            return $row->warehouse_branch_id !== null && (int) $row->warehouse_branch_id === TenantContext::branchId();
        });
        $general = $rows->filter(function ($row) { return $row->warehouse_branch_id === null; });
        $alerts = $local->filter(function ($row) { return $row->estado !== 'ok'; });
        if ($local->isEmpty()) { $priority = 'Sin inventario registrado'; }
        elseif ($local->contains('estado', 'desabastecido')) { $priority = 'Sin disponibilidad'; }
        elseif ($alerts->isNotEmpty()) { $priority = 'Por agotarse'; }
        elseif ($local->contains(function ($row) { return (float) $row->max_alert > 0 && $row->qty_available > (float) $row->max_alert; })) { $priority = 'Sobre máximo'; }
        else { $priority = 'Aceptable'; }
        return [
            'stock_current' => $simple ? (float) $local->sum('qty_on_hand') : null,
            'stock_general' => $simple ? (float) $general->sum('qty_on_hand') : null,
            'stock_reserved' => $simple ? (float) $local->sum('qty_reserved') : null,
            'stock_available' => $simple ? (float) $local->sum('qty_available') : null,
            // Thresholds have a meaning per StockItem/warehouse, never as a sum.
            'stock_min' => $simple && $rows->count() === 1 && $local->count() === 1 ? (float) $rows->first()->min_alert : null,
            'stock_max' => $simple && $rows->count() === 1 && $local->count() === 1 ? (float) $rows->first()->max_alert : null,
            'prioridad' => $priority, 'stock_alert_count' => $alerts->count(),
            'has_local_inventory' => $local->isNotEmpty(),
        ];
    }

    public function alerts()
    {
        // The automatic summary is for the current branch; general stock stays in detail.
        return $this->rows()->filter(function ($row) {
            return $row->warehouse_branch_id !== null && (int) $row->warehouse_branch_id === TenantContext::branchId()
                && $row->estado !== 'ok';
        })->values();
    }
}
