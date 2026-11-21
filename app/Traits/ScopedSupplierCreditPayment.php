<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;

trait ScopedSupplierCreditPayment
{
    protected static function bootScopedSupplierCreditPayment()
    {
        static::addGlobalScope('supplier_credit_payment_company', function ($query) {
            $query->whereIn($query->getModel()->qualifyColumn('supplier_credit_id'), \App\SupplierCredit::query()->select('supplier_credits.id'));
        });
        static::saving(function ($model) {
            $model->assertSupplierCreditPayment();
            $raw = $model->getAttributes()['amount'] ?? null;
            $cents = \App\Services\Purchases\SupplierCreditMoney::cents($raw);
            if ($cents <= 0 || $cents > 999999999999) {
                throw ValidationException::withMessages(['amount' => 'El pago debe estar entre 0.01 y 9999999999.99.']);
            }
            $model->amount = \App\Services\Purchases\SupplierCreditMoney::decimal($cents);
        });
        static::deleting(function ($model) { $model->assertSupplierCreditPayment(); });
    }

    protected function assertSupplierCreditPayment()
    {
        if ($this->exists && (string) $this->getOriginal('supplier_credit_id') !== (string) $this->supplier_credit_id) {
            throw ValidationException::withMessages(['credit' => 'No se puede trasladar un pago a otro crédito.']);
        }
        \App\SupplierCredit::query()->findOrFail($this->supplier_credit_id);
    }
}
