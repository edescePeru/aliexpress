<?php

namespace App\Http\Controllers;

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
            'inventoryLevel:id,stock_item_id,qty_on_hand,qty_reserved,min_alert,max_alert',
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
}
