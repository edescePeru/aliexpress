<?php
namespace App\Services\Transfers;

use App\Support\TenantContext;
use App\Transfers\{WarehouseTransfer as Transfer, WarehouseTransferAllocation as Allocation, WarehouseTransferAllocationItem as AllocationItem};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class WarehouseTransferPreparationService
{
    private $costs;
    public function __construct(WarehouseTransferCostResolver $costs) { $this->costs = $costs; }

    public function prepare(int $id): Transfer
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->transfer($id);
            if ($transfer->status === Transfer::PREPARED) { return $transfer->load('lines.allocations.items'); }
            if ($transfer->status !== Transfer::REQUESTED) { $this->invalid('Solo se puede preparar una solicitud pendiente.'); }
            $lines = $transfer->lines()->orderBy('stock_item_id')->lockForUpdate()->get();
            if ($lines->isEmpty() || $lines->contains(function ($line) { return !$line->is_active; })) {
                $this->invalid('La solicitud debe tener productos vigentes.');
            }
            foreach ($lines as $line) {
                if ($line->allocations()->exists()) { $this->invalid('La solicitud pendiente ya tiene asignaciones. Requiere revisiÃ³n.'); }
                $enabled = $this->owned('company_stock_items')->where('stock_item_id', $line->stock_item_id)->where('is_active', true)->exists();
                $stock = DB::table('stock_items')->where('tenant_id', TenantContext::tenantId())->where('id', $line->stock_item_id)->where('is_active', true)->first();
                if (!$enabled || !$stock) { $this->invalid('Un producto ya no estÃ¡ habilitado en la empresa.'); }
                $material = DB::table('materials')->where('tenant_id', TenantContext::tenantId())->where('id', $stock->material_id)->whereNull('deleted_at')->first();
                if (!$material || ((int) $material->tipo_venta_id === 3) !== (bool) $line->tracks_items) { $this->invalid('CambiÃ³ el control de unidades del producto. Requiere revisiÃ³n.'); }
                // Bloquear lotes antes que niveles, igual que la reserva de cotizaciones.
                $lots = $this->origin('stock_lots', $transfer, $line->stock_item_id)
                    ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('expiration_date')->orderBy('id')->lockForUpdate()->get();
                $level = $this->level($transfer, $line->stock_item_id);
                $lotReserved = '0.00'; $lotOnHand = '0.00';
                foreach ($lots as $lot) {
                    $lotReserved = bcadd($lotReserved, $this->quantity($lot->qty_reserved), 2);
                    $lotOnHand = bcadd($lotOnHand, $this->quantity($lot->qty_on_hand), 2);
                }
                if (bccomp($this->quantity($level->qty_reserved), $lotReserved, 2) < 0
                    || bccomp($this->quantity($level->qty_on_hand), $lotOnHand, 2) < 0) {
                    $this->invalid('El nivel de inventario no cubre los saldos de sus lotes. Requiere conciliación.');
                }
                $remaining = $this->quantity($line->requested_quantity);
                if (bccomp(bcsub($this->quantity($level->qty_on_hand), $this->quantity($level->qty_reserved), 2), $remaining, 2) < 0) {
                    $this->invalid('El nivel de inventario no tiene disponibilidad suficiente.');
                }
                foreach ($lots as $lot) {
                    $onHand = $this->quantity($lot->qty_on_hand); $reserved = $this->quantity($lot->qty_reserved);
                    if (bccomp($reserved, $onHand, 2) > 0) { $this->invalid('Un lote tiene reservas inconsistentes.'); }
                    $available = bcsub($onHand, $reserved, 2);
                    if (bccomp($remaining, '0', 2) === 0 || bccomp($available, '0', 2) === 0) { continue; }
                    $take = bccomp($available, $remaining, 2) < 0 ? $available : $remaining;
                    if ($line->tracks_items && bccomp($take, (string) (int) $take, 2) !== 0) { $this->invalid('El lote unitario tiene cantidades fraccionadas.'); }
                    $allocation = new Allocation();
                    $allocation->forceFill(array_merge($this->costs->resolve($lot), ['transfer_line_id' => $line->id, 'source_stock_lot_id' => $lot->id, 'quantity' => $take]));
                    $allocation->save();
                    if ($line->tracks_items) {
                        $items = $this->origin('items', $transfer, $line->stock_item_id)->where('stock_lot_id', $lot->id)
                            ->where('state_item', 'entered')->orderBy('id')->limit((int) $take)->lockForUpdate()->get();
                        if ($items->count() !== (int) $take) { $this->invalid('Las unidades disponibles no coinciden con el lote.'); }
                        foreach ($items as $item) {
                            if ($this->owned('warehouse_transfer_allocation_items')->where('item_id', $item->id)->whereIn('status', ['reserved', 'in_transit'])->exists()) {
                                $this->invalid('Una unidad ya estÃ¡ separada para otro traslado.');
                            }
                            $link = new AllocationItem(); $link->allocation_id = $allocation->id; $link->item_id = $item->id; $link->save();
                            $this->owned('items')->where('id', $item->id)->update(['state_item' => 'reserved', 'updated_at' => now()]);
                        }
                    }
                    $this->owned('stock_lots')->where('id', $lot->id)->update(['qty_reserved' => bcadd($reserved, $take, 2), 'updated_at' => now()]);
                    $remaining = bcsub($remaining, $take, 2);
                }
                if (bccomp($remaining, '0', 2) !== 0) { $this->invalid('Los lotes del almacÃ©n de origen no cubren lo solicitado.'); }
                // Sumar el aporte exacto; conservar inventario y reservas histÃ³ricos ajenos.
                $this->owned('inventory_levels')->where('id', $level->id)->update([
                    'qty_reserved' => bcadd($this->quantity($level->qty_reserved), $line->requested_quantity, 2), 'updated_at' => now()]);
            }
            $transfer->status = Transfer::PREPARED; $transfer->prepared_by = Auth::id(); $transfer->prepared_at = now(); $transfer->save();
            return $transfer->load('lines.allocations.items');
        }, 3);
    }

    public function cancel(int $id, string $reason): Transfer
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) { $this->invalid('Indique un motivo de cancelaciÃ³n de hasta 2000 caracteres.'); }
        return DB::transaction(function () use ($id, $reason) {
            $transfer = $this->transfer($id);
            if ($transfer->status === Transfer::CANCELLED) { return $transfer; }
            if (!in_array($transfer->status, [Transfer::REQUESTED, Transfer::PREPARED], true)) { $this->invalid('Un traslado despachado requiere devoluciÃ³n; no se puede cancelar.'); }
            foreach ($transfer->lines()->orderBy('stock_item_id')->lockForUpdate()->get() as $line) {
                $allocations = $line->allocations()->orderBy('source_stock_lot_id')->lockForUpdate()->get();
                if ($transfer->status === Transfer::REQUESTED && $allocations->isNotEmpty()) { $this->invalid('La solicitud pendiente tiene asignaciones inesperadas.'); }
                $assigned = '0.00';
                foreach ($allocations as $row) { $assigned = bcadd($assigned, $this->quantity($row->quantity), 2); }
                if ($transfer->status === Transfer::PREPARED && bccomp($assigned, $line->requested_quantity, 2) !== 0) { $this->invalid('Las asignaciones no cubren lo preparado.'); }
                $lots = $this->origin('stock_lots', $transfer, $line->stock_item_id)->whereIn('id', $allocations->pluck('source_stock_lot_id'))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $level = $allocations->isEmpty() ? null : $this->level($transfer, $line->stock_item_id);
                $released = '0.00';
                foreach ($allocations as $allocation) {
                    $lot = $lots->get($allocation->source_stock_lot_id);
                    if (!$lot || $allocation->status !== 'reserved' || $allocation->receiptLines()->exists()) { $this->invalid('La asignaciÃ³n no permite liberar la reserva.'); }
                    $quantity = $this->quantity($allocation->quantity);
                    if (bccomp($this->quantity($lot->qty_reserved), $quantity, 2) < 0) { $this->invalid('El lote no conserva la reserva del traslado.'); }
                    $links = $allocation->items()->orderBy('item_id')->lockForUpdate()->get();
                    if (($line->tracks_items && bccomp((string) $links->count(), $quantity, 2) !== 0) || (!$line->tracks_items && $links->isNotEmpty())) { $this->invalid('Las unidades asignadas no coinciden con la reserva.'); }
                    foreach ($links as $link) {
                        $item = $this->origin('items', $transfer, $line->stock_item_id)->where('stock_lot_id', $lot->id)->where('id', $link->item_id)->lockForUpdate()->first();
                        if (!$item || $item->state_item !== 'reserved' || $link->status !== 'reserved' || $link->receiptItem()->exists()) { $this->invalid('Una unidad ya no conserva su reserva de traslado.'); }
                        $this->owned('items')->where('id', $item->id)->update(['state_item' => 'entered', 'updated_at' => now()]);
                        $link->status = 'cancelled'; $link->save();
                    }
                    $this->owned('stock_lots')->where('id', $lot->id)->update(['qty_reserved' => bcsub($this->quantity($lot->qty_reserved), $quantity, 2), 'updated_at' => now()]);
                    $allocation->status = 'cancelled'; $allocation->save(); $released = bcadd($released, $quantity, 2);
                }
                if ($level) {
                    $reserved = $this->quantity($level->qty_reserved);
                    if (bccomp($reserved, $released, 2) < 0) { $this->invalid('El nivel no conserva la reserva del traslado.'); }
                    $this->owned('inventory_levels')->where('id', $level->id)->update(['qty_reserved' => bcsub($reserved, $released, 2), 'updated_at' => now()]);
                }
            }
            $transfer->status = Transfer::CANCELLED; $transfer->cancelled_by = Auth::id(); $transfer->cancelled_at = now();
            $transfer->cancellation_reason = $reason; $transfer->save(); return $transfer;
        }, 3);
    }

    private function transfer(int $id): Transfer
    {
        if (!Auth::id() || !DB::table('users')->where('id', Auth::id())->where('tenant_id', TenantContext::tenantId())->exists()) { $this->invalid('Se requiere un usuario del tenant actual.'); }
        $transfer = Transfer::query()->lockForUpdate()->find($id);
        if (!$transfer) { $this->invalid('El traslado no estÃ¡ disponible en la empresa actual.'); }
        // Fuerza las validaciones de identidad/almacenes del modelo antes de operar.
        $transfer->save(); return $transfer;
    }
    private function owned(string $table)
    {
        return DB::table($table)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
    }
    private function origin(string $table, Transfer $transfer, int $stock)
    {
        return $this->owned($table)->where('stock_item_id', $stock)->where('warehouse_id', $transfer->source_warehouse_id)->where('location_id', $transfer->source_location_id);
    }
    private function level(Transfer $transfer, int $stock)
    {
        $levels = $this->origin('inventory_levels', $transfer, $stock)->orderBy('id')->lockForUpdate()->get();
        if ($levels->count() !== 1) { $this->invalid('El origen requiere un Ãºnico nivel de inventario verificable.'); }
        return $levels->first();
    }
    private function quantity($value): string
    {
        if (!is_scalar($value) || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', (string) $value)) { $this->invalid('Las cantidades de inventario requieren conciliaciÃ³n.'); }
        return bcadd((string) $value, '0', 2);
    }
    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }
}
