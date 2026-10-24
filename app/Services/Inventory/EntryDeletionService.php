<?php
namespace App\Services\Inventory;

use App\{Audit, CreditPay, DetailEntry, Entry, InventoryLevel, MaterialOrder, OrderPurchase, OrderPurchaseDetail, SupplierCredit};
use App\Services\InventoryCostService;
use App\Services\Purchases\NormalPurchaseReceiptSummary;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntryDeletionService
{
    public function delete(int $entryId, ?int $detailId = null): array
    {
        // La lectura previa no debe fijar una instantánea antigua dentro de la transacción.
        if (DB::transactionLevel() !== 0) { throw new \LogicException('La eliminación debe iniciar su propia transacción.'); }
        $begin = microtime(true);
        $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
        $probe = Entry::query()->where('tenant_id', $tenant)->where('company_id', $company)->findOrFail($entryId);
        $orderId = $probe->order_purchase_id ? (int) $probe->order_purchase_id : null;
        return DB::transaction(function () use ($entryId, $detailId, $tenant, $company, $orderId, $begin) {
            // Mismo primer bloqueo que en la recepción: orden, luego ingreso y sus líneas.
            $order = $orderId ? OrderPurchase::query()->where('type', 'n')->lockForUpdate()->findOrFail($orderId) : null;
            $entry = Entry::query()->where('tenant_id', $tenant)->where('company_id', $company)->lockForUpdate()->findOrFail($entryId);
            if (($entry->order_purchase_id ? (int) $entry->order_purchase_id : null) !== $orderId) {
                $this->invalid('El origen del ingreso cambió. Actualice la pantalla.');
            }
            if ($entry->state_annulled) { $this->invalid('El ingreso está anulado y requiere conciliación antes de eliminarlo.'); }
            $before = null;
            if ($order) {
                if ((string) $entry->purchase_order !== (string) $order->code || trim((string) $order->code) === '') {
                    $this->invalid('El código y la relación de la orden no coinciden. Requiere conciliación.');
                }
                OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->orderBy('id')->lockForUpdate()->pluck('id')->all();
                $before = app(NormalPurchaseReceiptSummary::class)->forOrder((int) $order->id);
                if ($before['errors']) { $this->invalid(implode(' ', $before['errors'])); }
            } elseif (trim((string) $entry->purchase_order) !== '') {
                $this->invalid('El ingreso solo tiene una referencia histórica de orden. No se asignará una relación por suposición.');
            }
            $allDetails = DetailEntry::query()->where('tenant_id', $tenant)->where('company_id', $company)
                ->where('entry_id', $entry->id)->orderBy('id')->lockForUpdate()->get();
            if (DB::table('detail_entries')->where('entry_id', $entry->id)->count() !== $allDetails->count()) {
                $this->invalid('Hay detalles fuera del contexto del ingreso. Requiere conciliación.');
            }
            $details = $detailId === null ? $allDetails : $allDetails->where('id', $detailId)->values();
            if ($detailId !== null && $details->isEmpty()) { throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(DetailEntry::class, [$detailId]); }
            $credits = SupplierCredit::query()->where('entry_id', $entry->id)->orderBy('id')->lockForUpdate()->get();
            if ($credits->count() > 1) { $this->invalid('El ingreso tiene varios créditos y requiere conciliación.'); }
            $credit = $credits->first();
            if ($credit) {
                if ($credit->state_credit !== 'outstanding' || !in_array($credit->state_pay, [null, '', 'pending'], true)
                    || !is_numeric($credit->advance ?? 0) || (float) ($credit->advance ?? 0) != 0.0
                    || CreditPay::query()->where('supplier_credit_id', $credit->id)->orderBy('id')->lockForUpdate()->get()->isNotEmpty()) {
                    $this->invalid('El crédito tiene pagos, adelantos o un estado que impide revertirlo.');
                }
                if ($order && ((int) $credit->order_purchase_id !== (int) $order->id
                    || (int) $credit->supplier_id !== (int) $entry->supplier_id
                    || !in_array($entry->currency_invoice, ['PEN', 'USD'], true))) {
                    $this->invalid('El crédito no corresponde a la orden, proveedor o moneda del ingreso.');
                }
            }
            if ($order && $credit) {
                $entry->setRelation('details', $allDetails);
                $amount = $entry->currency_invoice === 'PEN' ? $credit->total_soles : $credit->total_dollars;
                if (!is_numeric($amount) || bccomp((string) $amount, (string) $entry->total, 2) !== 0) {
                    $this->invalid('El importe del crédito no coincide con el ingreso. Requiere conciliación.');
                }
            }
            $affected = app(EntryStockReversalService::class)->reverse($entry, $details);
            $remainingDetails = DetailEntry::query()->where('tenant_id', $tenant)->where('company_id', $company)->where('entry_id', $entry->id)->get();
            $deleteHeader = $detailId === null || ($order && $remainingDetails->isEmpty());
            if ($credit) {
                if ($deleteHeader) { $credit->delete(); }
                else {
                    $entry->setRelation('details', $remainingDetails);
                    $credit->total_soles = $entry->currency_invoice === 'PEN' ? (float) $entry->total : null;
                    $credit->total_dollars = $entry->currency_invoice === 'USD' ? (float) $entry->total : null;
                    $credit->save();
                }
            }
            if ($order) {
                // Conservar la clave como comprobante de procesamiento e invalidar la repetición del contenido original.
                $entry->purchase_receipt_hash = null;
                $entry->save();
            }
            if ($deleteHeader) { $entry->delete(); }
            if ($order) {
                $after = app(NormalPurchaseReceiptSummary::class)->forOrder((int) $order->id);
                if ($after['errors']) { $this->invalid(implode(' ', $after['errors'])); }
                foreach ($after['lines'] as $line) {
                    $tracking = MaterialOrder::query()->where('order_purchase_detail_id', $line['order_purchase_detail_id'])->orderBy('id')->lockForUpdate()->get();
                    if ($tracking->count() > 1) { $this->invalid('Hay seguimientos duplicados en una línea de la orden.'); }
                    foreach ($tracking as $row) {
                        if ((int) $row->material_id !== $line['material_id'] || (string) $row->stock_item_id !== (string) $line['stock_item_id']) {
                            $this->invalid('El seguimiento no corresponde al producto de la línea.');
                        }
                        $row->quantity_entered = $line['received']; $row->save();
                    }
                    if ($tracking->isEmpty()) {
                        MaterialOrder::create(['order_purchase_detail_id' => $line['order_purchase_detail_id'],
                            'material_id' => $line['material_id'], 'stock_item_id' => $line['stock_item_id'],
                            'quantity_request' => $line['ordered'], 'quantity_entered' => $line['received']]);
                    }
                }
                $order->state = $after['state'];
                if ($after['state'] !== 1 && $order->status_order === 'pick_up') { $order->status_order = 'send'; }
                $order->save();
            }
            if ($affected) {
                $averages = app(InventoryCostService::class)->getAverageCostsByStockItem($affected, now());
                foreach ($affected as $stockId) {
                    InventoryLevel::query()->where('tenant_id', $tenant)->where('company_id', $company)->where('stock_item_id', $stockId)
                        ->update(['average_cost' => (float) ($averages[$stockId] ?? 0)]);
                }
            }
            Audit::create(['user_id' => Auth::id(), 'action' => 'Revertir '.($detailId === null ? 'ingreso ' : 'detalle '.$detailId.' de ingreso ').$entryId
                .($order ? '; orden '.$order->id.': '.$before['state_text'].' -> '.$after['state_text'] : '; ingreso manual'),
                'time' => microtime(true) - $begin]);
            return ['entry_deleted' => (bool) $deleteHeader, 'order_id' => $order ? (int) $order->id : null];
        });
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['entry' => $message]);
    }
}
