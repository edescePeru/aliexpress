<?php
namespace App\Services\Purchases;

use App\OrderPurchase;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/** Lectura únicamente. Al guardar se recalcula bajo bloqueo de la orden en una transacción. */
class NormalPurchaseReceiptSummary
{
    public function forOrder(int $id): array
    {
        $tenant = TenantContext::tenantId();
        $company = TenantContext::companyId();
        $order = OrderPurchase::query()->where('type', 'n')->findOrFail($id);
        $errors = [];
        $lines = DB::table('order_purchase_details')->where('tenant_id', $tenant)
            ->where('company_id', $company)->where('order_purchase_id', $id)
            ->whereNull('deleted_at')->orderBy('id')->get()->keyBy('id');
        if ($lines->isEmpty()) { $errors[] = 'La orden no tiene líneas vigentes.'; }
        $received = [];
        // Se incluyen anuladas para bloquear hasta que se haya conciliado su reversión de stock.
        $entries = DB::table('entries')->where('tenant_id', $tenant)->where('company_id', $company)
            ->whereNull('deleted_at')->where(function ($q) use ($id, $order) {
                $q->where('order_purchase_id', $id);
                if (trim((string) $order->code) !== '') { $q->orWhere('purchase_order', $order->code); }
            })->get();
        $entryIds = [];
        foreach ($entries as $entry) {
            if ((int) $entry->order_purchase_id !== $id) {
                $errors[] = 'Existen ingresos históricos o contradictorios que requieren conciliación.';
                continue;
            }
            if ($entry->state_annulled) {
                $errors[] = 'Existe un ingreso anulado cuya reversión debe conciliarse.';
                continue;
            }
            $entryIds[] = $entry->id;
        }
        if ($entryIds) {
            $details = DB::table('detail_entries')->whereIn('entry_id', $entryIds)->get();
            foreach ($details as $detail) {
                $line = $lines->get($detail->order_purchase_detail_id);
                if ((int) $detail->tenant_id !== $tenant || (int) $detail->company_id !== $company
                    || !$line || (int) $line->material_id !== (int) $detail->material_id
                    || (string) $line->stock_item_id !== (string) $detail->stock_item_id) {
                    $errors[] = 'Un detalle recibido no coincide con una línea vigente de esta orden.';
                    continue;
                }
                $qty = $this->units($detail->entered_quantity);
                if ($qty === null || $qty <= 0) { $errors[] = 'Existe una recepción con cantidad inválida.'; continue; }
                $received[$line->id] = ($received[$line->id] ?? 0) + $qty;
            }
        }
        $result = []; $any = false; $complete = true;
        foreach ($lines as $line) {
            $ordered = $this->units($line->quantity);
            $qty = $received[$line->id] ?? 0;
            if ($ordered === null || $ordered <= 0 || $qty > $ordered) {
                $errors[] = 'Una línea tiene cantidades inválidas o recibió más de lo pedido.';
            }
            $any = $any || $qty > 0;
            $complete = $complete && $ordered !== null && $ordered > 0 && $qty === $ordered;
            $result[] = [
                'order_purchase_detail_id' => (int) $line->id,
                'material_id' => (int) $line->material_id,
                'stock_item_id' => $line->stock_item_id === null ? null : (int) $line->stock_item_id,
                'ordered' => $ordered === null ? null : $this->decimal($ordered),
                'received' => $this->decimal($qty),
                'pending' => $ordered === null ? null : $this->decimal(max(0, $ordered - $qty)),
            ];
        }
        $errors = array_values(array_unique($errors));
        $state = $errors ? null : ($complete ? 1 : ($any ? 0 : 2));
        return ['order_id' => $id, 'lines' => $result, 'state' => $state,
            'state_text' => $errors ? 'REQUIERE REVISIÓN' : [0 => 'INCOMPLETA', 1 => 'COMPLETA', 2 => 'POR INGRESAR'][$state],
            'can_receive' => !$errors && $state !== 1, 'errors' => $errors];
    }

    private function units($value): ?int
    {
        $text = (string) $value;
        if (!preg_match('/^([0-9]{1,12})(?:\.([0-9]{1,2}))?$/D', $text, $parts)) { return null; }
        return ((int) $parts[1]) * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    private function decimal(int $units): string
    {
        return intdiv($units, 100).'.'.str_pad((string) ($units % 100), 2, '0', STR_PAD_LEFT);
    }
}
