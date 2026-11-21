<?php

namespace App\Services\Purchases;

use App\OrderPurchaseFinance;
use App\SupplierCredit;
use Illuminate\Validation\ValidationException;

class FinancePurchaseCreditGuard
{
    public static function assertMutable($id)
    {
        if (\Illuminate\Support\Facades\DB::transactionLevel() < 1) {
            throw ValidationException::withMessages(['order' => 'La modificación financiera requiere una operación transaccional.']);
        }
        $order = OrderPurchaseFinance::withTrashed()->lockForUpdate()->findOrFail($id);
        if (SupplierCredit::where('order_purchase_finance_id', $order->id)->exists()) {
            throw ValidationException::withMessages(['order' => 'La orden ya tiene un crédito de proveedor. Revisa el crédito antes de cambiar importes, detalles o eliminar/restaurar la orden.']);
        }
    }

    public static function financialChanges($order)
    {
        foreach (['supplier_id', 'currency_order', 'payment_deadline_id'] as $field) {
            if ((string) $order->getOriginal($field) !== (string) $order->$field) { return true; }
        }
        foreach (['total', 'igv'] as $field) {
            $before = $order->getOriginal($field); $after = $order->$field;
            if ($before === null || $after === null) {
                if ($before !== $after) { return true; }
            } elseif (SupplierCreditMoney::cents($before) !== SupplierCreditMoney::cents($after)) { return true; }
        }
        return false;
    }
}
