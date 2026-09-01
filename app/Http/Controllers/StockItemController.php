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
            StockItem::query()

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
                ->paginate(10);


        return response()->json(
            $stockItems
        );
    }

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        return view('stockItem.list', compact('permissions'));
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

                $q->where(
                    'company_id',
                    $companyId
                )
                    ->with([
                        'location:id,description',
                        'warehouse:id,name',
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

    public function updateInventoryLevels(Request $request, StockItem $stockItem) {
        $companyId =
            TenantContext::companyId();


        /*
         * La Company debe tener habilitado
         * este StockItem.
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


        $request->validate([
            'inventory_levels' =>
                'required|array|min:1',

            'inventory_levels.*.id' =>
                'required|integer',

            'inventory_levels.*.min_alert' =>
                'nullable|numeric|min:0',

            'inventory_levels.*.max_alert' =>
                'nullable|numeric|min:0',
        ]);


        DB::beginTransaction();


        try {

            foreach (
                $request->inventory_levels
                as $row
            ) {

                /*
                 * CRÍTICO:
                 *
                 * StockItem
                 * +
                 * Company
                 * +
                 * InventoryLevel
                 */

                $inventoryLevel =
                    InventoryLevel::query()
                        ->where(
                            'id',
                            $row['id']
                        )
                        ->where(
                            'stock_item_id',
                            $stockItem->id
                        )
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->lockForUpdate()
                        ->first();


                if (!$inventoryLevel) {

                    throw new \RuntimeException(
                        'Uno de los niveles de inventario no pertenece a la empresa actual.'
                    );
                }


                $min =
                    (
                        $row['min_alert'] !== '' &&
                        $row['min_alert'] !== null
                    )
                        ? (float) $row['min_alert']
                        : 0;


                $max =
                    (
                        $row['max_alert'] !== '' &&
                        $row['max_alert'] !== null
                    )
                        ? (float) $row['max_alert']
                        : 0;


                if ($min > $max) {

                    throw new \RuntimeException(
                        'El stock mínimo no puede ser mayor que el stock máximo.'
                    );
                }


                $inventoryLevel->min_alert =
                    $min;

                $inventoryLevel->max_alert =
                    $max;

                $inventoryLevel->save();
            }


            DB::commit();


            return response()->json([
                'message' =>
                    'Niveles de inventario actualizados correctamente.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);


            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function getInventoryLevelsByMaterial(Material $material)
    {
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

                            $q->where(
                                'company_id',
                                $companyId
                            )
                                ->with([
                                    'location:id,description',
                                    'warehouse:id,name',
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
