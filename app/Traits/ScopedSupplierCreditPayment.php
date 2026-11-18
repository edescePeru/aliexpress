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
            if (!is_numeric($model->amount) || !is_finite((float) $model->amount) || (float) $model->amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'El importe del pago debe ser positivo.']);
            }
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
