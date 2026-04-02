<?php

namespace App\Http\Controllers;

use App\InventoryLevel;
use App\Material;
use App\StockItem;
use App\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockItemController extends Controller
{
    public function list(Request $request)
    {
        $search = trim($request->get('search', ''));

        $query = StockItem::with([
            'material:id,full_name,description,brand_id,exampler_id',
            'variant:id,material_id,quality_id,color_id,attribute_summary,is_active',
            'variant.talla:id,name,short_name',
            'variant.color:id,name,short_name',
            'inventoryLevels:id,stock_item_id,qty_on_hand,qty_reserved,min_alert,max_alert',
            'unitMeasure:id,name'
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhereHas('material', function ($mq) use ($search) {
                        $mq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $stockItems = $query
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json($stockItems);
    }

    public function index()
    {
        return view('stockItem.list');
    }

    public function toggleInventory(Request $request, $id)
    {
        $stockItem = StockItem::findOrFail($id);

        $stockItem->tracks_inventory = $request->value;
        $stockItem->save();

        return response()->json(['success' => true]);
    }

    public function toggleActive(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $stockItem = StockItem::findOrFail($id);

            $stockItem->is_active = $request->value;
            $stockItem->save();

            // 🔥 Si tiene variante → actualizar también variant
            if ($stockItem->variant_id) {
                $variant = Variant::find($stockItem->variant_id);

                if ($variant) {
                    $variant->is_active = $request->value;
                    $variant->save();
                }
            }

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function viewMaterialVariants($id)
    {
        $material = Material::find($id);
        return view('material.variants', compact('material'));
    }

    public function getItemsMaterial($id)
    {
        $material = Material::find($id);
        $variants = Variant::where('material_id',$material->id)
            ->with('material')
            ->with('talla')
            ->with('color')->get();

        return datatables($variants)->toJson();

    }

    public function getInventoryLevels(StockItem $stockItem)
    {
        $stockItem->load([
            'material:id,full_name',
            'variant:id,material_id,attribute_summary,quality_id,color_id',
            'variant.talla:id,name,short_name',
            'variant.color:id,name,short_name',
            'inventoryLevels.location:id,description',
            'inventoryLevels.warehouse:id,name',
            'unitMeasure:id,name',
        ]);

        $variantText = '';

        if ($stockItem->variant) {
            if (!empty($stockItem->variant->attribute_summary)) {
                $variantText = $stockItem->variant->attribute_summary;
            } else {
                $talla = optional($stockItem->variant->talla)->short_name ?: optional($stockItem->variant->talla)->name;
                $color = optional($stockItem->variant->color)->name;
                $variantText = collect([$talla, $color])->filter()->implode(' / ');
            }
        }

        $levels = $stockItem->inventoryLevels->map(function ($level) {
            return [
                'id'            => $level->id,
                'warehouse_id'  => $level->warehouse_id,
                'warehouse_name'=> optional($level->warehouse)->name,
                'location_id'   => $level->location_id,
                'location_name' => optional($level->location)->description,
                'qty_on_hand'   => (float) $level->qty_on_hand,
                'qty_reserved'  => (float) $level->qty_reserved,
                'min_alert'     => (float) $level->min_alert,
                'max_alert'     => (float) $level->max_alert,
                'average_cost'  => (float) $level->average_cost,
                'last_cost'     => (float) $level->last_cost,
            ];
        })->values()->toArray();

        return response()->json([
            'stock_item' => [
                'id'            => $stockItem->id,
                'sku'           => $stockItem->sku,
                'barcode'       => $stockItem->barcode,
                'display_name'  => $stockItem->display_name,
                'material_name' => optional($stockItem->material)->full_name,
                'variant_text'  => $variantText,
                'unit_measure'  => optional($stockItem->unitMeasure)->name,
                'tracks_inventory' => (int) $stockItem->tracks_inventory,
                'is_active'     => (int) $stockItem->is_active,
            ],
            'inventory_levels' => $levels
        ]);
    }

    public function updateInventoryLevels(Request $request, StockItem $stockItem)
    {
        $request->validate([
            'inventory_levels' => 'required|array|min:1',
            'inventory_levels.*.id' => 'required|integer|exists:inventory_levels,id',
            'inventory_levels.*.min_alert' => 'nullable|numeric|min:0',
            'inventory_levels.*.max_alert' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->inventory_levels as $row) {
                $inventoryLevel = InventoryLevel::where('stock_item_id', $stockItem->id)
                    ->where('id', $row['id'])
                    ->firstOrFail();

                $min = ($row['min_alert'] !== '' && $row['min_alert'] !== null)
                    ? (float) $row['min_alert']
                    : 0;

                $max = ($row['max_alert'] !== '' && $row['max_alert'] !== null)
                    ? (float) $row['max_alert']
                    : 0;

                if ($min > $max) {
                    throw new \Exception('El stock mínimo no puede ser mayor que el stock máximo.');
                }

                $inventoryLevel->min_alert = $min;
                $inventoryLevel->max_alert = $max;
                $inventoryLevel->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Niveles de inventario actualizados correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
