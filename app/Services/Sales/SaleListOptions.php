<?php
namespace App\Services\Sales;

use App\{Sale, Customer, Quote, User};
use App\Support\TenantContext;

class SaleListOptions
{
    public function data(bool $errors = false): array
    {
        $access = app(SaleListAccess::class); $access->context($errors);
        $arrayYears = $access->visible(Sale::query())->select('created_at')->get()->pluck('created_at')->filter()
            ->map(function ($date) { return \Carbon\Carbon::parse($date)->format('Y'); })->unique()->sortDesc()->values()->all();
        $cashBoxes = $access->boxes()->orderBy('position')->get();
        $subtypes = $access->subtypes()->orderBy('position')->get();
        $customers = Customer::query()->where('tenant_id', TenantContext::tenantId())->where(function ($q) use ($access) {
            $q->whereIn('id', $access->visible(Sale::query())->whereNotNull('customer_id')->select('customer_id'))
                ->orWhereIn('id', Quote::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                    ->whereIn('id', $access->visible(Sale::query())->whereNotNull('quote_id')->select('quote_id'))->select('customer_id'));
        })->orderBy('business_name')->get();
        $saleCreators = User::query()->where('tenant_id', TenantContext::tenantId())
            ->whereIn('id', $access->visible(Sale::query())->select('created_by'))->orderBy('name')->get(['id', 'name']);
        $saleScopeLabel = $access->allBranch() ? 'Ventas del local' : 'Mis ventas del local';
        $salePermissions = $access->permissions();
        return compact('arrayYears', 'cashBoxes', 'subtypes', 'customers', 'saleCreators', 'saleScopeLabel', 'salePermissions');
    }
}
