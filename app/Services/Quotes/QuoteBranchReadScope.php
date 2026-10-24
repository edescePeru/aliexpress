<?php
namespace App\Services\Quotes;

use App\Support\TenantContext;

class QuoteBranchReadScope
{
    public function local($query)
    {
        return $this->company($query)->where('quotes.branch_id', TenantContext::branchId());
    }

    public function pending($query)
    {
        return $this->company($query)->whereNull('quotes.branch_id');
    }

    private function company($query)
    {
        app(QuoteBranchCatalog::class)->validateContext();
        return $query->where('quotes.tenant_id', TenantContext::tenantId())
            ->where('quotes.company_id', TenantContext::companyId());
    }
}
