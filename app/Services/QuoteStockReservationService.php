<?php
/**
 * Created by PhpStorm.
 * User: Milly
 * Date: 13/04/2026
 * Time: 12:27 PM
 */

namespace App\Services;

use App\InventoryLevel;
use App\Item;
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

    /**
     * Reserva normal por cantidad.
     * El sistema decide desde qué lotes separar el stock.
     */
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

            $lot->qty_reserved = (float) $lot->qty_reserved + $toReserve;
            $lot->save();

            QuoteStockLot::create([
                'quote_id' => $quoteId,
                'quote_detail_id' => $quoteDetailId,
                'stock_item_id' => $stockItemId,
                'stock_lot_id' => $lot->id,
                'warehouse_id' => $lot->warehouse_id,
                'location_id' => $lot->location_id,
                'quantity' => $toReserve,
                'item_ids' => null,
                'unit_cost' => (float) $lot->unit_cost,
            ]);

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

    /**
     * Reserva productos itemeables.
     * Recibe los Item IDs escogidos manualmente desde la cotización.
     */
    public function reserveItemeableForQuoteDetail(
        int $quoteId,
        int $quoteDetailId,
        int $stockItemId,
        array $itemIds
    ): void {
        $itemIds = collect($itemIds)
            ->map(function ($itemId) {
                return (int) $itemId;
            })
            ->filter(function ($itemId) {
                return $itemId > 0;
            })
            ->values()
            ->toArray();

        if (empty($itemIds)) {
            throw new \Exception('Debe seleccionar al menos un ítem para reservar.');
        }

        if (count($itemIds) !== count(array_unique($itemIds))) {
            throw new \Exception('No se puede reservar el mismo ítem más de una vez.');
        }

        /*
         * LockForUpdate evita que dos cotizaciones concurrentes
         * puedan reservar el mismo Item.
         */
        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->lockForUpdate()
            ->get();

        if ($items->count() !== count($itemIds)) {
            throw new \Exception('Uno o más ítems seleccionados no existen.');
        }

        foreach ($items as $item) {
            if ((int) $item->stock_item_id !== $stockItemId) {
                throw new \Exception(
                    "El ítem {$item->code} no pertenece al producto seleccionado."
                );
            }

            if ((float) $item->percentage !== 1.0) {
                throw new \Exception(
                    "El ítem {$item->code} no corresponde a un ítem completo."
                );
            }

            if ($item->state_item !== 'entered') {
                throw new \Exception(
                    "El ítem {$item->code} ya no está disponible para cotizar."
                );
            }

            if (!$item->stock_lot_id) {
                throw new \Exception(
                    "El ítem {$item->code} no tiene lote asignado."
                );
            }
        }

        /*
         * Agrupamos por lote porque un mismo detalle puede tener
         * ítems seleccionados provenientes de diferentes lotes.
         */
        $itemsByLot = $items->groupBy('stock_lot_id');

        foreach ($itemsByLot as $stockLotId => $itemsOfLot) {
            $lot = StockLot::where('id', $stockLotId)
                ->where('stock_item_id', $stockItemId)
                ->lockForUpdate()
                ->first();

            if (!$lot) {
                throw new \Exception(
                    "No se encontró el lote {$stockLotId} para el producto seleccionado."
                );
            }

            $quantityToReserve = (float) $itemsOfLot->count();

            $availableInLot = (float) $lot->qty_on_hand - (float) $lot->qty_reserved;

            if ($quantityToReserve > $availableInLot) {
                throw new \Exception(
                    "El lote {$lot->id} ya no tiene stock suficiente para reservar los ítems seleccionados."
                );
            }

            /*
             * Validar consistencia de almacén y ubicación.
             */
            foreach ($itemsOfLot as $item) {
                if (
                    (int) $item->warehouse_id !== (int) $lot->warehouse_id ||
                    (int) $item->location_id !== (int) $lot->location_id
                ) {
                    throw new \Exception(
                        "El ítem {$item->code} no coincide con el almacén o ubicación de su lote."
                    );
                }
            }

            $itemIdsOfLot = $itemsOfLot
                ->pluck('id')
                ->map(function ($itemId) {
                    return (int) $itemId;
                })
                ->values()
                ->toArray();

            /*
             * Reserva de cantidad en el lote.
             */
            $lot->qty_reserved = (float) $lot->qty_reserved + $quantityToReserve;
            $lot->save();

            /*
             * Trazabilidad:
             * quote_detail_id corresponde al EquipmentConsumable.
             */
            QuoteStockLot::create([
                'quote_id' => $quoteId,
                'quote_detail_id' => $quoteDetailId,
                'stock_item_id' => $stockItemId,
                'stock_lot_id' => $lot->id,
                'warehouse_id' => $lot->warehouse_id,
                'location_id' => $lot->location_id,
                'quantity' => $quantityToReserve,
                'item_ids' => $itemIdsOfLot,
                'unit_cost' => (float) $lot->unit_cost,
            ]);

            /*
             * Los ítems físicos quedan reservados.
             */
            Item::whereIn('id', $itemIdsOfLot)
                ->where('state_item', 'entered')
                ->update([
                    'state_item' => 'reserved',
                ]);

            $this->syncInventoryLevelReserved(
                $stockItemId,
                $lot->warehouse_id,
                $lot->location_id
            );
        }
    }

    public function releaseReservationsByQuote(int $quoteId): void
    {
        $reservations = QuoteStockLot::where('quote_id', $quoteId)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $this->releaseReservation($reservation);
        }

        QuoteStockLot::where('quote_id', $quoteId)->delete();
    }

    public function releaseReservationsByQuoteDetail(
        int $quoteId,
        int $quoteDetailId
    ): void {
        $reservations = QuoteStockLot::where('quote_id', $quoteId)
            ->where('quote_detail_id', $quoteDetailId)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $this->releaseReservation($reservation);
        }

        QuoteStockLot::where('quote_id', $quoteId)
            ->where('quote_detail_id', $quoteDetailId)
            ->delete();
    }

    /**
     * Libera lote e ítems asociados a una reserva.
     */
    protected function releaseReservation(QuoteStockLot $reservation): void
    {
        $itemIds = $reservation->item_ids ?? [];

        if (!empty($itemIds) && is_array($itemIds)) {
            Item::whereIn('id', $itemIds)
                ->where('state_item', 'reserved')
                ->lockForUpdate()
                ->update([
                    'state_item' => 'entered',
                ]);
        }

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

    public function validateStockForQuote(
        int $stockItemId,
        float $requestedUnits
    ): void {
        $available = $this->getAvailableStockByStockItem($stockItemId);

        if ($requestedUnits > $available) {
            throw new \Exception(
                "No hay stock suficiente. Requerido: {$requestedUnits}. Disponible: {$available}."
            );
        }
    }

    protected function syncInventoryLevelReserved(
        int $stockItemId,
        $warehouseId,
        $locationId
    ): void {
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