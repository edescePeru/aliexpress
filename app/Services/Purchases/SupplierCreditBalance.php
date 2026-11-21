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
        $totalCents = SupplierCreditMoney::cents($pen ? $credit->total_soles : $credit->total_dollars);
        $paidCents = SupplierCreditMoney::cents(CreditPay::where('supplier_credit_id', $credit->id)->sum('amount'));
        $balanceCents = SupplierCreditMoney::add($totalCents, -$paidCents);
        return [
            'currency' => $pen ? 'PEN' : 'USD',
            'total' => (float) SupplierCreditMoney::decimal($totalCents),
            'paid' => (float) SupplierCreditMoney::decimal($paidCents),
            'balance' => (float) SupplierCreditMoney::decimal($balanceCents),
            'total_cents' => $totalCents, 'paid_cents' => $paidCents, 'balance_cents' => $balanceCents,
            'legacy_review' => SupplierCreditMoney::cents($credit->advance ?? '0') !== 0,
        ];
    }
}
