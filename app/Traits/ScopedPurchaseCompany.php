<?php
namespace App\Traits;

use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

trait ScopedPurchaseCompany
{
    public static function bootScopedPurchaseCompany()
    {
        static::addGlobalScope('purchase_company', function ($query) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
            if (!$tenant || !$company) { $query->whereRaw('1 = 0'); return; }
            $model = $query->getModel();
            $query->where($model->qualifyColumn('tenant_id'), $tenant)
                ->where($model->qualifyColumn('company_id'), $company);
        });
        static::saving(function ($model) {
            $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
            if ($model->exists && ((int) $model->getOriginal('tenant_id') !== $tenant
                || (int) $model->getOriginal('company_id') !== $company)) {
                throw ValidationException::withMessages(['order' => 'El registro no pertenece a la empresa actual.']);
            }
            if ($model instanceof \App\OrderPurchaseDetail) {
                $parentKey = 'order_purchase_id'; $parentClass = \App\OrderPurchase::class;
            } elseif ($model instanceof \App\MaterialOrder) {
                $parentKey = 'order_purchase_detail_id'; $parentClass = \App\OrderPurchaseDetail::class;
            } else { $parentKey = null; }
            if ($parentKey) {
                if ($model->exists && (string) $model->getOriginal($parentKey) !== (string) $model->$parentKey) {
                    throw ValidationException::withMessages(['order' => 'No se puede trasladar un detalle a otra orden.']);
                }
                if ($model->$parentKey) { $parentClass::query()->findOrFail($model->$parentKey); }
                elseif ($model instanceof \App\OrderPurchaseDetail) {
                    throw ValidationException::withMessages(['order' => 'El detalle requiere una orden válida.']);
                }
            }
            // No aceptar la atribución enviada por el navegador ni reubicar registros existentes.
            $model->tenant_id = $tenant; $model->company_id = $company;
        });
        static::deleting(function ($model) {
            if ((int) $model->getOriginal('tenant_id') !== TenantContext::tenantId()
                || (int) $model->getOriginal('company_id') !== TenantContext::companyId()) {
                throw ValidationException::withMessages(['order' => 'El registro no pertenece a la empresa actual.']);
            }
        });
    }
}
