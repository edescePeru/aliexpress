<?php

namespace App\Services\Inventory;

use App\{Entry, DetailEntry, InventoryLevel, Item, Location, Material, StockLot, Warehouse};
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EntryStockReversalService
{
    public function reverse(Entry $entry, $details): array
    {
        if (DB::transactionLevel() === 0) { throw new \LogicException('La reversión exige una transacción.'); }
        $tenant = TenantContext::tenantId();
        $company = TenantContext::companyId();
        if ((int) $entry->tenant_id !== $tenant || (int) $entry->company_id !== $company) {
            $this->invalid('El ingreso no pertenece al contexto actual.');
        }
        if ($entry->entry_type !== 'Por compra') { $this->invalid('El registro no es un ingreso por compra.'); }
        $order = null;
        if ($entry->order_purchase_id) {
            $order = \App\OrderPurchase::query()->where('type', 'n')->lockForUpdate()->find($entry->order_purchase_id);
            if (!$order || (string) $order->code !== (string) $entry->purchase_order || trim((string) $order->code) === '') {
                $this->invalid('El ingreso no tiene una relación verificable con la orden actual.');
            }
        } elseif (trim((string) $entry->purchase_order) !== '') {
            $this->invalid('El ingreso tiene una referencia histórica sin vínculo exacto con una orden. Requiere conciliación.');
        }
        $plans = []; $keys = []; $affected = [];
        foreach ($details as $detail) {
            if ((int) $detail->tenant_id !== $tenant || (int) $detail->company_id !== $company
                || (int) $detail->entry_id !== (int) $entry->id || !$detail->stock_item_id) {
                $this->invalid('El detalle no tiene una identidad de inventario verificable. Requiere conciliación.');
            }
            if ($order) {
                $line = \App\OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)
                    ->lockForUpdate()->find($detail->order_purchase_detail_id);
                if (!$line || (int) $line->material_id !== (int) $detail->material_id
                    || (string) $line->stock_item_id !== (string) $detail->stock_item_id) {
                    $this->invalid('El detalle no corresponde a una línea vigente de la orden.');
                }
            } elseif ($detail->order_purchase_detail_id) {
                $this->invalid('El detalle tiene una referencia de orden sin encabezado verificable.');
            }
            $qty = $this->decimal($detail->entered_quantity);
            if (bccomp($qty, '0', 10) <= 0) { $this->invalid('La cantidad original del detalle no es válida.'); }
            $lots = StockLot::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->where('detail_entry_id', $detail->id)->orderBy('id')->lockForUpdate()->get();
            if ($lots->isEmpty()) {
                $this->invalid('El detalle #' . $detail->id . ' no tiene lotes de origen verificables. No se descontará de otro almacén; requiere conciliación histórica.');
            }
            // Conservar el origen de cualquier traslado, incluso cancelado.
            if ((Schema::hasTable('warehouse_transfer_allocations') && DB::table('warehouse_transfer_allocations')
                    ->whereIn('source_stock_lot_id', $lots->pluck('id')->all())->exists())
                || (Schema::hasTable('warehouse_transfer_receipt_lines') && DB::table('warehouse_transfer_receipt_lines')
                    ->whereIn('destination_stock_lot_id', $lots->pluck('id')->all())->exists())) {
                $this->invalid('Un lote del ingreso tiene historial de traslado. No se puede eliminar; requiere una reversión que conserve su trazabilidad.');
            }
            $items = Item::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->where('detail_entry_id', $detail->id)->orderBy('id')->lockForUpdate()->get();
            $sum = '0';
            foreach ($lots as $lot) {
                if ((int) $lot->stock_item_id !== (int) $detail->stock_item_id || !$lot->warehouse_id || !$lot->location_id) {
                    $this->invalid('Un lote no corresponde al producto o no identifica su almacén y ubicación.');
                }
                $onHand = $this->decimal($lot->qty_on_hand);
                if (bccomp($onHand, '0', 10) < 0 || bccomp($this->decimal($lot->qty_reserved), '0', 10) !== 0) {
                    $this->invalid('Un lote tiene reservas o cantidades inconsistentes.');
                }
                $sum = bcadd($sum, $onHand, 10);
                $key = implode(':', [$lot->stock_item_id, $lot->warehouse_id, $lot->location_id]);
                if (!isset($keys[$key])) {
                    $keys[$key] = ['stock_item_id' => (int) $lot->stock_item_id,
                        'warehouse_id' => (int) $lot->warehouse_id, 'location_id' => (int) $lot->location_id, 'qty' => '0'];
                }
                $keys[$key]['qty'] = bcadd($keys[$key]['qty'], $onHand, 10);
            }
            if (bccomp($sum, $qty, 10) !== 0) {
                $this->invalid('El stock restante no coincide con el ingreso original: hubo consumo o requiere conciliación.');
            }
            if (Schema::hasColumn('output_details', 'stock_lot_id') && DB::table('output_details')
                ->whereIn('stock_lot_id', $lots->pluck('id')->all())->exists()) {
                $this->invalid('Un lote del ingreso está relacionado con una salida. No se puede eliminar.');
            }
            if (Schema::hasTable('quote_stock_lots') && DB::table('quote_stock_lots')
                ->whereIn('stock_lot_id', $lots->pluck('id')->all())->orderBy('id')->lockForUpdate()->get()->isNotEmpty()) {
                $this->invalid('Un lote mantiene una referencia de reserva de cotización. Requiere liberación o conciliación.');
            }
            foreach ($items as $item) {
                $lot = $lots->firstWhere('id', $item->stock_lot_id);
                if ($item->state_item !== 'entered' || !$lot
                    || (int) $item->stock_item_id !== (int) $detail->stock_item_id
                    || (int) $item->warehouse_id !== (int) $lot->warehouse_id
                    || (int) $item->location_id !== (int) $lot->location_id) {
                    $this->invalid('Hay ítems reservados, utilizados o con trazabilidad inconsistente.');
                }
            }
            $material = Material::query()->where('tenant_id', $tenant)->find($detail->material_id);
            if (!$material) { $this->invalid('No se puede verificar el material del detalle.'); }
            if ((int) $material->tipo_venta_id === 3 && bccomp((string) $items->count(), $qty, 10) !== 0) {
                $this->invalid('La cantidad de ítems unitarios no coincide con la cantidad original.');
            }
            $plans[] = compact('detail', 'items', 'lots');
            $affected[] = (int) $detail->stock_item_id;
        }
        ksort($keys, SORT_NATURAL);
        $levels = [];
        foreach ($keys as $key => $destination) {
            // Validar el origen histórico dentro de la empresa, sin sustituirlo por el local activo.
            $warehouse = Warehouse::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->lockForUpdate()->find($destination['warehouse_id']);
            $location = Location::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->where('warehouse_id', $destination['warehouse_id'])->lockForUpdate()->find($destination['location_id']);
            if (!$warehouse || !$location) { $this->invalid('El origen histórico ya no es válido. Requiere conciliación.'); }
            $matches = InventoryLevel::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->where('stock_item_id', $destination['stock_item_id'])->where('warehouse_id', $destination['warehouse_id'])
                ->where('location_id', $destination['location_id'])->orderBy('id')->lockForUpdate()->get();
            if ($matches->count() !== 1) { $this->invalid('Debe existir un único nivel de inventario para el origen histórico.'); }
            $level = $matches->first();
            $remaining = bcsub($this->decimal($level->qty_on_hand), $destination['qty'], 10);
            $reserved = $this->decimal($level->qty_reserved);
            if (bccomp($remaining, '0', 10) < 0 || bccomp($reserved, '0', 10) < 0
                || bccomp($remaining, $reserved, 10) < 0) {
                $this->invalid('El nivel de inventario no permite revertir el ingreso sin afectar existencias o reservas.');
            }
            $levels[] = ['level' => $level, 'remaining' => $remaining];
        }
        // No eliminar nada hasta validar el plan completo.
        foreach ($plans as $plan) {
            foreach ($plan['items'] as $item) { $item->delete(); }
            foreach ($plan['lots'] as $lot) { $lot->delete(); }
            $plan['detail']->delete();
        }
        foreach ($levels as $row) {
            // Restar el aporte exacto; no reemplazar todo el nivel con SUM(lotes),
            // porque puede coexistir inventario histórico aún no conciliado.
            $row['level']->qty_on_hand = $row['remaining'];
            $row['level']->save();
        }
        return array_values(array_unique($affected));
    }

    private function decimal($value): string
    {
        if (!is_numeric($value) || !is_finite((float) $value)) { $this->invalid('Cantidad de inventario inválida.'); }
        return number_format((float) $value, 10, '.', '');
    }

    private function invalid($message): void
    {
        throw ValidationException::withMessages(['entry' => $message]);
    }
}
