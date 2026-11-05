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
        $access = app(\App\Services\Materials\StockItemScreenAccess::class);
        $access->permission('listStockitem_material');
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        $request->validate(['search' => 'nullable|string|max:250', 'page' => 'nullable|integer|min:1']);
        $companyId =
            TenantContext::companyId();

        $search =
            trim(
                $request->get(
                    'search',
                    ''
                )
            );


        $query =
            $access->stockItems()

                /*
                 * StockItem habilitado comercialmente
                 * para la Company actual.
                 */
                ->whereHas(
                    'companyStockItems',
                    function ($q) use ($companyId) {

                        $q->where(
                            'company_id',
                            $companyId
                        )
                            ->where(
                                'is_active',
                                true
                            );
                    }
                )

                ->with([
                    'material:id,full_name,description,brand_id,exampler_id,tipo_venta_id',

                    'variant:id,material_id,talla_id,color_id,attribute_summary,is_active',

                    'variant.talla:id,name,short_name',

                    'variant.color:id,name,short_name',

                    /*
                     * SOLO Company actual.
                     */
                    'inventoryLevels' => function ($q) use ($companyId) {
                        app(\App\Services\Materials\MaterialInventoryScope::class)->levels($q);
                        $q->with('warehouse:id,name,branch_id');

                        $q->where(
                            'company_id',
                            $companyId
                        );
                    },

                    'unitMeasure:id,name',
                ]);


        if ($search !== '') {

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'sku',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'barcode',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'display_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'material',
                            function ($mq) use ($search) {

                                $mq->where(
                                    'full_name',
                                    'like',
                                    "%{$search}%"
                                )
                                    ->orWhere(
                                        'description',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }


        $stockItems =
            $query
                ->orderBy(
                    'id',
                    'desc'
                )
                ->paginate(10, ['*'], 'page', (int) $request->input('page', 1));


        $stockItems->setCollection($stockItems->getCollection()->map(function ($stockItem) {
            $local = $stockItem->inventoryLevels->filter(function ($level) {
                return $level->warehouse && $level->warehouse->branch_id !== null
                    && (int) $level->warehouse->branch_id === TenantContext::branchId();
            });
            $general = $stockItem->inventoryLevels->filter(function ($level) {
                return $level->warehouse && $level->warehouse->branch_id === null;
            });
            // Do not serialize legacy Material stock accessors from other warehouses.
            if ($stockItem->material) { $stockItem->material->setAppends([]); }
            foreach ($stockItem->inventoryLevels as $level) {
                $level->setAttribute('qty_available', \App\Services\Materials\MaterialStockReadModel::available($level));
                $level->setAttribute('warehouse_branch_id', $level->warehouse->branch_id);
            }
            $data = $stockItem->toArray();
            $data['stock_current'] = (float) $local->sum('qty_on_hand');
            $data['stock_reserved'] = (float) $local->sum('qty_reserved');
            $data['stock_available'] = round($data['stock_current'] - $data['stock_reserved'], 10);
            $data['stock_general'] = (float) $general->sum('qty_on_hand');
            $data['stock_general_reserved'] = (float) $general->sum('qty_reserved');
            $data['stock_general_available'] = round($data['stock_general'] - $data['stock_general_reserved'], 10);
            return $data;
        }));

        return response()->json(
            $stockItems
        );
    }

    public function index()
    {
        app(\App\Services\Materials\StockItemScreenAccess::class)->permission('listStockitem_material');
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
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
        $material = Material::findOrFail($id);

        return view(
            'material.variants',
            compact('material')
        );
    }

    public function getItemsMaterial($id)
    {
        $material = Material::findOrFail($id);

        $variants =
            Variant::query()
                ->where(
                    'material_id',
                    $material->id
                )
                ->with([
                    'material',
                    'talla',
                    'color',
                ])
                ->get();

        return datatables($variants)->toJson();
    }

    public function getInventoryLevels( StockItem $stockItem) {
        app(\App\Services\Materials\MaterialInventoryScope::class)->context();
        app(\App\Services\Materials\MaterialInventoryScope::class)->stockItem($stockItem);
        $companyId =
            TenantContext::companyId();


        /*
         * Segunda frontera:
         * aunque conozca el ID del StockItem,
         * la Company actual debe tenerlo habilitado.
         */

        $isEnabledForCompany =
            $stockItem
                ->companyStockItems()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->exists();


        if (!$isEnabledForCompany) {

            return response()->json([
                'message' =>
                    'El producto no está habilitado para la empresa actual.',
            ], 404);
        }


        $stockItem->load([
            'material:id,full_name',

            'variant:id,material_id,attribute_summary,talla_id,color_id',

            'variant.talla:id,name,short_name',

            'variant.color:id,name,short_name',

            'inventoryLevels' => function ($q) use ($companyId) {

                app(\App\Services\Materials\MaterialInventoryScope::class)->levels($q)->where(
                    'company_id',
                    $companyId
                )
                    ->with([
                        'location:id,description',
                        'warehouse:id,name,branch_id',
                    ]);
            },

            'unitMeasure:id,name',
        ]);


        $variantText = '';


        if ($stockItem->variant) {

            if (
            !empty(
            $stockItem
                ->variant
                ->attribute_summary
            )
            ) {

                $variantText =
                    $stockItem
                        ->variant
                        ->attribute_summary;

            } else {

                $talla =
                    optional(
                        $stockItem
                            ->variant
                            ->talla
                    )->short_name
                        ?: optional(
                        $stockItem
                            ->variant
                            ->talla
                    )->name;


                $color =
                    optional(
                        $stockItem
                            ->variant
                            ->color
                    )->name;


                $variantText =
                    collect([
                        $talla,
                        $color,
                    ])
                        ->filter()
                        ->implode(' / ');
            }
        }


        $levels =
            $stockItem
                ->inventoryLevels
                ->map(function ($level) {

                    return [
                        'id' =>
                            $level->id,

                        'warehouse_id' =>
                            $level->warehouse_id,

                        'warehouse_name' =>
                            optional(
                                $level->warehouse
                            )->name,

                        'location_id' =>
                            $level->location_id,

                        'location_name' =>
                            optional(
                                $level->location
                            )->description,

                        'qty_on_hand' =>
                            (float) $level->qty_on_hand,

                        'qty_reserved' =>
                            (float) $level->qty_reserved,

                        'qty_available' => \App\Services\Materials\MaterialStockReadModel::available($level),

                        'warehouse_branch_id' => optional($level->warehouse)->branch_id,


                        'min_alert' =>
                            (float) $level->min_alert,

                        'max_alert' =>
                            (float) $level->max_alert,

                        'average_cost' =>
                            (float) $level->average_cost,

                        'last_cost' =>
                            (float) $level->last_cost,
                    ];
                })
                ->values()
                ->toArray();


        return response()->json([
            'stock_item' => [
                'id' =>
                    $stockItem->id,

                'sku' =>
                    $stockItem->sku,

                'barcode' =>
                    $stockItem->barcode,

                'display_name' =>
                    $stockItem->display_name,

                'material_name' =>
                    optional(
                        $stockItem->material
                    )->full_name,

                'variant_text' =>
                    $variantText,

                'unit_measure' =>
                    optional(
                        $stockItem->unitMeasure
                    )->name,

                'tracks_inventory' =>
                    (int) $stockItem->tracks_inventory,

                'is_active' =>
                    (int) $stockItem->is_active,
            ],

            'inventory_levels' =>
                $levels,
        ]);
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
        app(\App\Services\Materials\MaterialInventoryScope::class)->context();
        abort_unless((int) $material->tenant_id === TenantContext::tenantId(), 404);
        $companyId =
            TenantContext::companyId();

        $material = Material::with([
            'stockItems' => function ($query) use ($companyId) {

                $query
                    ->where('is_active', true)

                    /*
                     * El StockItem debe estar habilitado
                     * para la Company actual.
                     */
                    ->whereHas(
                        'companyStockItems',
                        function ($q) use ($companyId) {

                            $q->where(
                                'company_id',
                                $companyId
                            )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    )

                    ->with([
                        'material:id,full_name',

                        'variant:id,material_id,attribute_summary,talla_id,color_id',

                        'variant.talla:id,name,short_name',

                        'variant.color:id,name,short_name',

                        /*
                         * SOLO inventario de la Company actual.
                         */
                        'inventoryLevels' => function ($q) use ($companyId) {

                            app(\App\Services\Materials\MaterialInventoryScope::class)->levels($q)->where(
                                'company_id',
                                $companyId
                            )
                                ->with([
                                    'location:id,description',
                                    'warehouse:id,name,branch_id',
                                ]);
                        },

                        'unitMeasure:id,name',
                    ]);
            },
        ])
            ->findOrFail(
                $material->id
            );


        $inventoryLevels =
            collect();


        foreach (
            $material->stockItems
            as $stockItem
        ) {

            $variantText = '';

            if ($stockItem->variant) {

                if (
                !empty(
                $stockItem
                    ->variant
                    ->attribute_summary
                )
                ) {

                    $variantText =
                        $stockItem
                            ->variant
                            ->attribute_summary;

                } else {

                    $talla =
                        optional(
                            $stockItem
                                ->variant
                                ->talla
                        )->short_name
                            ?: optional(
                            $stockItem
                                ->variant
                                ->talla
                        )->name;

                    $color =
                        optional(
                            $stockItem
                                ->variant
                                ->color
                        )->name;

                    $variantText =
                        collect([
                            $talla,
                            $color,
                        ])
                            ->filter()
                            ->implode(' / ');
                }
            }


            $levels =
                $stockItem
                    ->inventoryLevels
                    ->map(
                        function ($level) use (
                            $stockItem,
                            $variantText
                        ) {

                            return [
                                'id' =>
                                    $level->id,

                                'stock_item_id' =>
                                    $stockItem->id,

                                'stock_item_sku' =>
                                    $stockItem->sku,

                                'stock_item_barcode' =>
                                    $stockItem->barcode,

                                'stock_item_name' =>
                                    $stockItem->display_name,

                                'variant_text' =>
                                    $variantText,

                                'unit_measure' =>
                                    optional(
                                        $stockItem
                                            ->unitMeasure
                                    )->name,

                                'warehouse_id' =>
                                    $level->warehouse_id,

                                'warehouse_name' =>
                                    optional(
                                        $level->warehouse
                                    )->name,

                                'location_id' =>
                                    $level->location_id,

                                'location_name' =>
                                    optional(
                                        $level->location
                                    )->description,

                                'qty_on_hand' =>
                                    (float) $level->qty_on_hand,

                                'qty_reserved' =>
                                    (float) $level->qty_reserved,

                                'qty_available' => \App\Services\Materials\MaterialStockReadModel::available($level),

                                'warehouse_branch_id' => optional($level->warehouse)->branch_id,


                                'min_alert' =>
                                    (float) $level->min_alert,

                                'max_alert' =>
                                    (float) $level->max_alert,

                                'average_cost' =>
                                    (float) $level->average_cost,

                                'last_cost' =>
                                    (float) $level->last_cost,
                            ];
                        }
                    );


            $inventoryLevels =
                $inventoryLevels
                    ->merge(
                        $levels
                    );
        }


        return response()->json([
            'material' => [
                'id' =>
                    $material->id,

                'full_name' =>
                    $material->full_name,
            ],

            'inventory_levels' =>
                $inventoryLevels
                    ->values()
                    ->toArray(),
        ]);
    }

}
