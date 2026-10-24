<?php
namespace App\Services;

use App\Services\Quotes\QuoteBranchCatalog;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteStockReservationService
{
    public function getAvailableStockByStockItem(int $stockItemId): float
    {
        app(QuoteBranchCatalog::class)->validateContext();
        $this->product($stockItemId);
        return (float) $this->lots($stockItemId)->selectRaw('COALESCE(SUM(qty_on_hand - qty_reserved), 0) AS available')->value('available');
    }

    public function validateStockForQuote(int $stockItemId, float $requestedUnits): void
    {
        $quantity = $this->quantity($requestedUnits, true);
        if (bccomp($quantity, (string) $this->getAvailableStockByStockItem($stockItemId), 2) > 0) {
            $this->invalid('No hay existencias disponibles suficientes en el local actual.');
        }
    }

    public function reserveForQuoteDetail(int $quoteId, ?int $quoteDetailId, int $stockItemId, float $requestedUnits): void
    {
        DB::transaction(function () use ($quoteId, $quoteDetailId, $stockItemId, $requestedUnits) {
            $this->quote($quoteId);
            $this->detail($quoteId, $quoteDetailId, $stockItemId);
            $product = $this->product($stockItemId);
            $this->validateRows($this->rows($quoteId));
            $remaining = $this->quantity($requestedUnits, true);
            $this->capacity($quoteId, $quoteDetailId, $remaining);
            if ((int) $product->tipo_venta_id === 3 && bccomp($remaining, (string) (int) $remaining, 2) !== 0) {
                $this->invalid('Los productos itemeables requieren unidades enteras.');
            }
            foreach ($this->orderedLots($stockItemId)->lockForUpdate()->get() as $lot) {
                $this->place($lot->warehouse_id, $lot->location_id);
                $available = bcsub($this->quantity($lot->qty_on_hand), $this->quantity($lot->qty_reserved), 2);
                if (bccomp($available, '0', 2) < 0) { $this->invalid('El lote tiene reservas inconsistentes.'); }
                if (bccomp($available, '0', 2) === 0) { continue; }
                $take = bccomp($available, $remaining, 2) < 0 ? $available : $remaining;
                $ids = [];
                if ((int) $product->tipo_venta_id === 3) {
                    // Used by recotizacion: FEFO lots, then oldest entered Item IDs.
                    $items = $this->owned('items')->where('stock_item_id', $stockItemId)->where('stock_lot_id', $lot->id)
                        ->where('warehouse_id', $lot->warehouse_id)->where('location_id', $lot->location_id)
                        ->where('percentage', 1)->where('state_item', 'entered')->orderBy('id')->limit((int) $take)->lockForUpdate()->get();
                    if ($items->count() !== (int) $take) { $this->invalid('Los Items disponibles no coinciden con las cantidades del lote.'); }
                    $ids = $items->pluck('id')->map(function ($id) { return (int) $id; })->all();
                    $this->assertUnclaimed($ids);
                }
                $this->reserve($quoteId, $quoteDetailId, $lot, $take, $ids);
                $remaining = bcsub($remaining, $take, 2);
                if (bccomp($remaining, '0', 2) === 0) { break; }
            }
            if (bccomp($remaining, '0', 2) !== 0) { $this->invalid('Los lotes del local actual no cubren la cantidad solicitada.'); }
        });
    }

    public function reserveItemeableForQuoteDetail(int $quoteId, int $quoteDetailId, int $stockItemId, array $itemIds): void
    {
        $itemIds = $this->ids($itemIds);
        if (!$itemIds) { $this->invalid('Seleccione al menos un Item.'); }
        DB::transaction(function () use ($quoteId, $quoteDetailId, $stockItemId, $itemIds) {
            $this->quote($quoteId);
            $this->detail($quoteId, $quoteDetailId, $stockItemId);
            if ((int) $this->product($stockItemId)->tipo_venta_id !== 3) { $this->invalid('El producto no utiliza Items.'); }
            $this->validateRows($this->rows($quoteId));
            $this->capacity($quoteId, $quoteDetailId, (string) count($itemIds));
            // Lock lots before Items, matching quantity reservations and transfers.
            $lots = $this->orderedLots($stockItemId)->lockForUpdate()->get()->keyBy('id');
            $items = $this->owned('items')->whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get();
            if ($items->count() !== count($itemIds)) { $this->invalid('Hay Items inexistentes o ajenos a la empresa.'); }
            $this->assertUnclaimed($itemIds);
            foreach ($items->groupBy('stock_lot_id') as $lotId => $group) {
                $lot = $lots->get($lotId);
                if (!$lot) { $this->invalid('Un Item pertenece al general o a otro local, o no tiene lote válido.'); }
                $this->place($lot->warehouse_id, $lot->location_id);
                foreach ($group as $item) { $this->assertItem($item, $lot, 'entered'); }
                $this->reserve($quoteId, $quoteDetailId, $lot, (string) $group->count(), $group->pluck('id')->all());
            }
        });
    }

    public function releaseReservationsByQuote(int $quoteId): void { $this->release($quoteId, null); }

    public function releaseReservationsByQuoteDetail(int $quoteId, int $quoteDetailId): void
    {
        $this->release($quoteId, $quoteDetailId);
    }

    private function release(int $quoteId, ?int $detailId): void
    {
        DB::transaction(function () use ($quoteId, $detailId) {
            $this->quote($quoteId);
            $all = $this->rows($quoteId);
            $this->validateRows($all);
            if ($detailId !== null) { $this->detail($quoteId, $detailId, null); }
            foreach ($all as $row) {
                if ($detailId !== null && (int) $row->quote_detail_id !== $detailId) { continue; }
                $lot = $this->lots((int) $row->stock_item_id)->where('id', $row->stock_lot_id)->lockForUpdate()->first();
                $quantity = $this->quantity($row->quantity, true);
                $level = $this->level($lot);
                $this->owned('stock_lots')->where('id', $lot->id)->update(['qty_reserved' => bcsub($this->quantity($lot->qty_reserved), $quantity, 2), 'updated_at' => now()]);
                $this->owned('inventory_levels')->where('id', $level->id)->update(['qty_reserved' => bcsub($this->quantity($level->qty_reserved), $quantity, 2), 'updated_at' => now()]);
                $ids = $this->ids($row->item_ids);
                if ($ids) { $this->owned('items')->whereIn('id', $ids)->update(['state_item' => 'entered', 'updated_at' => now()]); }
                DB::table('quote_stock_lots')->where('id', $row->id)->where('quote_id', $quoteId)->delete();
            }
        });
    }

    /** Must execute inside storeFromQuote's transaction before any consumption. */
    public function validateReservationsForSale(int $quoteId): void
    {
        $this->transactionRequired();
        $this->quote($quoteId);
        $rows = $this->rows($quoteId);
        $this->validateRows($rows);
        $details = DB::table('equipment_consumables as c')->join('equipments as e', 'e.id', '=', 'c.equipment_id')
            ->where('e.quote_id', $quoteId)->select('c.id', 'c.stock_item_id', 'c.quantity')->get();
        foreach ($details as $detail) {
            $reserved = '0.00';
            foreach ($rows->where('quote_detail_id', $detail->id) as $row) {
                if ((int) $row->stock_item_id !== (int) $detail->stock_item_id) { $this->invalid('Una reserva no coincide con su detalle.'); }
                $reserved = bcadd($reserved, $this->quantity($row->quantity, true), 2);
            }
            if (bccomp($reserved, $this->quantity($detail->quantity, true), 2) !== 0) { $this->invalid('Las reservas no cubren exactamente la cantidad del detalle '.$detail->id.'.'); }
        }
    }

    /** Lot has already been consumed by storeFromQuote. Preserve unrelated level balances. */
    public function consumeInventoryLevel(int $stock, int $warehouse, int $location, float $units): void
    {
        $this->transactionRequired();
        $quantity = $this->quantity($units, true);
        $this->place($warehouse, $location);
        $lotKey = (object) ['stock_item_id' => $stock, 'warehouse_id' => $warehouse, 'location_id' => $location];
        $level = $this->level($lotKey);
        $onHand = bcsub($this->quantity($level->qty_on_hand), $quantity, 2);
        $reserved = bcsub($this->quantity($level->qty_reserved), $quantity, 2);
        if (bccomp($onHand, '0', 2) < 0 || bccomp($reserved, '0', 2) < 0 || bccomp($reserved, $onHand, 2) > 0) { $this->invalid('El nivel no permite consumir la reserva con seguridad.'); }
        $totals = $this->owned('stock_lots')->where('stock_item_id', $stock)->where('warehouse_id', $warehouse)->where('location_id', $location)
            ->selectRaw('COALESCE(SUM(qty_on_hand),0) AS physical, COALESCE(SUM(qty_reserved),0) AS reserved')->first();
        if (bccomp($onHand, $this->quantity($totals->physical), 2) < 0 || bccomp($reserved, $this->quantity($totals->reserved), 2) < 0) { $this->invalid('El consumo dejaría el nivel por debajo de sus lotes.'); }
        $this->owned('inventory_levels')->where('id', $level->id)->update(['qty_on_hand' => $onHand, 'qty_reserved' => $reserved, 'updated_at' => now()]);
    }

    private function reserve(int $quote, ?int $detail, $lot, string $quantity, array $ids): void
    {
        $level = $this->level($lot);
        if (bccomp(bcsub($this->quantity($lot->qty_on_hand), $this->quantity($lot->qty_reserved), 2), $quantity, 2) < 0
            || bccomp(bcsub($this->quantity($level->qty_on_hand), $this->quantity($level->qty_reserved), 2), $quantity, 2) < 0) { $this->invalid('El lote o nivel ya no tiene disponibilidad suficiente en este local.'); }
        $this->owned('stock_lots')->where('id', $lot->id)->update(['qty_reserved' => bcadd($this->quantity($lot->qty_reserved), $quantity, 2), 'updated_at' => now()]);
        $this->owned('inventory_levels')->where('id', $level->id)->update(['qty_reserved' => bcadd($this->quantity($level->qty_reserved), $quantity, 2), 'updated_at' => now()]);
        DB::table('quote_stock_lots')->insert(['quote_id' => $quote, 'quote_detail_id' => $detail, 'stock_item_id' => $lot->stock_item_id,
            'stock_lot_id' => $lot->id, 'warehouse_id' => $lot->warehouse_id, 'location_id' => $lot->location_id, 'quantity' => $quantity,
            'unit_cost' => $lot->unit_cost, 'item_ids' => $ids ? json_encode(array_values($ids)) : null, 'created_at' => now(), 'updated_at' => now()]);
        if ($ids) { $this->owned('items')->whereIn('id', $ids)->update(['state_item' => 'reserved', 'updated_at' => now()]); }
    }

    private function validateRows($rows): void
    {
        $totals = []; $seen = [];
        foreach ($rows as $row) {
            $this->detail((int) $row->quote_id, $row->quote_detail_id === null ? null : (int) $row->quote_detail_id, (int) $row->stock_item_id);
            $lot = $this->lots((int) $row->stock_item_id)->where('id', $row->stock_lot_id)->lockForUpdate()->first();
            if (!$lot || (int) $lot->warehouse_id !== (int) $row->warehouse_id || (int) $lot->location_id !== (int) $row->location_id) { $this->invalid('Hay reservas fuera de la sucursal o con trazabilidad incompatible. Requieren revisión.'); }
            $this->place($lot->warehouse_id, $lot->location_id);
            $quantity = $this->quantity($row->quantity, true);
            if (bccomp($this->quantity($lot->qty_reserved), $this->quantity($lot->qty_on_hand), 2) > 0) { $this->invalid('Un lote tiene más reservas que existencias.'); }
            $totals[$lot->id] = bcadd($totals[$lot->id] ?? '0', $quantity, 2);
            if (bccomp($this->quantity($lot->qty_reserved), $totals[$lot->id], 2) < 0) { $this->invalid('Las reservas de la cotización exceden la reserva del lote.'); }
            $this->level($lot);
            $ids = $this->ids($row->item_ids);
            $material = DB::table('stock_items as s')->join('materials as m', 'm.id', '=', 's.material_id')
                ->where('s.tenant_id', TenantContext::tenantId())->where('m.tenant_id', TenantContext::tenantId())->where('s.id', $row->stock_item_id)->value('m.tipo_venta_id');
            if ($material === null || ((int) $material === 3 && bccomp((string) count($ids), $quantity, 2) !== 0)
                || ((int) $material !== 3 && $ids)) { $this->invalid('Los Items registrados no coinciden con el tipo de producto o la cantidad reservada.'); }
            foreach ($ids as $id) { if (isset($seen[$id])) { $this->invalid('Un Item figura más de una vez en las reservas.'); } $seen[$id] = true; }
            $this->assertUnclaimed($ids, (int) $row->id);
            if ($ids) {
                $items = $this->owned('items')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if ($items->count() !== count($ids)) { $this->invalid('Faltan Items reservados.'); }
                foreach ($items as $item) { $this->assertItem($item, $lot, 'reserved'); }
            }
        }
    }

    private function assertUnclaimed(array $ids, ?int $except = null): void
    {
        if (!$ids) { return; }
        $lotIds = $this->owned('items')->whereIn('id', $ids)->pluck('stock_lot_id');
        foreach (DB::table('quote_stock_lots')->whereIn('stock_lot_id', $lotIds)->whereNotNull('item_ids')->when($except !== null, function ($q) use ($except) { $q->where('id', '<>', $except); })->get(['item_ids']) as $other) {
            if (array_intersect($ids, $this->ids($other->item_ids))) { $this->invalid('Un Item está vinculado a otra reserva.'); }
        }
        if (DB::table('warehouse_transfer_allocation_items')->whereIn('item_id', $ids)->whereIn('status', ['reserved', 'in_transit'])->exists()) { $this->invalid('Un Item está separado para un traslado.'); }
    }

    private function assertItem($item, $lot, string $state): void
    {
        if ((int) $item->stock_item_id !== (int) $lot->stock_item_id || (int) $item->stock_lot_id !== (int) $lot->id
            || (int) $item->warehouse_id !== (int) $lot->warehouse_id || (int) $item->location_id !== (int) $lot->location_id
            || (float) $item->percentage !== 1.0 || $item->state_item !== $state) { $this->invalid('El Item no conserva el estado, producto, lote y ubicación requeridos.'); }
    }

    private function level($lot)
    {
        $levels = $this->owned('inventory_levels')->where('stock_item_id', $lot->stock_item_id)->where('warehouse_id', $lot->warehouse_id)
            ->where('location_id', $lot->location_id)->lockForUpdate()->get();
        if ($levels->count() !== 1) { $this->invalid('Debe existir un único nivel de inventario para el producto y ubicación.'); }
        $level = $levels->first();
        $physical = $this->quantity($level->qty_on_hand); $reserved = $this->quantity($level->qty_reserved);
        $totals = $this->owned('stock_lots')->where('stock_item_id', $lot->stock_item_id)->where('warehouse_id', $lot->warehouse_id)->where('location_id', $lot->location_id)
            ->selectRaw('COALESCE(SUM(qty_on_hand),0) AS physical, COALESCE(SUM(qty_reserved),0) AS reserved')->first();
        if (bccomp($reserved, $physical, 2) > 0 || bccomp($physical, $this->quantity($totals->physical), 2) < 0 || bccomp($reserved, $this->quantity($totals->reserved), 2) < 0) { $this->invalid('El nivel no cubre los saldos de sus lotes. Requiere conciliación.'); }
        return $level;
    }

    private function quote(int $id): void
    {
        app(QuoteBranchCatalog::class)->validateContext();
        $quote = $this->owned('quotes')->where('id', $id)->lockForUpdate()->first();
        if (!$quote || $quote->branch_id === null || (int) $quote->branch_id !== TenantContext::branchId()) { $this->invalid('La cotización debe tener asignada la sucursal activa de su empresa.'); }
    }

    private function detail(int $quote, ?int $id, ?int $stock): void
    {
        if ($id === null) { $this->invalid('La reserva requiere un detalle de cotización trazable.'); }
        $query = DB::table('equipment_consumables as c')->join('equipments as e', 'e.id', '=', 'c.equipment_id')->where('c.id', $id)->where('e.quote_id', $quote);
        if ($stock !== null) { $query->where('c.stock_item_id', $stock); }
        if (!$query->exists()) { $this->invalid('El detalle no pertenece a la cotización o al producto.'); }
    }

    private function capacity(int $quote, int $detail, string $additional): void
    {
        $requested = DB::table('equipment_consumables')->where('id', $detail)->value('quantity');
        $existing = DB::table('quote_stock_lots')->where('quote_id', $quote)->where('quote_detail_id', $detail)->sum('quantity');
        if (bccomp(bcadd($this->quantity($existing), $additional, 2), $this->quantity($requested, true), 2) > 0) { $this->invalid('La reserva supera la cantidad cotizada del detalle.'); }
    }

    private function product(int $id)
    {
        $product = DB::table('stock_items as s')->join('materials as m', 'm.id', '=', 's.material_id')
            ->where('s.tenant_id', TenantContext::tenantId())->where('m.tenant_id', TenantContext::tenantId())->whereNull('m.deleted_at')
            ->where('s.id', $id)->where('s.is_active', true)->where('s.tracks_inventory', true)->where('m.enable_status', 1)
            ->whereExists(function ($q) { $q->selectRaw('1')->from('company_stock_items as c')->whereColumn('c.stock_item_id', 's.id')
                ->where('c.tenant_id', TenantContext::tenantId())->where('c.company_id', TenantContext::companyId())->where('c.is_active', true); })
            ->first(['s.id', 'm.tipo_venta_id']);
        if (!$product) { $this->invalid('El producto no está habilitado para inventario en esta empresa.'); }
        return $product;
    }

    private function place(int $warehouse, int $location): void
    {
        if (!$this->owned('warehouses')->where('id', $warehouse)->where('branch_id', TenantContext::branchId())->exists()
            || !$this->owned('locations')->where('id', $location)->where('warehouse_id', $warehouse)->exists()) { $this->invalid('El almacén y ubicación deben pertenecer al local de la cotización.'); }
    }

    private function lots(int $stock)
    {
        return $this->owned('stock_lots')->where('stock_item_id', $stock)->whereIn('warehouse_id', app(QuoteBranchCatalog::class)->warehouses());
    }
    private function orderedLots(int $stock) { return $this->lots($stock)->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')->orderBy('expiration_date')->orderBy('id'); }
    private function rows(int $quote) { return DB::table('quote_stock_lots')->where('quote_id', $quote)->orderBy('stock_lot_id')->orderBy('id')->lockForUpdate()->get(); }
    private function owned(string $table) { return DB::table($table)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId()); }
    private function transactionRequired(): void { if (DB::transactionLevel() < 1) { throw new \LogicException('Esta operación requiere la transacción de la venta.'); } }
    private function ids($values): array
    {
        if ($values === null) { return []; }
        if (is_string($values)) { $values = json_decode($values, true); }
        if (!is_array($values)) { $this->invalid('La lista de Items es inválida.'); }
        $result = [];
        foreach ($values as $id) {
            if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id) || isset($result[(string) $id])) { $this->invalid('La lista contiene Items inválidos o duplicados.'); }
            $result[(string) $id] = (int) $id;
        }
        return array_values($result);
    }
    private function quantity($value, bool $positive = false): string
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 9999999999.99 || abs((float) $value - round((float) $value, 2)) > 0.000001) { $this->invalid('Cantidad inválida: se admiten hasta dos decimales.'); }
        $number = number_format((float) $value, 2, '.', '');
        if ($positive && bccomp($number, '0', 2) <= 0) { $this->invalid('La cantidad debe ser mayor que cero.'); }
        return $number;
    }
    private function invalid(string $message): void { throw ValidationException::withMessages(['stock' => $message]); }
}

