<?php

namespace App\Services\Materials;

use App\Support\TenantContext;

class MaterialCompanyCatalog
{
    /**
     * A Material belongs in this company list if at least one of its StockItems
     * is explicitly enabled for the current company. No stock/branch requirement.
     * Existing Material status and soft-delete filters remain with the caller.
     */
    public function apply($query)
    {
        app(MaterialParameters::class)->context();

        return $query->where('materials.tenant_id', TenantContext::tenantId())
            ->whereExists(function ($enabled) {
                $enabled->selectRaw('1')
                    ->from('company_stock_items as catalog_enabled')
                    ->join('stock_items as catalog_item', 'catalog_item.id', '=', 'catalog_enabled.stock_item_id')
                    ->whereColumn('catalog_item.material_id', 'materials.id')
                    ->where('catalog_item.tenant_id', TenantContext::tenantId())
                    ->where('catalog_enabled.tenant_id', TenantContext::tenantId())
                    ->where('catalog_enabled.company_id', TenantContext::companyId())
                    ->where('catalog_enabled.is_active', true);
            });
    }
}
