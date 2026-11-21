<?php
namespace App\Services\Materials;

use App\Support\TenantContext;
use Illuminate\Http\Request;

class StockItemListRead
{
    public function page(Request $request)
    {
        $access = app(StockItemScreenAccess::class);
        $access->permission('listStockitem_material');
        app(MaterialInventoryScope::class)->snapshot($request);
        $data = $request->validate([
            'search'=>['nullable','string','max:250'],
            'page'=>['nullable','integer','min:1','max:'.intdiv(PHP_INT_MAX, 10)],
        ]);
        $tenant = TenantContext::tenantId();
        $query = $access->stockItems()->with([
            'material'=>function ($q) use ($tenant) { $q->where('tenant_id',$tenant)->select('id','tenant_id','full_name','description','tipo_venta_id'); },
            'variant'=>function ($q) use ($tenant) { $q->where('tenant_id',$tenant)->select('id','tenant_id','material_id','talla_id','color_id','attribute_summary','is_active'); },
            'variant.talla'=>function ($q) use ($tenant) { $q->where('tenant_id',$tenant)->select('id','tenant_id','name','short_name'); },
            'variant.color'=>function ($q) use ($tenant) { $q->where('tenant_id',$tenant)->select('id','tenant_id','name','short_name'); },
            'unitMeasure'=>function ($q) use ($tenant) { $q->where('tenant_id',$tenant)->select('id','tenant_id','name'); },
            'inventoryLevels'=>function ($q) { app(MaterialInventoryScope::class)->levels($q)->orderBy('inventory_levels.id')->with('warehouse:id,name,branch_id'); },
        ]);
        $term = trim($data['search'] ?? '');
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('stock_items.sku','like','%'.$term.'%')->orWhere('stock_items.barcode','like','%'.$term.'%')
                    ->orWhere('stock_items.display_name','like','%'.$term.'%')
                    ->orWhereHas('material',function ($q) use ($term) { $q->where(function ($q) use ($term) {
                        $q->where('full_name','like','%'.$term.'%')->orWhere('description','like','%'.$term.'%');
                    }); });
            });
        }
        $page = $query->orderByDesc('stock_items.id')->paginate(10,['stock_items.*'],'page',(int)($data['page'] ?? 1));
        $page->setCollection($page->getCollection()->map(function ($item) { return $this->row($item); }));
        return $page;
    }

    private function row($item): array
    {
        $variant = $item->variant;
        // Una FK que apunte a variante de otro material nunca debe mostrarse como propia.
        if ($variant && (int)$variant->material_id !== (int)$item->material_id) { $variant = null; }
        $material = $item->material;
        $unit = $item->unitMeasure;
        $result = [
            'id'=>$item->id,'material_id'=>$item->material_id,'variant_id'=>$variant ? $variant->id : null,
            'sku'=>$item->sku,'barcode'=>$item->barcode,'display_name'=>$item->display_name,
            'tracks_inventory'=>$item->tracks_inventory,'is_active'=>$item->is_active,
            'unit_measure_id'=>$unit ? $unit->id : null,
            'material'=>$material ? ['id'=>$material->id,'full_name'=>$material->full_name,'description'=>$material->description,
                'tipo_venta_id'=>$material->tipo_venta_id] : null,
            'variant'=>$variant ? ['id'=>$variant->id,'material_id'=>$variant->material_id,'attribute_summary'=>$variant->attribute_summary,
                'is_active'=>$variant->is_active,
                'talla'=>$variant->talla ? ['id'=>$variant->talla->id,'name'=>$variant->talla->name,'short_name'=>$variant->talla->short_name] : null,
                'color'=>$variant->color ? ['id'=>$variant->color->id,'name'=>$variant->color->name,'short_name'=>$variant->color->short_name] : null] : null,
            'unit_measure'=>$unit ? ['id'=>$unit->id,'name'=>$unit->name] : null,
            'inventory_levels'=>[], 'stock_current'=>0.0,'stock_reserved'=>0.0,'stock_general'=>0.0,'stock_general_reserved'=>0.0,
        ];
        foreach ($item->inventoryLevels as $level) {
            $warehouse = $level->warehouse;
            if (!$warehouse) { continue; }
            $prefix = $warehouse->branch_id === null ? 'stock_general' : 'stock';
            if ($warehouse->branch_id !== null && (int)$warehouse->branch_id !== TenantContext::branchId()) { continue; }
            $quantityKey = $prefix === 'stock' ? 'stock_current' : 'stock_general';
            $reservedKey = $prefix === 'stock' ? 'stock_reserved' : 'stock_general_reserved';
            $result[$quantityKey] += (float)$level->qty_on_hand;
            $result[$reservedKey] += (float)$level->qty_reserved;
            $result['inventory_levels'][] = [
                'id'=>$level->id,'stock_item_id'=>$item->id,'warehouse_id'=>$level->warehouse_id,'location_id'=>$level->location_id,
                'qty_on_hand'=>(float)$level->qty_on_hand,'qty_reserved'=>(float)$level->qty_reserved,
                'qty_available'=>MaterialStockReadModel::available($level),'warehouse_branch_id'=>$warehouse->branch_id,
                'min_alert'=>(float)$level->min_alert,'max_alert'=>(float)$level->max_alert,
                'average_cost'=>(float)$level->average_cost,'last_cost'=>(float)$level->last_cost,
                'warehouse'=>['id'=>$warehouse->id,'name'=>$warehouse->name,'branch_id'=>$warehouse->branch_id],
            ];
        }
        $result['stock_available'] = round($result['stock_current'] - $result['stock_reserved'],10);
        $result['stock_general_available'] = round($result['stock_general'] - $result['stock_general_reserved'],10);
        return $result;
    }
}
