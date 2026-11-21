<?php

namespace App\Http\Controllers;

use App\InventoryLevel;
use App\Material;
use App\StockItem;
use App\Support\TenantContext;
use App\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockItemController extends Controller
{
    public function list(Request $request)
    {
        return response()->json(app(\App\Services\Materials\StockItemListRead::class)->page($request));
    }

    public function index()
    {
        app(\App\Services\Materials\StockItemScreenAccess::class)->permission('listStockitem_material');
        $user = Auth::user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        return view('stockItem.list', compact('permissions'));
    }

    public function toggleInventory(Request $request, $id)
    {
        $access = app(\App\Services\Materials\StockItemScreenAccess::class);
        $access->permission('configInventariable_material');
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        $request->validate(['value' => 'required|boolean']);
        DB::transaction(function () use ($access, $request, $id) {
            app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
            $stockItem = $access->stockItem($id, true);
            $stockItem->tracks_inventory = (bool) $request->input('value');
            if (!$stockItem->save()) { throw new \RuntimeException('No se pudo guardar la configuración de inventario.'); }
        });
        return response()->json(['success' => true]);
    }

    public function toggleActive(Request $request, $id)
    {
        $access = app(\App\Services\Materials\StockItemScreenAccess::class);
        $access->permission('configActive_material');
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        $request->validate(['value' => 'required|boolean']);
        DB::transaction(function () use ($access, $request, $id) {
            app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
            $stockItem = $access->stockItem($id, true);
            $variant = null;
            if ($stockItem->variant_id) {
                $variant = Variant::query()->where('tenant_id', TenantContext::tenantId())
                    ->where('material_id', $stockItem->material_id)->where('id', $stockItem->variant_id)
                    ->lockForUpdate()->first();
                if (!$variant) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['value' => 'La variante no pertenece al producto.']);
                }
            }
            $stockItem->is_active = (bool) $request->input('value');
            if (!$stockItem->save()) { throw new \RuntimeException('No se pudo guardar el estado.'); }
            if ($variant) {
                $variant->is_active = (bool) $request->input('value');
                if (!$variant->save()) { throw new \RuntimeException('No se pudo guardar el estado de la variante.'); }
            }
        });
        return response()->json(['success' => true]);
    }

    public function viewMaterialVariants($id)
    {
        $material = app(\App\Services\Materials\VariantScreenRead::class)->material($id);
        return view('material.variants', compact('material'));
    }

    public function getItemsMaterial($id)
    {
        $variants = app(\App\Services\Materials\VariantScreenRead::class)->rows($id);
        return datatables($variants)->toJson();
    }

    public function getInventoryLevels(StockItem $stockItem)
    {
        return response()->json(app(\App\Services\Materials\StockItemInventoryRead::class)->forStockItem($stockItem->id));
    }

    public function updateInventoryLevels(Request $request, StockItem $stockItem)
    {
        $scope = app(\App\Services\Materials\MaterialInventoryScope::class);
        $scope->snapshot($request);
        $scope->stockItem($stockItem);
        $request->validate([
            'inventory_levels' => 'required|array|min:1|max:500',
            'inventory_levels.*' => 'required|array',
            'inventory_levels.*.id' => 'required|integer|min:1|distinct',
            'inventory_levels.*.min_alert' => 'sometimes|nullable|numeric|min:0',
            'inventory_levels.*.max_alert' => 'sometimes|nullable|numeric|min:0',
        ]);
        DB::transaction(function () use ($scope, $stockItem, $request) {
            $scope->snapshot($request);
            $scope->stockItem($stockItem);
            $scope->sync($stockItem, $request->input('inventory_levels'));
        });
        return response()->json(['message' => 'Niveles de inventario actualizados correctamente.'], 200);
    }

    public function getInventoryLevelsByMaterial(Material $material)
    {
        return response()->json(app(\App\Services\Materials\StockItemInventoryRead::class)->forMaterial($material->id));
    }
}
