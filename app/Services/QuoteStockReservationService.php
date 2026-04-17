<?php
/**
 * Created by PhpStorm.
 * User: Milly
 * Date: 13/04/2026
 * Time: 12:27 PM
 */

namespace App\Services;

use App\InventoryLevel;
use App\QuoteStockLot;
use App\StockLot;
use Illuminate\Support\Facades\DB;

class QuoteStockReservationService
{
    public function getAvailableStockByStockItem(int $stockItemId): float
    {
        $lots = StockLot::where('stock_item_id', $stockItemId)->get();

        return (float) $lots->sum(function ($lot) {
            return (float) $lot->qty_on_hand - (float) $lot->qty_reserved;
        });
    }

    public function reserveForQuoteDetail(
        int $quoteId,
        ?int $quoteDetailId,
        int $stockItemId,
        float $requestedUnits
    ): void {
        if ($requestedUnits <= 0) {
            throw new \Exception('La cantidad a reservar debe ser mayor a 0.');
        }

        $lots = StockLot::where('stock_item_id', $stockItemId)
            ->whereRaw('(qty_on_hand - qty_reserved) > 0')
            ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expiration_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $available = (float) $lots->sum(function ($lot) {
            return (float) $lot->qty_on_hand - (float) $lot->qty_reserved;
        });

        if ($requestedUnits > $available) {
            throw new \Exception(
                "No hay stock suficiente para reservar. Requerido: {$requestedUnits}. Disponible: {$available}."
            );
        }

        $remaining = $requestedUnits;

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $availableInLot = (float) $lot->qty_on_hand - (float) $lot->qty_reserved;

            if ($availableInLot <= 0) {
                continue;
            }

            $toReserve = min($remaining, $availableInLot);

            // 1) Reservar en el lote
            $lot->qty_reserved = (float) $lot->qty_reserved + $toReserve;
            $lot->save();

            // 2) Trazabilidad de la reserva
            QuoteStockLot::create([
                'quote_id' => $quoteId,
                'quote_detail_id' => $quoteDetailId,
                'stock_item_id' => $stockItemId,
                'stock_lot_id' => $lot->id,
                'warehouse_id' => $lot->warehouse_id,
                'location_id' => $lot->location_id,
                'quantity' => $toReserve,
                'unit_cost' => (float) $lot->unit_cost,
            ]);

            // 3) Sincronizar resumen del inventory level
            $this->syncInventoryLevelReserved(
                (int) $stockItemId,
                $lot->warehouse_id,
                $lot->location_id
            );

            $remaining -= $toReserve;
        }

        if ($remaining > 0) {
            throw new \Exception('No se pudo completar la reserva de stock.');
        }
    }

    public function releaseReservationsByQuote(int $quoteId): void
    {
        $reservations = QuoteStockLot::where('quote_id', $quoteId)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $lot = StockLot::where('id', $reservation->stock_lot_id)
                ->lockForUpdate()
                ->first();

            if ($lot) {
                $lot->qty_reserved = max(
                    0,
                    (float) $lot->qty_reserved - (float) $reservation->quantity
                );
                $lot->save();

                $this->syncInventoryLevelReserved(
                    (int) $reservation->stock_item_id,
                    $reservation->warehouse_id,
                    $reservation->location_id
                );
            }
        }

        QuoteStockLot::where('quote_id', $quoteId)->delete();
    }

    public function releaseReservationsByQuoteDetail(int $quoteId, int $quoteDetailId): void
    {
        $reservations = QuoteStockLot::where('quote_id', $quoteId)
            ->where('quote_detail_id', $quoteDetailId)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $lot = StockLot::where('id', $reservation->stock_lot_id)
                ->lockForUpdate()
                ->first();

            if ($lot) {
                $lot->qty_reserved = max(
                    0,
                    (float) $lot->qty_reserved - (float) $reservation->quantity
                );
                $lot->save();

                $this->syncInventoryLevelReserved(
                    (int) $reservation->stock_item_id,
                    $reservation->warehouse_id,
                    $reservation->location_id
                );
            }
        }

        QuoteStockLot::where('quote_id', $quoteId)
            ->where('quote_detail_id', $quoteDetailId)
            ->delete();
    }

    public function validateStockForQuote(int $stockItemId, float $requestedUnits): void
    {
        $available = $this->getAvailableStockByStockItem($stockItemId);

        if ($requestedUnits > $available) {
            throw new \Exception(
                "No hay stock suficiente. Requerido: {$requestedUnits}. Disponible: {$available}."
            );
        }
    }

    protected function syncInventoryLevelReserved(int $stockItemId, $warehouseId, $locationId): void
    {
        $reserved = (float) StockLot::where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->sum('qty_reserved');

        $inventoryLevel = InventoryLevel::lockForUpdate()->firstOrCreate(
            [
                'stock_item_id' => $stockItemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
            ],
            [
                'qty_on_hand' => 0,
                'qty_reserved' => 0,
                'min_alert' => 0,
                'max_alert' => 0,
                'average_cost' => 0,
                'last_cost' => 0,
            ]
        );

        $inventoryLevel->qty_reserved = $reserved;
        $inventoryLevel->save();
    }
}