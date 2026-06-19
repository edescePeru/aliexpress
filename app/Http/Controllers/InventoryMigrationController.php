<?php

namespace App\Http\Controllers;

use App\DetailEntry;
use App\Entry;
use App\InventoryLevel;
use App\Item;
use App\Material;
use App\Output;
use App\OutputDetail;
use App\PriceListItem;
use App\Quote;
use App\SaleDetail;
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

    public function adjustMaterialOldOutput($materialId, $qty_ajusted)
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

            // Tomar el $lots->sum('qty_on_hand');
            /*$available = (float) $lots->sum(function ($lot) {
                return (float) $lot->qty_on_hand - (float) $lot->qty_reserved;
            });*/
            $totalQty = (float) $lots->sum('qty_on_hand');

            // $available < Qty_consume
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
                // $qtyToConsume = (float) Qty_consume;
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

                // Resta
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

    public function adjustMaterialStockOutSinITems(int $materialId, float $qtyToAdjust): Output
    {
        if ($qtyToAdjust <= 0) {
            throw new \Exception("La cantidad a ajustar debe ser mayor a cero.");
        }

        return DB::transaction(function () use ($materialId, $qtyToAdjust) {

            $stockItem = StockItem::where('material_id', $materialId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if (!$stockItem) {
                throw new \Exception("No existe StockItem simple para material_id {$materialId}");
            }

            $lots = StockLot::where('stock_item_id', $stockItem->id)
                ->where('qty_on_hand', '>', 0)
                ->orderByRaw('expiration_date IS NULL ASC') // primero los que sí tienen vencimiento
                ->orderBy('expiration_date')                // FEFO
                ->orderBy('id')                             // FIFO si no hay vencimiento
                ->lockForUpdate()
                ->get();

            if ($lots->isEmpty()) {
                throw new \Exception("No hay lotes con stock para material_id {$materialId}");
            }

            $available = (float) $lots->sum(function ($lot) {
                return max(0, (float) $lot->qty_on_hand - (float) $lot->qty_reserved);
            });

            if ($available < $qtyToAdjust) {
                throw new \Exception(
                    "Stock disponible insuficiente para material {$materialId}. " .
                    "Disponible: {$available}. Requerido: {$qtyToAdjust}."
                );
            }

            $output = Output::create([
                'execution_order'  => 'AJUSTE-MATERIAL-' . $materialId . '-' . now()->format('YmdHis'),
                'request_date'     => now(),
                'requesting_user'  => Auth::id(),
                'responsible_user' => Auth::id(),
                'state'            => 'confirmed',
                'indicator'        => 'or',
            ]);

            $remainingQty = $qtyToAdjust;
            $affectedLevels = [];

            foreach ($lots as $lot) {
                if ($remainingQty <= 0) {
                    break;
                }

                $lotAvailable = max(
                    0,
                    (float) $lot->qty_on_hand - (float) $lot->qty_reserved
                );

                if ($lotAvailable <= 0) {
                    continue;
                }

                $consumeQty = min($remainingQty, $lotAvailable);
                $unitCost = (float) ($lot->unit_cost ?? 0);

                OutputDetail::create([
                    'output_id'      => $output->id,
                    'sale_detail_id' => null,
                    'item_id'        => null,

                    'material_id'   => $materialId,
                    'stock_item_id' => $stockItem->id,
                    'stock_lot_id'  => $lot->id,

                    'warehouse_id' => $lot->warehouse_id,
                    'location_id'  => $lot->location_id,

                    'quote_id'     => null,
                    'equipment_id' => null,

                    'custom'     => 0,
                    'percentage' => $consumeQty,
                    'price'      => 0,

                    'length' => null,
                    'width'  => null,
                    'activo' => null,

                    'unit_cost'  => $unitCost,
                    'total_cost' => $consumeQty * $unitCost,
                ]);

                $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                $lot->save();

                $affectedLevels[] = [
                    'stock_item_id' => $stockItem->id,
                    'warehouse_id'  => $lot->warehouse_id,
                    'location_id'   => $lot->location_id,
                ];

                $remainingQty -= $consumeQty;
            }

            if ($remainingQty > 0.00001) {
                throw new \Exception("No se pudo completar el ajuste. Cantidad pendiente: {$remainingQty}");
            }

            foreach (collect($affectedLevels)->unique() as $level) {
                $this->syncInventoryLevelFromLots(
                    $level['stock_item_id'],
                    $level['warehouse_id'],
                    $level['location_id']
                );
            }

            return $output;
        });
    }

    public function adjustMaterialStockOut(int $materialId, float $qtyToAdjust): Output
    {
        if ($qtyToAdjust <= 0) {
            throw new \Exception("La cantidad a ajustar debe ser mayor a cero.");
        }

        return DB::transaction(function () use ($materialId, $qtyToAdjust) {

            $material = Material::lockForUpdate()->find($materialId);

            if (!$material) {
                throw new \Exception("No existe el material {$materialId}.");
            }

            $stockItem = StockItem::where('material_id', $materialId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if (!$stockItem) {
                throw new \Exception("No existe StockItem simple para material_id {$materialId}.");
            }

            $lots = StockLot::where('stock_item_id', $stockItem->id)
                ->where('qty_on_hand', '>', 0)
                ->orderByRaw('expiration_date IS NULL ASC')
                ->orderBy('expiration_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lots->isEmpty()) {
                throw new \Exception("No hay lotes con stock para material_id {$materialId}.");
            }

            $available = (float) $lots->sum(function ($lot) {
                return max(0, (float) $lot->qty_on_hand - (float) $lot->qty_reserved);
            });

            if ($available < $qtyToAdjust) {
                throw new \Exception(
                    "Stock disponible insuficiente para material {$materialId}. " .
                    "Disponible: {$available}. Requerido: {$qtyToAdjust}."
                );
            }

            $output = Output::create([
                'execution_order'  => 'AJUSTE-MATERIAL-' . $materialId . '-' . now()->format('YmdHis'),
                'request_date'     => now(),
                'requesting_user'  => Auth::id(),
                'responsible_user' => Auth::id(),
                'state'            => 'confirmed',
                'indicator'        => 'or',
            ]);

            $remainingQty = $qtyToAdjust;
            $affectedLevels = [];

            if ((int) $material->tipo_venta_id === 3) {

                if (floor($qtyToAdjust) != $qtyToAdjust) {
                    throw new \Exception(
                        "El material {$materialId} es itemeable. La cantidad a ajustar debe ser entera."
                    );
                }

                foreach ($lots as $lot) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $lotAvailable = max(
                        0,
                        (float) $lot->qty_on_hand - (float) $lot->qty_reserved
                    );

                    if ($lotAvailable <= 0) {
                        continue;
                    }

                    $consumeQty = min((int) $remainingQty, (int) $lotAvailable);

                    $items = Item::where('stock_item_id', $stockItem->id)
                        ->where('stock_lot_id', $lot->id)
                        ->whereIn('state_item', ['entered', 'scrapped'])
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->take($consumeQty)
                        ->get();

                    if ($items->count() < $consumeQty) {
                        throw new \Exception(
                            "Stock insuficiente de items para material {$materialId}, lote {$lot->id}. " .
                            "Se requieren {$consumeQty} items y solo hay {$items->count()} disponibles."
                        );
                    }

                    foreach ($items as $item) {
                        $unitCost = (float) ($item->unit_cost ?? $lot->unit_cost ?? 0);

                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => null,
                            'item_id'        => $item->id,

                            'material_id'   => $materialId,
                            'stock_item_id' => $item->stock_item_id,
                            'stock_lot_id'  => $item->stock_lot_id,

                            'warehouse_id' => $item->warehouse_id,
                            'location_id'  => $item->location_id,

                            'quote_id'      => null,
                            'equipment_id'  => null,
                            'custom'        => 0,
                            'percentage'    => 1,
                            'price'         => 0,
                            'length'        => $item->length,
                            'width'         => $item->width,
                            'activo'        => null,

                            'unit_cost'  => $unitCost,
                            'total_cost' => $unitCost,
                        ]);

                        $item->state_item = 'exited';
                        $item->save();
                    }

                    $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                    $lot->save();

                    $affectedLevels[] = [
                        'stock_item_id' => $stockItem->id,
                        'warehouse_id'  => $lot->warehouse_id,
                        'location_id'   => $lot->location_id,
                    ];

                    $remainingQty -= $consumeQty;
                }

            } else {

                foreach ($lots as $lot) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $lotAvailable = max(
                        0,
                        (float) $lot->qty_on_hand - (float) $lot->qty_reserved
                    );

                    if ($lotAvailable <= 0) {
                        continue;
                    }

                    $consumeQty = min($remainingQty, $lotAvailable);
                    $unitCost = (float) ($lot->unit_cost ?? 0);

                    OutputDetail::create([
                        'output_id'      => $output->id,
                        'sale_detail_id' => null,
                        'item_id'        => null,

                        'material_id'   => $materialId,
                        'stock_item_id' => $stockItem->id,
                        'stock_lot_id'  => $lot->id,

                        'warehouse_id' => $lot->warehouse_id,
                        'location_id'  => $lot->location_id,

                        'quote_id'      => null,
                        'equipment_id'  => null,
                        'custom'        => 0,
                        'percentage'    => $consumeQty,
                        'price'         => 0,
                        'length'        => null,
                        'width'         => null,
                        'activo'        => null,

                        'unit_cost'  => $unitCost,
                        'total_cost' => $consumeQty * $unitCost,
                    ]);

                    $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                    $lot->save();

                    $affectedLevels[] = [
                        'stock_item_id' => $stockItem->id,
                        'warehouse_id'  => $lot->warehouse_id,
                        'location_id'   => $lot->location_id,
                    ];

                    $remainingQty -= $consumeQty;
                }
            }

            if ($remainingQty > 0.00001) {
                throw new \Exception("No se pudo completar el ajuste. Cantidad pendiente: {$remainingQty}.");
            }

            foreach (collect($affectedLevels)->unique() as $level) {
                $this->syncInventoryLevelFromLots(
                    $level['stock_item_id'],
                    $level['warehouse_id'],
                    $level['location_id']
                );
            }

            return $output;
        });
    }

    protected function syncInventoryLevelFromLots(int $stockItemId, $warehouseId, $locationId): void
    {
        $qtyOnHand = (float) StockLot::where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->sum('qty_on_hand');

        $qtyReserved = (float) StockLot::where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->sum('qty_reserved');

        $inventoryLevel = InventoryLevel::lockForUpdate()->firstOrCreate(
            [
                'stock_item_id' => $stockItemId,
                'warehouse_id'  => $warehouseId,
                'location_id'   => $locationId,
            ],
            [
                'qty_on_hand'   => 0,
                'qty_reserved'  => 0,
                'min_alert'     => 0,
                'max_alert'     => 0,
                'average_cost'  => 0,
                'last_cost'     => 0,
            ]
        );

        $inventoryLevel->qty_on_hand  = $qtyOnHand;
        $inventoryLevel->qty_reserved = $qtyReserved;
        $inventoryLevel->save();
    }

    public function adjustStockItemStockOut(Request $request)
    {
        $request->validate([
            'stock_item_id' => 'required|integer|exists:stock_items,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $output = $this->adjustStockItemStockOutLogic(
                (int) $request->stock_item_id,
                (float) $request->quantity
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado correctamente.',
                'output_id' => $output->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function adjustStockItemStockOutLogic(int $stockItemId, float $qtyToAdjust): Output
    {
        if ($qtyToAdjust <= 0) {
            throw new \Exception("La cantidad a ajustar debe ser mayor a cero.");
        }

        return DB::transaction(function () use ($stockItemId, $qtyToAdjust) {

            $stockItem = StockItem::with('material')
                ->lockForUpdate()
                ->find($stockItemId);

            if (!$stockItem) {
                throw new \Exception("No existe el StockItem {$stockItemId}.");
            }

            $material = $stockItem->material;

            if (!$material) {
                throw new \Exception("El StockItem {$stockItemId} no tiene material asociado.");
            }

            $materialId = $material->id;

            $lots = StockLot::where('stock_item_id', $stockItem->id)
                ->where('qty_on_hand', '>', 0)
                ->orderByRaw('expiration_date IS NULL ASC')
                ->orderBy('expiration_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lots->isEmpty()) {
                throw new \Exception("No hay lotes con stock para stock_item_id {$stockItemId}.");
            }

            $available = (float) $lots->sum(function ($lot) {
                return max(0, (float) $lot->qty_on_hand - (float) $lot->qty_reserved);
            });

            if ($available < $qtyToAdjust) {
                throw new \Exception(
                    "Stock disponible insuficiente. Disponible: {$available}. Requerido: {$qtyToAdjust}."
                );
            }

            $output = Output::create([
                'execution_order'  => 'AJUSTE-STOCKITEM-' . $stockItemId . '-' . now()->format('YmdHis'),
                'request_date'     => now(),
                'requesting_user'  => Auth::id(),
                'responsible_user' => Auth::id(),
                'state'            => 'confirmed',
                'indicator'        => 'or',
            ]);

            $remainingQty = $qtyToAdjust;
            $affectedLevels = [];

            if ((int) $material->tipo_venta_id === 3) {
                if (floor($qtyToAdjust) != $qtyToAdjust) {
                    throw new \Exception(
                        "Este producto es itemeable. La cantidad a ajustar debe ser entera."
                    );
                }

                foreach ($lots as $lot) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $lotAvailable = max(
                        0,
                        (float) $lot->qty_on_hand - (float) $lot->qty_reserved
                    );

                    if ($lotAvailable <= 0) {
                        continue;
                    }

                    $consumeQty = min((int) $remainingQty, (int) $lotAvailable);

                    $items = Item::where('stock_item_id', $stockItem->id)
                        ->where('stock_lot_id', $lot->id)
                        ->whereIn('state_item', ['entered', 'scrapped'])
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->take($consumeQty)
                        ->get();

                    if ($items->count() < $consumeQty) {
                        throw new \Exception(
                            "Stock insuficiente de items para stock_item_id {$stockItemId}, lote {$lot->id}."
                        );
                    }

                    foreach ($items as $item) {
                        $unitCost = (float) ($item->unit_cost ?? $lot->unit_cost ?? 0);

                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => null,
                            'item_id'        => $item->id,

                            'material_id'   => $materialId,
                            'stock_item_id' => $item->stock_item_id,
                            'stock_lot_id'  => $item->stock_lot_id,

                            'warehouse_id' => $item->warehouse_id,
                            'location_id'  => $item->location_id,

                            'quote_id'      => null,
                            'equipment_id'  => null,
                            'custom'        => 0,
                            'percentage'    => 1,
                            'price'         => 0,
                            'length'        => $item->length,
                            'width'         => $item->width,
                            'activo'        => null,

                            'unit_cost'  => $unitCost,
                            'total_cost' => $unitCost,
                        ]);

                        $item->state_item = 'exited';
                        $item->save();
                    }

                    $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                    $lot->save();

                    $affectedLevels[] = [
                        'stock_item_id' => $stockItem->id,
                        'warehouse_id'  => $lot->warehouse_id,
                        'location_id'   => $lot->location_id,
                    ];

                    $remainingQty -= $consumeQty;
                }
            } else {
                foreach ($lots as $lot) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $lotAvailable = max(
                        0,
                        (float) $lot->qty_on_hand - (float) $lot->qty_reserved
                    );

                    if ($lotAvailable <= 0) {
                        continue;
                    }

                    $consumeQty = min($remainingQty, $lotAvailable);
                    $unitCost = (float) ($lot->unit_cost ?? 0);

                    OutputDetail::create([
                        'output_id'      => $output->id,
                        'sale_detail_id' => null,
                        'item_id'        => null,

                        'material_id'   => $materialId,
                        'stock_item_id' => $stockItem->id,
                        'stock_lot_id'  => $lot->id,

                        'warehouse_id' => $lot->warehouse_id,
                        'location_id'  => $lot->location_id,

                        'quote_id'      => null,
                        'equipment_id'  => null,
                        'custom'        => 0,
                        'percentage'    => $consumeQty,
                        'price'         => 0,
                        'length'        => null,
                        'width'         => null,
                        'activo'        => null,

                        'unit_cost'  => $unitCost,
                        'total_cost' => $consumeQty * $unitCost,
                    ]);

                    $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                    $lot->save();

                    $affectedLevels[] = [
                        'stock_item_id' => $stockItem->id,
                        'warehouse_id'  => $lot->warehouse_id,
                        'location_id'   => $lot->location_id,
                    ];

                    $remainingQty -= $consumeQty;
                }
            }

            if ($remainingQty > 0.00001) {
                throw new \Exception("No se pudo completar el ajuste. Cantidad pendiente: {$remainingQty}.");
            }

            foreach (collect($affectedLevels)->unique() as $level) {
                $this->syncInventoryLevelFromLots(
                    $level['stock_item_id'],
                    $level['warehouse_id'],
                    $level['location_id']
                );
            }

            return $output;
        });
    }

    public function adjustEntryCost(Request $request)
    {
        $request->validate([
            'entry_id' => 'required|integer',
            'stock_item_id' => 'required|integer',
            'output_id' => 'nullable|integer',
            'new_total_detail' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $entryId = (int) $request->entry_id;
        $stockItemId = (int) $request->stock_item_id;
        $outputId = $request->filled('output_id') ? (int) $request->output_id : null;
        $newTotalDetail = round((float) $request->new_total_detail, 4);
        $reason = $request->reason;

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. Validar ingreso
            |--------------------------------------------------------------------------
            */
            $entry = Entry::query()
                ->lockForUpdate()
                ->find($entryId);

            if (!$entry) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Ingreso no encontrado.',
                ], 404);
            }

            if ((int) $entry->state_annulled === 1) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se puede ajustar un ingreso anulado.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Buscar DetailEntry
            |--------------------------------------------------------------------------
            | Se busca por entry_id + stock_item_id.
            | Si hay más de una línea con el mismo stock_item_id, se bloquea por seguridad.
            */
            $details = DetailEntry::query()
                ->where('entry_id', $entryId)
                ->where('stock_item_id', $stockItemId)
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró detalle de ingreso para el stock_item_id enviado.',
                ], 404);
            }

            if ($details->count() > 1) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Hay más de un detalle con este stock_item_id en el ingreso. Para evitar errores, envía detail_entry_id o ajusta la búsqueda.',
                    'detail_entry_ids' => $details->pluck('id'),
                ], 422);
            }

            $detailEntry = $details->first();

            if ((float) $detailEntry->entered_quantity <= 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'El detalle de ingreso no tiene cantidad ingresada válida.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Validar material
            |--------------------------------------------------------------------------
            */
            $material = Material::query()
                ->lockForUpdate()
                ->find($detailEntry->material_id);

            if (!$material) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Material no encontrado.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Calcular nuevo costo
            |--------------------------------------------------------------------------
            */
            $enteredQuantity = (float) $detailEntry->entered_quantity;

            $oldUnitCost = round((float) $detailEntry->unit_price, 4);
            $oldTotalDetail = round((float) $detailEntry->total_detail, 4);

            $newUnitCost = round($newTotalDetail / $enteredQuantity, 4);

            /*
            |--------------------------------------------------------------------------
            | 5. Actualizar DetailEntry
            |--------------------------------------------------------------------------
            */
            $detailEntry->unit_price = $newUnitCost;
            $detailEntry->total_detail = $newTotalDetail;
            $detailEntry->save();

            /*
            |--------------------------------------------------------------------------
            | 6. Buscar StockLots del DetailEntry
            |--------------------------------------------------------------------------
            | Esto es clave para no tocar salidas de otros lotes.
            */
            $stockLots = StockLot::query()
                ->where('detail_entry_id', $detailEntry->id)
                ->where('stock_item_id', $stockItemId)
                ->lockForUpdate()
                ->get();

            if ($stockLots->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron lotes relacionados al detalle de ingreso.',
                ], 404);
            }

            $stockLotIds = $stockLots->pluck('id');

            /*
            |--------------------------------------------------------------------------
            | 7. Actualizar costo de StockLots
            |--------------------------------------------------------------------------
            */
            foreach ($stockLots as $stockLot) {
                $stockLot->unit_cost = $newUnitCost;
                $stockLot->save();
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Actualizar Items si el material es itemeable
            |--------------------------------------------------------------------------
            | tipo_venta_id = 3 => itemeable.
            | No tocamos price, solo unit_cost.
            */
            $itemIds = collect();

            if ((int) $material->tipo_venta_id === 3) {
                $items = Item::query()
                    ->where('detail_entry_id', $detailEntry->id)
                    ->where('stock_item_id', $stockItemId)
                    ->where('material_id', $material->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($items as $item) {
                    $item->unit_cost = $newUnitCost;
                    $item->save();
                }

                $itemIds = $items->pluck('id');
            }

            /*
            |--------------------------------------------------------------------------
            | 9. Buscar OutputDetails afectados
            |--------------------------------------------------------------------------
            | Aquí se filtra por stock_lot_id para asegurar que solo se cambian salidas
            | del lote generado por este detail_entry.
            */
            $outputDetailsQuery = OutputDetail::query()
                ->where('stock_item_id', $stockItemId)
                ->where('material_id', $material->id)
                ->whereIn('stock_lot_id', $stockLotIds)
                ->lockForUpdate();

            /*
            |--------------------------------------------------------------------------
            | Si envías output_id, solo corrige esa salida específica.
            | Si no envías output_id, corrige todas las salidas relacionadas a ese lote.
            |--------------------------------------------------------------------------
            */
            if ($outputId) {
                $outputDetailsQuery->where('output_id', $outputId);
            }

            /*
            |--------------------------------------------------------------------------
            | Si es itemeable, también filtramos por item_id.
            | Esto añade una segunda capa de seguridad.
            |--------------------------------------------------------------------------
            */
            if ((int) $material->tipo_venta_id === 3) {
                if ($itemIds->isEmpty()) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'El material es itemeable, pero no se encontraron items relacionados al ingreso.',
                    ], 422);
                }

                $outputDetailsQuery->whereIn('item_id', $itemIds);
            }

            $outputDetails = $outputDetailsQuery->get();

            /*
            |--------------------------------------------------------------------------
            | Puede que aún no haya salidas.
            | En ese caso igual está bien corregir DetailEntry + StockLot + Items.
            |--------------------------------------------------------------------------
            */
            $affectedSaleDetailIds = collect();

            foreach ($outputDetails as $outputDetail) {
                /*
                |--------------------------------------------------------------------------
                | percentage:
                | - si es item: normalmente 1
                | - si no es item: cantidad que salió
                |--------------------------------------------------------------------------
                */
                $quantityOutput = (float) $outputDetail->percentage;

                if ($quantityOutput <= 0) {
                    $quantityOutput = 1;
                }

                $newOutputTotalCost = round($newUnitCost * $quantityOutput, 4);

                $outputDetail->unit_cost = $newUnitCost;
                $outputDetail->total_cost = $newOutputTotalCost;
                $outputDetail->save();

                if ($outputDetail->sale_detail_id) {
                    $affectedSaleDetailIds->push($outputDetail->sale_detail_id);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 10. Recalcular SaleDetails afectados
            |--------------------------------------------------------------------------
            | Se recalcula desde OutputDetails porque una venta puede tener varias salidas.
            */
            $affectedSaleDetailIds = $affectedSaleDetailIds->unique()->values();

            foreach ($affectedSaleDetailIds as $saleDetailId) {
                $saleDetail = SaleDetail::query()
                    ->lockForUpdate()
                    ->find($saleDetailId);

                if (!$saleDetail) {
                    continue;
                }

                $sumTotalCost = OutputDetail::query()
                    ->where('sale_detail_id', $saleDetail->id)
                    ->sum('total_cost');

                $quantitySale = (float) $saleDetail->quantity;

                $saleDetail->total_cost = round($sumTotalCost, 4);
                $saleDetail->unit_cost = $quantitySale > 0
                    ? round($sumTotalCost / $quantitySale, 4)
                    : 0;

                $saleDetail->save();
            }

            /*
            |--------------------------------------------------------------------------
            | 11. Auditoría opcional
            |--------------------------------------------------------------------------
            | Si ya tienes tabla de logs, aquí puedes registrar el cambio.
            */
            /*
            CostAdjustmentLog::create([
                'user_id' => Auth::id(),
                'entry_id' => $entry->id,
                'detail_entry_id' => $detailEntry->id,
                'material_id' => $material->id,
                'stock_item_id' => $stockItemId,
                'output_id' => $outputId,
                'old_unit_cost' => $oldUnitCost,
                'old_total_detail' => $oldTotalDetail,
                'new_unit_cost' => $newUnitCost,
                'new_total_detail' => $newTotalDetail,
                'reason' => $reason,
            ]);
            */

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Costo de ingreso ajustado correctamente.',
                'data' => [
                    'entry_id' => $entry->id,
                    'detail_entry_id' => $detailEntry->id,
                    'material_id' => $material->id,
                    'stock_item_id' => $stockItemId,
                    'output_id' => $outputId,
                    'old_unit_cost' => $oldUnitCost,
                    'old_total_detail' => $oldTotalDetail,
                    'new_unit_cost' => $newUnitCost,
                    'new_total_detail' => $newTotalDetail,
                    'updated_stock_lots' => $stockLotIds->values(),
                    'updated_items' => $itemIds->values(),
                    'updated_output_details' => $outputDetails->pluck('id')->values(),
                    'updated_sale_details' => $affectedSaleDetailIds,
                    'reason' => $reason,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar el costo de ingreso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
