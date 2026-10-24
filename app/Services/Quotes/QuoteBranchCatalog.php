<?php

namespace App\Services\Quotes;

use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{Auth, DB};

/** Read-only scope for the QuoteSale product selector. No reservation writes. */
class QuoteBranchCatalog
{
    public function validateContext(): void
    {
        $user = Auth::user();
        if (!$user || $user->isPlatformAdmin() || (int) $user->tenant_id !== TenantContext::tenantId()
            || !DB::table('companies')->where('id', TenantContext::companyId())->where('tenant_id', TenantContext::tenantId())->where('is_active', true)->exists()
            || !DB::table('company_user')->where('company_id', TenantContext::companyId())->where('user_id', $user->id)->where('is_active', true)->exists()
            || !DB::table('branches')->where('id', TenantContext::branchId())->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            || !DB::table('branch_user')->where('branch_id', TenantContext::branchId())->where('user_id', $user->id)->where('is_active', true)->exists()) {
            throw new AuthorizationException('No tiene acceso a la empresa y el local actuales.');
        }
    }

    public function warehouses()
    {
        return DB::table('warehouses')->select('id')
            ->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
            ->where('branch_id', TenantContext::branchId());
    }

    public function levels($query)
    {
        return $query->where('inventory_levels.tenant_id', TenantContext::tenantId())
            ->where('inventory_levels.company_id', TenantContext::companyId())
            ->whereIn('inventory_levels.warehouse_id', $this->warehouses());
    }

    public function catalog($query)
    {
        $this->validateContext();
        return $query->where('stock_items.tenant_id', TenantContext::tenantId())
            ->where(function ($q) {
                // Non-inventory services remain company catalog entries.
                $q->where('stock_items.tracks_inventory', false)->orWhereIn('stock_items.id',
                    $this->levels(DB::table('inventory_levels'))->select('inventory_levels.stock_item_id')
                        ->groupBy('inventory_levels.stock_item_id')
                        ->havingRaw('SUM(qty_on_hand - qty_reserved) > 0'));
            });
    }

    public function items($query, int $stockItemId)
    {
        $this->validateContext();
        return $query->where('items.tenant_id', TenantContext::tenantId())
            ->where('items.company_id', TenantContext::companyId())
            ->where('items.stock_item_id', $stockItemId)
            ->whereIn('items.warehouse_id', $this->warehouses())
            ->whereExists(function ($lot) use ($stockItemId) {
                $lot->selectRaw('1')->from('stock_lots as local_lot')
                    ->whereColumn('local_lot.id', 'items.stock_lot_id')
                    ->whereColumn('local_lot.warehouse_id', 'items.warehouse_id')
                    ->whereColumn('local_lot.location_id', 'items.location_id')
                    ->where('local_lot.tenant_id', TenantContext::tenantId())
                    ->where('local_lot.company_id', TenantContext::companyId())
                    ->where('local_lot.stock_item_id', $stockItemId)
                    ->whereRaw('local_lot.qty_on_hand - local_lot.qty_reserved >= 1');
            });
    }
}
