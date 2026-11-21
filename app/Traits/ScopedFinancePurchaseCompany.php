<?php

namespace App\Traits;

use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

trait ScopedFinancePurchaseCompany
{
    protected static function bootScopedFinancePurchaseCompany()
    {
        static::addGlobalScope('finance_purchase_company', function ($query) {
            $tenant = TenantContext::tenantIdOrNull();
            $company = TenantContext::companyIdOrNull();
            if (!$tenant || !$company) {
                $query->whereRaw('1 = 0');
                return;
            }
            $model = $query->getModel();
            $query->where($model->qualifyColumn('tenant_id'), $tenant)
                ->where($model->qualifyColumn('company_id'), $company);
        });

        static::saving(function ($model) {
            $model->assertFinancePurchaseContext();
            if ($model instanceof \App\OrderPurchaseFinance && $model->exists
                && \App\Services\Purchases\FinancePurchaseCreditGuard::financialChanges($model)) {
                \App\Services\Purchases\FinancePurchaseCreditGuard::assertMutable($model->id);
            }
            if ($model instanceof \App\OrderPurchaseFinanceDetail) {
                if ($model->exists && (string) $model->getOriginal('order_purchase_finance_id') !== (string) $model->order_purchase_finance_id) {
                    throw ValidationException::withMessages(['order' => 'No se puede cambiar la orden de un detalle existente.']);
                }
                if (!$model->exists || $model->isDirty(['material', 'unit', 'quantity', 'price', 'igv', 'total_detail'])) {
                    \App\Services\Purchases\FinancePurchaseCreditGuard::assertMutable($model->order_purchase_finance_id);
                }
                // Incluye eliminadas porque el flujo legacy restaura detalles antes que la orden.
                \App\OrderPurchaseFinance::withTrashed()->findOrFail($model->order_purchase_finance_id);
            }
            $model->tenant_id = TenantContext::tenantId();
            $model->company_id = TenantContext::companyId();
        });
        static::deleting(function ($model) {
            $model->assertFinancePurchaseContext();
            $id = $model instanceof \App\OrderPurchaseFinanceDetail ? $model->order_purchase_finance_id : $model->id;
            \App\Services\Purchases\FinancePurchaseCreditGuard::assertMutable($id);
        });
        static::restoring(function ($model) {
            $model->assertFinancePurchaseContext();
            $id = $model instanceof \App\OrderPurchaseFinanceDetail ? $model->order_purchase_finance_id : $model->id;
            \App\Services\Purchases\FinancePurchaseCreditGuard::assertMutable($id);
        });
    }

    protected function assertFinancePurchaseContext()
    {
        $tenant = TenantContext::tenantIdOrNull();
        $company = TenantContext::companyIdOrNull();
        if (!$tenant || !$company || !\App\Company::query()->where('tenant_id', $tenant)->whereKey($company)->exists()) {
            throw ValidationException::withMessages(['order' => 'Selecciona una empresa válida para esta operación.']);
        }
        if ($this->exists && ((int) $this->getOriginal('tenant_id') !== $tenant || (int) $this->getOriginal('company_id') !== $company)) {
            throw ValidationException::withMessages(['order' => 'La orden o detalle no pertenece a la empresa activa.']);
        }
    }
}
