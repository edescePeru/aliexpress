<?php

namespace App\Traits;

use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

trait ScopedSupplierCreditCompany
{
    protected static function bootScopedSupplierCreditCompany()
    {
        static::addGlobalScope('supplier_credit_company', function ($query) {
            $tenant = TenantContext::tenantIdOrNull();
            $company = TenantContext::companyIdOrNull();
            if (!$tenant || !$company) { $query->whereRaw('1 = 0'); return; }
            $model = $query->getModel();
            $query->where($model->qualifyColumn('tenant_id'), $tenant)
                ->where($model->qualifyColumn('company_id'), $company);
        });
        static::saving(function ($model) {
            $model->assertSupplierCreditCompany();
            $tenant = TenantContext::tenantId();
            $company = TenantContext::companyId();
            $supplierQuery = \App\Supplier::where('tenant_id', $tenant)->whereKey($model->supplier_id);
            if ($model->exists) { $supplierQuery->withTrashed(); }
            if (!$supplierQuery->exists()) { $model->invalidCredit('El proveedor no pertenece al tenant activo.'); }
            if ($model->order_service_id) {
                $model->invalidCredit('Las órdenes de servicio legacy requieren contexto confirmado antes de generar o modificar créditos.');
            }
            $hasSource = false;
            foreach (['entry_id' => \App\Entry::class, 'order_purchase_id' => \App\OrderPurchase::class, 'order_purchase_finance_id' => \App\OrderPurchaseFinance::class] as $field => $class) {
                if ($model->exists && $model->getOriginal($field) && (string) $model->getOriginal($field) !== (string) $model->$field) {
                    $model->invalidCredit('No se puede sustituir la referencia de origen de un crédito existente.');
                }
                if (!$model->$field) { continue; }
                $hasSource = true;
                $parent = $class::withTrashed()->where('tenant_id', $tenant)->where('company_id', $company)->find($model->$field);
                if (!$parent || (string) $parent->supplier_id !== (string) $model->supplier_id) {
                    $model->invalidCredit('El origen del crédito no pertenece a esta empresa/proveedor.');
                }
                if (!$model->exists && $parent->trashed()) { $model->invalidCredit('No se puede crear crédito desde un origen eliminado.'); }
            }
            if (!$hasSource) { $model->invalidCredit('El crédito requiere una entrada u orden de origen contextual.'); }
            if ($model->payment_deadline_id && !\App\PaymentDeadline::where('type', 'purchases')->whereKey($model->payment_deadline_id)->exists()) {
                $model->invalidCredit('El plazo de compras no pertenece a esta empresa.');
            }
            $model->tenant_id = $tenant;
            $model->company_id = $company;
        });
        static::deleting(function ($model) { $model->assertSupplierCreditCompany(); });
    }

    protected function assertSupplierCreditCompany()
    {
        $tenant = TenantContext::tenantIdOrNull();
        $company = TenantContext::companyIdOrNull();
        if (!$tenant || !$company || !\App\Company::where('tenant_id', $tenant)->whereKey($company)->exists()) {
            $this->invalidCredit('Selecciona una empresa válida para operar créditos.');
        }
        if ($this->exists && ((int) $this->getOriginal('tenant_id') !== $tenant || (int) $this->getOriginal('company_id') !== $company)) {
            $this->invalidCredit('El crédito no pertenece a la empresa activa.');
        }
    }

    protected function invalidCredit($message)
    {
        throw ValidationException::withMessages(['credit' => $message]);
    }
}
