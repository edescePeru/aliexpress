<?php
namespace App\Services\Materials;

use App\Material;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class StockItemInventoryRead
{
    private function authorize(): void
    {
        app(MaterialInventoryScope::class)->context();
        $user = Auth::user();
        abort_unless($user->can('list_material') || $user->can('verInventario_material') || $user->can('update_material'), 403);
    }

    private function query($materialId)
    {
        $tenant = TenantContext::tenantId();
        return app(StockItemScreenAccess::class)->stockItems()->where('stock_items.material_id', $materialId)
            ->with([
                'material'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','full_name'); },
                'variant'=>function ($q) use ($tenant, $materialId) {
                    $q->where('tenant_id', $tenant)->where('material_id', $materialId)
                        ->select('id','tenant_id','material_id','attribute_summary','talla_id','color_id');
                },
                'variant.talla'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','name','short_name'); },
                'variant.color'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','name','short_name'); },
                'unitMeasure'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','name'); },
                'inventoryLevels'=>function ($q) {
                    app(MaterialInventoryScope::class)->levels($q)->orderBy('inventory_levels.id')
                        ->with(['location:id,description','warehouse:id,name,branch_id']);
                },
            ])->orderBy('stock_items.id');
    }

    private function variantText($item): string
    {
        $variant = $item->variant;
        if (!$variant) { return ''; }
        if (!empty($variant->attribute_summary)) { return $variant->attribute_summary; }
        return collect([
            optional($variant->talla)->short_name ?: optional($variant->talla)->name,
            optional($variant->color)->name,
        ])->filter()->implode(' / ');
    }

    private function level($level): array
    {
        return [
            'id'=>$level->id, 'warehouse_id'=>$level->warehouse_id, 'warehouse_name'=>optional($level->warehouse)->name,
            'location_id'=>$level->location_id, 'location_name'=>optional($level->location)->description,
            'qty_on_hand'=>(float)$level->qty_on_hand, 'qty_reserved'=>(float)$level->qty_reserved,
            'qty_available'=>MaterialStockReadModel::available($level), 'warehouse_branch_id'=>optional($level->warehouse)->branch_id,
            'min_alert'=>(float)$level->min_alert, 'max_alert'=>(float)$level->max_alert,
            'average_cost'=>(float)$level->average_cost, 'last_cost'=>(float)$level->last_cost,
        ];
    }

    public function forStockItem($id): array
    {
        $this->authorize();
        $allowed = app(StockItemScreenAccess::class)->stockItem($id);
        // Releer por el ID autorizado; no confiar en relaciones precargadas por el llamador.
        $item = $this->query($allowed->material_id)->where('stock_items.id', $allowed->id)->firstOrFail();
        return [
            'stock_item'=>[
                'id'=>$item->id, 'sku'=>$item->sku, 'barcode'=>$item->barcode, 'display_name'=>$item->display_name,
                'material_name'=>optional($item->material)->full_name, 'variant_text'=>$this->variantText($item),
                'unit_measure'=>optional($item->unitMeasure)->name, 'tracks_inventory'=>(int)$item->tracks_inventory, 'is_active'=>(int)$item->is_active,
            ],
            'inventory_levels'=>$item->inventoryLevels->map(function ($level) { return $this->level($level); })->values()->all(),
        ];
    }

    public function forMaterial($id): array
    {
        $this->authorize();
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string)$id), 404);
        $material = Material::query()->where('tenant_id', TenantContext::tenantId())->findOrFail($id, ['id','tenant_id','full_name']);
        // El material puede tener cero productos activos: conservar respuesta vacía sin revelar catálogo ajeno.
        abort_unless(app(StockItemScreenAccess::class)->stockItems()->where('stock_items.material_id', $material->id)->exists(), 404);
        $items = $this->query($material->id)->where('stock_items.is_active', true)->get();
        $rows = [];
        foreach ($items as $item) {
            $variant = $this->variantText($item);
            foreach ($item->inventoryLevels as $level) {
                $rows[] = array_merge($this->level($level), [
                    'stock_item_id'=>$item->id, 'stock_item_sku'=>$item->sku, 'stock_item_barcode'=>$item->barcode,
                    'stock_item_name'=>$item->display_name, 'variant_text'=>$variant, 'unit_measure'=>optional($item->unitMeasure)->name,
                ]);
            }
        }
        return ['material'=>['id'=>$material->id,'full_name'=>$material->full_name], 'inventory_levels'=>$rows];
    }
}
