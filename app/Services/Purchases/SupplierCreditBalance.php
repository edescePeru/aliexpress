<?php

namespace App\Services\Purchases;

use App\CreditPay;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class SupplierCreditBalance
{
    public static function forCredit($credit)
    {
        if (!$credit->exists || (int) $credit->tenant_id !== TenantContext::tenantIdOrNull()
            || (int) $credit->company_id !== TenantContext::companyIdOrNull()) {
            throw ValidationException::withMessages(['credit' => 'El crédito no pertenece al contexto activo.']);
        }
        $pen = $credit->total_soles !== null;
        $usd = $credit->total_dollars !== null;
        if ($pen === $usd) {
            throw ValidationException::withMessages(['credit' => 'El crédito '.$credit->id.' requiere un importe en una única moneda antes de calcular su saldo.']);
        }
        $total = round((float) ($pen ? $credit->total_soles : $credit->total_dollars), 2);
        $paid = round((float) CreditPay::where('supplier_credit_id', $credit->id)->sum('amount'), 2);
        if (!is_finite($total) || !is_finite($paid)) {
            throw ValidationException::withMessages(['credit' => 'El crédito contiene importes que requieren revisión.']);
        }
        return [
            'currency' => $pen ? 'PEN' : 'USD',
            'total' => $total, 'paid' => $paid, 'balance' => round($total - $paid, 2),
            'legacy_review' => (float) $credit->advance !== 0.0,
        ];
    }
}
