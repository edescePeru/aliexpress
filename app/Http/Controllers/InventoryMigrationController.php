<?php

namespace App\Http\Controllers;

use App\DetailEntry;
use App\Entry;
use App\InventoryLevel;
use App\Material;
use App\Output;
use App\OutputDetail;
use App\PriceListItem;
use App\Quote;
use App\StockItem;
use App\StockLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryMigrationController extends Controller
{
    public function run(Request $request)
    {
        DB::beginTransaction();

        try {
            $warehouseId = (int) $request->input('warehouse_id', 1);
            $locationId  = (int) $request->input('location_id', 1);
            $priceListId = (int) $request->input('price_list_id', 1);

            $materials = Material::whereDoesntHave('stockItems')
                ->where('enable_status', 1)
                ->get();

            if ($materials->isEmpty()) {
                return response()->json([
                    'message' => 'No hay materiales pendientes de migrar.'
                ], 200);
            }

            // 🔥 Solo los que tienen stock
            $materialsWithStock = $materials->filter(function ($material) {
                return (float) ($material->stock_current ?? 0) > 0;
            });

            $entry = null;

            if ($materialsWithStock->isNotEmpty()) {
                $entry = Entry::create([
                    'entry_type' => 'Inventario',
                    'date_entry' => now(),
                    'purchase_order' => 'MIGRACION-INICIAL-' . now()->format('YmdHis'),
                    'invoice' => null,
                    'deferred_invoice' => 'off',
                    'finance' => false,
                    'currency_invoice' => 'PEN',
                    'currency_compra' => 1,
                    'currency_venta' => 1,
                    'image' => 'no_image.png',
                    'observation' => 'MIGRACIÓN INICIAL DE STOCK ITEMS - SALDO DE APERTURA'
                ]);
            }

            $createdStockItems = 0;
            $createdDetails = 0;
            $createdLots = 0;
            $createdLevels = 0;
            $createdPriceListItems = 0;
            $withStock = 0;
            $withoutStock = 0;

            foreach ($materials as $material) {

                $qty = (float) ($material->stock_current ?? 0);
                $unitCost = (float) ($material->unit_price ?? 0);
                $listPrice = (float) ($material->list_price ?? 0);

                $hasCost = $unitCost > 0;

                // =========================
                // 1. Crear StockItem
                // =========================
                $stockItem = StockItem::create([
                    'material_id' => $material->id,
                    'variant_id' => null,
                    'sku' => $material->code ?: 'MAT-' . $material->id,
                    'barcode' => $material->codigo ?? null,
                    'display_name' => $material->full_name,
                    'unit_measure_id' => $material->unit_measure_id,
                    'tracks_inventory' => 1,
                    'is_active' => 1,
                ]);

                $createdStockItems++;

                // =========================
                // 2. Crear PriceListItem
                // =========================
                if ($listPrice > 0) {
                    PriceListItem::updateOrCreate(
                        [
                            'price_list_id' => $priceListId,
                            'stock_item_id' => $stockItem->id,
                        ],
                        [
                            'price' => $listPrice,
                        ]
                    );

                    $createdPriceListItems++;
                }

                // =========================
                // 3. SI TIENE STOCK
                // =========================
                if ($qty > 0) {

                    $withStock++;

                    $detailEntry = DetailEntry::create([
                        'entry_id' => $entry->id,
                        'material_id' => $material->id,
                        'stock_item_id' => $stockItem->id,
                        'ordered_quantity' => $qty,
                        'entered_quantity' => $qty,
                        'unit_price' => $unitCost,
                        'total_detail' => $qty * $unitCost,
                    ]);

                    $createdDetails++;

                    StockLot::create([
                        'stock_item_id' => $stockItem->id,
                        'warehouse_id' => $warehouseId,
                        'location_id' => $locationId,
                        'detail_entry_id' => $detailEntry->id,
                        'lot_code' => 'MIGRACION-INICIAL',
                        'expiration_date' => null,
                        'qty_on_hand' => $qty,
                        'qty_reserved' => 0,
                        'unit_cost' => $unitCost,
                    ]);

                    $createdLots++;

                    InventoryLevel::updateOrCreate(
                        [
                            'stock_item_id' => $stockItem->id,
                            'warehouse_id' => $warehouseId,
                            'location_id' => $locationId,
                        ],
                        [
                            'qty_on_hand' => $qty,
                            'qty_reserved' => 0,
                            'min_alert' => 0,
                            'max_alert' => 0,
                            'average_cost' => $hasCost ? $unitCost : 0,
                            'last_cost' => $hasCost ? $unitCost : 0,
                        ]
                    );

                    $createdLevels++;

                } else {

                    // =========================
                    // 4. SIN STOCK
                    // =========================
                    $withoutStock++;

                    InventoryLevel::updateOrCreate(
                        [
                            'stock_item_id' => $stockItem->id,
                            'warehouse_id' => $warehouseId,
                            'location_id' => $locationId,
                        ],
                        [
                            'qty_on_hand' => 0,
                            'qty_reserved' => 0,
                            'min_alert' => 0,
                            'max_alert' => 0,
                            'average_cost' => $hasCost ? $unitCost : 0,
                            'last_cost' => $hasCost ? $unitCost : 0,
                        ]
                    );

                    $createdLevels++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Migración inicial ejecutada correctamente.',
                'entry_id' => optional($entry)->id,
                'created_stock_items' => $createdStockItems,
                'created_detail_entries' => $createdDetails,
                'created_stock_lots' => $createdLots,
                'created_inventory_levels' => $createdLevels,
                'created_price_list_items' => $createdPriceListItems,
                'materials_with_stock' => $withStock,
                'materials_without_stock' => $withoutStock,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function fixQuotesStockItemsSimple()
    {
        DB::beginTransaction();

        try {
            $quoteIds = [422, 414];

            $quotes = Quote::with(['equipments.consumables'])
                ->whereIn('id', $quoteIds)
                ->get();

            $reservedByStockItem = [];

            foreach ($quotes as $quote) {
                foreach ($quote->equipments as $equipment) {
                    foreach ($equipment->consumables as $consumable) {

                        $materialId = (int) $consumable->material_id;
                        $quantity = (float) $consumable->quantity;

                        if ($materialId <= 0 || $quantity <= 0) {
                            continue;
                        }

                        $stockItem = StockItem::where('material_id', $materialId)
                            ->whereNull('variant_id')
                            ->first();

                        if (!$stockItem) {
                            throw new \Exception("No existe stock_item para material_id {$materialId}");
                        }

                        // Actualizar consumible
                        $consumable->stock_item_id = $stockItem->id;
                        $consumable->save();

                        // Acumular reservado por stockItem
                        if (!isset($reservedByStockItem[$stockItem->id])) {
                            $reservedByStockItem[$stockItem->id] = 0;
                        }

                        $reservedByStockItem[$stockItem->id] += $quantity;
                    }
                }
            }

            foreach ($reservedByStockItem as $stockItemId => $reservedQty) {

                $stockLot = StockLot::where('stock_item_id', $stockItemId)
                    ->lockForUpdate()
                    ->first();

                if (!$stockLot) {
                    throw new \Exception("No existe stock_lot para stock_item_id {$stockItemId}");
                }

                if ((float) $stockLot->qty_on_hand < (float) $reservedQty) {
                    throw new \Exception(
                        "Stock insuficiente para stock_item_id {$stockItemId}. " .
                        "Stock: {$stockLot->qty_on_hand}, reservado requerido: {$reservedQty}"
                    );
                }

                // Como solo hay 1 stockLot, se coloca directo
                $stockLot->qty_reserved = (float) $reservedQty;
                $stockLot->save();

                // Como solo hay 1 inventoryLevel, se coloca directo
                InventoryLevel::where('stock_item_id', $stockItemId)
                    ->update([
                        'qty_reserved' => (float) $reservedQty,
                    ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Cotizaciones reparadas correctamente.',
                'quotes' => $quoteIds,
                'reserved_by_stock_item' => $reservedByStockItem,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function adjustMaterial201Output()
    {
        DB::beginTransaction();

        try {
            $materialId = 201;

            $stockItem = StockItem::where('material_id', $materialId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if (!$stockItem) {
                throw new \Exception("No existe StockItem para material_id {$materialId}");
            }

            $lots = StockLot::where('stock_item_id', $stockItem->id)
                ->where('qty_on_hand', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($lots->isEmpty()) {
                throw new \Exception("No hay stock_lots con stock para material_id {$materialId}");
            }

            $totalQty = (float) $lots->sum('qty_on_hand');

            if ($totalQty <= 0) {
                throw new \Exception("El material {$materialId} no tiene stock disponible para ajustar.");
            }

            // 1. Crear salida de ajuste
            $output = Output::create([
                'execution_order'   => 'AJUSTE-MATERIAL-201-' . now()->format('YmdHis'),
                'request_date'      => now(),
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed', // importante para kardex
                'indicator'         => 'or', // mismo flujo que usas
            ]);

            // 2. Crear output_details por cada lote y consumir stock_lots
            foreach ($lots as $lot) {
                $qtyToConsume = (float) $lot->qty_on_hand;
                $unitCost = (float) ($lot->unit_cost ?? 0);

                OutputDetail::create([
                    'output_id' => $output->id,
                    'sale_detail_id' => null,
                    'item_id' => null,

                    'material_id' => $materialId,
                    'stock_item_id' => $stockItem->id,
                    'stock_lot_id' => $lot->id,

                    'warehouse_id' => $lot->warehouse_id,
                    'location_id' => $lot->location_id,

                    'quote_id' => null,
                    'equipment_id' => null,

                    'custom' => 0,
                    'percentage' => $qtyToConsume,
                    'price' => 0,

                    'unit_cost' => $unitCost,
                    'total_cost' => $qtyToConsume * $unitCost,
                ]);

                $lot->qty_on_hand = 0;
                $lot->qty_reserved = 0;
                $lot->save();
            }

            // 3. Actualizar inventory_levels
            InventoryLevel::where('stock_item_id', $stockItem->id)
                ->update([
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Ajuste realizado correctamente.',
                'material_id' => $materialId,
                'stock_item_id' => $stockItem->id,
                'output_id' => $output->id,
                'quantity_adjusted' => $totalQty,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function adjustMaterialOldOutput($materialId)
    {
        DB::beginTransaction();

        try {

            $stockItem = StockItem::where('material_id', $materialId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if (!$stockItem) {
                throw new \Exception("No existe StockItem para material_id {$materialId}");
            }

            $lots = StockLot::where('stock_item_id', $stockItem->id)
                ->where('qty_on_hand', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($lots->isEmpty()) {
                throw new \Exception("No hay stock_lots con stock para material_id {$materialId}");
            }

            $totalQty = (float) $lots->sum('qty_on_hand');

            if ($totalQty <= 0) {
                throw new \Exception("El material {$materialId} no tiene stock disponible para ajustar.");
            }

            // 1. Crear salida de ajuste
            $output = Output::create([
                'execution_order'   => 'AJUSTE-MATERIAL-201-' . now()->format('YmdHis'),
                'request_date'      => now(),
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed', // importante para kardex
                'indicator'         => 'or', // mismo flujo que usas
            ]);

            // 2. Crear output_details por cada lote y consumir stock_lots
            foreach ($lots as $lot) {
                $qtyToConsume = (float) $lot->qty_on_hand;
                $unitCost = (float) ($lot->unit_cost ?? 0);

                OutputDetail::create([
                    'output_id' => $output->id,
                    'sale_detail_id' => null,
                    'item_id' => null,

                    'material_id' => $materialId,
                    'stock_item_id' => $stockItem->id,
                    'stock_lot_id' => $lot->id,

                    'warehouse_id' => $lot->warehouse_id,
                    'location_id' => $lot->location_id,

                    'quote_id' => null,
                    'equipment_id' => null,

                    'custom' => 0,
                    'percentage' => $qtyToConsume,
                    'price' => 0,

                    'unit_cost' => $unitCost,
                    'total_cost' => $qtyToConsume * $unitCost,
                ]);

                $lot->qty_on_hand = 0;
                $lot->qty_reserved = 0;
                $lot->save();
            }

            // 3. Actualizar inventory_levels
            InventoryLevel::where('stock_item_id', $stockItem->id)
                ->update([
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Ajuste realizado correctamente.',
                'material_id' => $materialId,
                'stock_item_id' => $stockItem->id,
                'output_id' => $output->id,
                'quantity_adjusted' => $totalQty,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }
}
