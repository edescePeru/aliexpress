<?php

namespace App\Http\Controllers;

use App\DetailEntry;
use App\Entry;
use App\InventoryLevel;
use App\Material;
use App\StockItem;
use App\StockLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMigrationController extends Controller
{
    public function run(Request $request)
    {
        DB::beginTransaction();

        try {
            $warehouseId = (int) $request->input('warehouse_id', 1);
            $locationId  = (int) $request->input('location_id', 1);

            $materials = Material::whereDoesntHave('stockItems')
                ->where('enable_status', 1)
                ->where('stock_current', '>', 0)
                ->get();

            if ($materials->isEmpty()) {
                return response()->json([
                    'message' => 'No hay materiales pendientes de migrar.'
                ], 200);
            }

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

            $createdStockItems = 0;
            $createdDetails = 0;
            $createdLots = 0;
            $createdLevels = 0;

            foreach ($materials as $material) {
                $qty = (float) ($material->stock_current ?? 0);
                $unitCost = (float) ($material->unit_price ?? 0);

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
                        'average_cost' => $unitCost,
                        'last_cost' => $unitCost,
                    ]
                );

                $createdLevels++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Migración inicial ejecutada correctamente.',
                'entry_id' => $entry->id,
                'created_stock_items' => $createdStockItems,
                'created_detail_entries' => $createdDetails,
                'created_stock_lots' => $createdLots,
                'created_inventory_levels' => $createdLevels,
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
