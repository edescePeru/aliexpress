<?php
namespace App\Services\Transfers;

use App\Services\{InventoryCostService, SettingService};
use App\Support\TenantContext;
use App\Transfers\{WarehouseTransfer as Transfer, WarehouseTransferReceipt as Receipt, WarehouseTransferReceiptLine as ReceiptLine, WarehouseTransferReceiptItem as ReceiptItem};
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class WarehouseTransferMovementService
{
    public function summary(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $transfer = Transfer::query()->find($id);
            if (!$transfer) { $this->invalid('El traslado no está disponible en la empresa actual.'); }
            $rows = [];
            foreach ($transfer->lines()->orderBy('id')->get() as $line) {
                $reserved = '0.00'; $dispatched = '0.00'; $received = '0.00';
                foreach ($line->allocations()->orderBy('id')->get() as $allocation) {
                    if ($allocation->status === 'reserved') { $reserved = bcadd($reserved, $allocation->quantity, 2); }
                    if ($allocation->status === 'dispatched') { $dispatched = bcadd($dispatched, $allocation->quantity, 2); }
                    $received = bcadd($received, $this->receivedQuantity($allocation), 2);
                }
                if (bccomp($received, $dispatched, 2) > 0) { $this->invalid('El traslado registra cantidades inconsistentes.'); }
                $rows[] = ['transfer_line_id' => $line->id, 'stock_item_id' => $line->stock_item_id,
                    'sku' => $line->sku_snapshot, 'description' => $line->description_snapshot,
                    'requested' => $line->requested_quantity, 'reserved' => $reserved,
                    'dispatched' => $dispatched, 'received' => $received,
                    'in_transit' => bcsub($dispatched, $received, 2),
                    'pending_receipt' => $transfer->status === Transfer::CANCELLED ? '0.00' : bcsub($line->requested_quantity, $received, 2)];
            }
            return ['id' => $transfer->id, 'code' => $transfer->code, 'status' => $transfer->status, 'lines' => $rows];
        });
    }

    public function dispatch(int $id): Transfer
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->lockedTransfer($id);
            if (in_array($transfer->status, [Transfer::DISPATCHED, Transfer::PARTIALLY_RECEIVED, Transfer::RECEIVED], true)) {
                return $transfer; // Un reintento nunca descuenta por segunda vez.
            }
            if ($transfer->status !== Transfer::PREPARED) { $this->invalid('Solo se puede despachar un traslado preparado.'); }
            if ($transfer->receipts()->exists()) { $this->invalid('El traslado preparado ya tiene recepciones inesperadas.'); }
            $lines = $transfer->lines()->orderBy('stock_item_id')->lockForUpdate()->get();
            if ($lines->isEmpty()) { $this->invalid('El traslado no tiene productos.'); }
            foreach ($lines as $line) {
                if (!$line->is_active) { $this->invalid('El traslado contiene una línea retirada.'); }
                $allocations = $line->allocations()->orderBy('source_stock_lot_id')->lockForUpdate()->get();
                $assigned = '0.00';
                foreach ($allocations as $a) { $assigned = bcadd($assigned, $this->quantity($a->quantity, false), 2); }
                if (bccomp($assigned, $line->requested_quantity, 2) !== 0) { $this->invalid('Las asignaciones no cubren toda la solicitud.'); }
                $lots = $this->origin('stock_lots', $transfer, $line->stock_item_id)->whereIn('id', $allocations->pluck('source_stock_lot_id'))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $levels = $this->origin('inventory_levels', $transfer, $line->stock_item_id)->orderBy('id')->lockForUpdate()->get();
                if ($levels->count() !== 1) { $this->invalid('El origen requiere un único nivel de inventario.'); }
                $level = $levels->first();
                foreach ($allocations as $allocation) {
                    $lot = $lots->get($allocation->source_stock_lot_id);
                    if (!$lot || $allocation->status !== 'reserved' || $allocation->receiptLines()->exists()) { $this->invalid('Una asignación no conserva su reserva de origen.'); }
                    $quantity = $this->quantity($allocation->quantity, false);
                    $balances = $this->subtractReserved($lot, $quantity);
                    $links = $allocation->items()->orderBy('item_id')->lockForUpdate()->get();
                    if (($line->tracks_items && bccomp((string) $links->count(), $quantity, 2) !== 0)
                        || (!$line->tracks_items && $links->isNotEmpty())) { $this->invalid('Las unidades no coinciden con la asignación.'); }
                    foreach ($links as $link) {
                        $item = $this->origin('items', $transfer, $line->stock_item_id)->where('stock_lot_id', $lot->id)
                            ->where('id', $link->item_id)->lockForUpdate()->first();
                        if (!$item || $item->state_item !== 'reserved' || $link->status !== 'reserved' || $link->receiptItem()->exists()) { $this->invalid('Una unidad no conserva su reserva.'); }
                        $this->owned('items')->where('id', $item->id)->update(['state_item' => 'in_transit', 'updated_at' => now()]);
                        $link->status = 'in_transit'; $link->save();
                    }
                    $this->owned('stock_lots')->where('id', $lot->id)->update($balances + ['updated_at' => now()]);
                    $allocation->status = 'dispatched'; $allocation->save();
                }
                $this->owned('inventory_levels')->where('id', $level->id)->update($this->subtractReserved($level, $assigned) + ['updated_at' => now()]);
            }
            $transfer->status = Transfer::DISPATCHED; $transfer->dispatched_by = Auth::id(); $transfer->dispatched_at = now(); $transfer->save();
            return $transfer;
        }, 3);
    }

    public function receive(int $id, array $data): Receipt
    {
        $payload = $this->normalize($data);
        try {
            return DB::transaction(function () use ($id, $payload) {
                $transfer = $this->lockedTransfer($id);
                $hash = hash('sha256', json_encode([TenantContext::tenantId(), TenantContext::companyId(), $id, (int) Auth::id(), $payload], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $previous = DB::table('warehouse_transfer_receipts')->where('request_key', $payload['request_key'])->first();
                if ($previous) {
                    if ((int) $previous->tenant_id !== TenantContext::tenantId() || (int) $previous->company_id !== TenantContext::companyId()
                        || (int) $previous->transfer_id !== $id || !hash_equals((string) $previous->request_hash, $hash)) {
                        $this->invalid('La clave de recepción ya fue utilizada para otra operación.');
                    }
                    return Receipt::query()->with('lines.items')->findOrFail($previous->id);
                }
                if (!in_array($transfer->status, [Transfer::DISPATCHED, Transfer::PARTIALLY_RECEIVED], true)) { $this->invalid('Solo se recibe mercancía despachada y pendiente.'); }
                $lines = $transfer->lines()->orderBy('stock_item_id')->lockForUpdate()->get()->keyBy('id');
                foreach ($payload['lines'] as $row) {
                    if (!$lines->has($row['transfer_line_id']) || !$lines->get($row['transfer_line_id'])->is_active) { $this->invalid('Un producto no pertenece al traslado.'); }
                }
                $base = strtoupper((string) app(SettingService::class)->get('finance.base_currency'));
                $receipt = new Receipt(['notes' => $payload['notes']]); $receipt->transfer_id = $id;
                $receipt->request_key = $payload['request_key']; $receipt->request_hash = $hash; $receipt->save();
                foreach ($payload['lines'] as $row) {
                    $line = $lines->get($row['transfer_line_id']);
                    $remaining = $row['quantity'];
                    if ($line->tracks_items && bccomp($remaining, (string) (int) $remaining, 2) !== 0) { $this->invalid('Las unidades requieren cantidades enteras.'); }
                    $allocations = $line->allocations()->orderBy('id')->lockForUpdate()->get();
                    foreach ($allocations as $allocation) {
                        if ($allocation->status !== 'dispatched' || $allocation->base_currency !== $base) { $this->invalid('La asignación o su moneda base requieren revisión.'); }
                        $received = $this->receivedQuantity($allocation);
                        $available = bcsub($this->quantity($allocation->quantity, false), $received, 2);
                        if (bccomp($available, '0', 2) < 0) { $this->invalid('La asignación ya registra una recepción excesiva.'); }
                        if (bccomp($remaining, '0', 2) === 0 || bccomp($available, '0', 2) === 0) { continue; }
                        $take = bccomp($available, $remaining, 2) < 0 ? $available : $remaining;
                        $source = $this->origin('stock_lots', $transfer, $line->stock_item_id)->where('id', $allocation->source_stock_lot_id)->lockForUpdate()->first();
                        if (!$source) { $this->invalid('El lote original no conserva su trazabilidad.'); }
                        $lotId = $this->ownedInsert('stock_lots', ['stock_item_id' => $line->stock_item_id,
                            'warehouse_id' => $transfer->destination_warehouse_id, 'location_id' => $transfer->destination_location_id,
                            'detail_entry_id' => null, 'lot_code' => $source->lot_code, 'expiration_date' => $source->expiration_date,
                            'qty_on_hand' => $take, 'qty_reserved' => '0.00', 'unit_cost' => $allocation->source_unit_cost]);
                        $receiptLine = new ReceiptLine();
                        $receiptLine->forceFill(['receipt_id' => $receipt->id, 'allocation_id' => $allocation->id, 'destination_stock_lot_id' => $lotId, 'quantity' => $take]); $receiptLine->save();
                        if ($line->tracks_items) {
                            $links = $allocation->items()->where('status', 'in_transit')->orderBy('item_id')->limit((int) $take)->lockForUpdate()->get();
                            if ($links->count() !== (int) $take) { $this->invalid('Faltan unidades verificables en tránsito.'); }
                            foreach ($links as $link) {
                                $item = $this->origin('items', $transfer, $line->stock_item_id)->where('stock_lot_id', $source->id)->where('id', $link->item_id)->lockForUpdate()->first();
                                if (!$item || $item->state_item !== 'in_transit' || $link->receiptItem()->exists()) { $this->invalid('Una unidad ya fue recibida o cambió de ubicación.'); }
                                $receivedItem = new ReceiptItem(); $receivedItem->receipt_line_id = $receiptLine->id; $receivedItem->allocation_item_id = $link->id; $receivedItem->save();
                                // Conserva ID, código, compra original, precio y costo del Item.
                                $this->owned('items')->where('id', $item->id)->update(['stock_lot_id' => $lotId,
                                    'warehouse_id' => $transfer->destination_warehouse_id, 'location_id' => $transfer->destination_location_id,
                                    'state_item' => 'entered', 'updated_at' => now()]);
                                $link->status = 'received'; $link->save();
                            }
                        } elseif ($allocation->items()->exists()) { $this->invalid('Un producto por cantidad tiene unidades inesperadas.'); }
                        $remaining = bcsub($remaining, $take, 2);
                    }
                    if (bccomp($remaining, '0', 2) !== 0) { $this->invalid('La cantidad recibida supera lo pendiente del producto.'); }
                    $this->increaseDestination($transfer, $line->stock_item_id, $row['quantity']);
                }
                $complete = true;
                foreach ($lines as $line) {
                    $dispatched = '0.00'; $received = '0.00';
                    foreach ($line->allocations()->orderBy('id')->get() as $allocation) {
                        if ($allocation->status !== 'dispatched') { $this->invalid('Hay asignaciones que no fueron despachadas.'); }
                        $dispatched = bcadd($dispatched, $allocation->quantity, 2);
                        $received = bcadd($received, $this->receivedQuantity($allocation), 2);
                    }
                    if (bccomp($dispatched, $line->requested_quantity, 2) !== 0 || bccomp($received, $dispatched, 2) > 0) { $this->invalid('Las cantidades del traslado requieren revisión.'); }
                    if (bccomp($received, $dispatched, 2) < 0) { $complete = false; }
                }
                $transfer->status = $complete ? Transfer::RECEIVED : Transfer::PARTIALLY_RECEIVED;
                if ($complete) { $transfer->completed_at = now(); }
                $transfer->save(); return $receipt->load('lines.items');
            }, 3);
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'wtr_request_key_uq') !== false) { $this->invalid('La clave de recepción ya fue utilizada.'); }
            throw $e;
        }
    }

    private function increaseDestination(Transfer $transfer, int $stock, string $quantity): void
    {
        $rows = $this->owned('inventory_levels')->where('stock_item_id', $stock)->where('warehouse_id', $transfer->destination_warehouse_id)
            ->where('location_id', $transfer->destination_location_id)->orderBy('id')->lockForUpdate()->get();
        if ($rows->count() > 1) { $this->invalid('El destino tiene niveles de inventario duplicados.'); }
        $averages = app(InventoryCostService::class)->getAverageCostsByStockItem([$stock]);
        if (!isset($averages[$stock]) || !is_numeric($averages[$stock]) || !is_finite((float) $averages[$stock]) || $averages[$stock] < 0) { $this->invalid('No se puede verificar el costo promedio de la empresa.'); }
        $average = number_format((float) $averages[$stock], 2, '.', '');
        if (strlen(strtok($average, '.')) > 10) { $this->invalid('El costo promedio excede la capacidad del nivel de inventario.'); }
        if ($rows->isEmpty()) {
            $this->ownedInsert('inventory_levels', ['stock_item_id' => $stock, 'warehouse_id' => $transfer->destination_warehouse_id,
                'location_id' => $transfer->destination_location_id, 'qty_on_hand' => $quantity, 'qty_reserved' => '0.00',
                'min_alert' => 0, 'max_alert' => 0, 'average_cost' => $average, 'last_cost' => 0]);
        } else {
            $level = $rows->first(); $onHand = $this->quantity($level->qty_on_hand); $reserved = $this->quantity($level->qty_reserved);
            if (bccomp($reserved, $onHand, 2) > 0) { $this->invalid('El destino tiene saldos inconsistentes.'); }
            $total = $this->quantity(bcadd($onHand, $quantity, 2));
            $this->owned('inventory_levels')->where('id', $level->id)->update(['qty_on_hand' => $total, 'average_cost' => $average, 'updated_at' => now()]);
        }
    }

    private function lockedTransfer(int $id): Transfer
    {
        if (!Auth::id() || !DB::table('users')->where('id', Auth::id())->where('tenant_id', TenantContext::tenantId())->exists()) { $this->invalid('Se requiere un usuario del tenant actual.'); }
        // Serializa también recepciones simultáneas hacia un nivel todavía inexistente.
        if (!DB::table('companies')->where('id', TenantContext::companyId())->where('tenant_id', TenantContext::tenantId())->lockForUpdate()->first()) { $this->invalid('La empresa no está disponible.'); }
        $transfer = Transfer::query()->lockForUpdate()->find($id);
        if (!$transfer) { $this->invalid('El traslado no está disponible en la empresa actual.'); }
        $warehouses = $this->owned('warehouses')->whereIn('id', [$transfer->source_warehouse_id, $transfer->destination_warehouse_id])
            ->orderBy('id')->lockForUpdate()->get();
        if ($warehouses->count() !== 2) { $this->invalid('Los almacenes del traslado requieren revisión.'); }
        foreach ($warehouses as $warehouse) {
            $locations = $this->owned('locations')->where('warehouse_id', $warehouse->id)->orderBy('id')->lockForUpdate()->get();
            if ($locations->count() !== 1) { $this->invalid('Cada almacén debe conservar una ubicación operativa.'); }
        }
        $transfer->save(); return $transfer;
    }
    private function receivedQuantity($allocation): string
    {
        $quantity = '0.00';
        foreach ($allocation->receiptLines()->orderBy('id')->get() as $line) { $quantity = bcadd($quantity, $this->quantity($line->quantity, false), 2); }
        return $quantity;
    }
    private function subtractReserved($row, string $quantity): array
    {
        $onHand = $this->quantity($row->qty_on_hand); $reserved = $this->quantity($row->qty_reserved);
        if (bccomp($reserved, $onHand, 2) > 0 || bccomp($quantity, $reserved, 2) > 0) { $this->invalid('El origen no conserva las existencias y reservas necesarias.'); }
        return ['qty_on_hand' => bcsub($onHand, $quantity, 2), 'qty_reserved' => bcsub($reserved, $quantity, 2)];
    }
    private function owned(string $table)
    {
        return DB::table($table)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
    }
    private function origin(string $table, Transfer $transfer, int $stock)
    {
        return $this->owned($table)->where('stock_item_id', $stock)->where('warehouse_id', $transfer->source_warehouse_id)->where('location_id', $transfer->source_location_id);
    }
    private function ownedInsert(string $table, array $row): int
    {
        return DB::table($table)->insertGetId($row + ['tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId(), 'created_at' => now(), 'updated_at' => now()]);
    }
    private function normalize(array $data): array
    {
        $key = $data['request_key'] ?? null; $notes = $data['notes'] ?? null;
        if (!is_string($key) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $key)) { $this->invalid('La recepción requiere una clave UUID conservada al reintentar.'); }
        if ($notes !== null && (!is_string($notes) || mb_strlen($notes) > 2000)) { $this->invalid('La observación admite hasta 2000 caracteres.'); }
        if (empty($data['lines']) || !is_array($data['lines']) || count($data['lines']) > 500) { $this->invalid('Indique entre 1 y 500 productos recibidos.'); }
        $lines = [];
        foreach ($data['lines'] as $row) {
            $id = is_array($row) ? ($row['transfer_line_id'] ?? null) : null;
            if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string) $id) || isset($lines[(int) $id])) { $this->invalid('La línea no es válida o está repetida.'); }
            $lines[(int) $id] = ['transfer_line_id' => (int) $id, 'quantity' => $this->quantity($row['quantity'] ?? null, false)];
        }
        ksort($lines, SORT_NUMERIC); $notes = $notes === null ? null : trim($notes);
        return ['request_key' => strtolower($key), 'notes' => $notes === '' ? null : $notes, 'lines' => array_values($lines)];
    }
    private function quantity($value, bool $zero = true): string
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', (string) $value)
            || (!$zero && bccomp((string) $value, '0', 2) <= 0)) { $this->invalid('La cantidad debe ser válida, con hasta dos decimales y positiva al recibir.'); }
        return bcadd((string) $value, '0', 2);
    }
    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }
}
