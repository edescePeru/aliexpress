<?php
namespace App\Services\Materials;

use App\Item;
use App\StockItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockItemScreenAccess
{
    public const VISIBLE_ITEM_STATES = ['entered', 'scraped', 'reserved'];

    public function permission(string $permission): void
    {
        app(MaterialInventoryScope::class)->context();
        abort_unless(Auth::user()->can($permission), 403);
    }

    public function stockItems()
    {
        app(MaterialInventoryScope::class)->context();
        return StockItem::query()->where('stock_items.tenant_id', TenantContext::tenantId())
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('company_stock_items as screen_enabled')
                    ->whereColumn('screen_enabled.stock_item_id', 'stock_items.id')
                    ->where('screen_enabled.tenant_id', TenantContext::tenantId())
                    ->where('screen_enabled.company_id', TenantContext::companyId())
                    ->where('screen_enabled.is_active', true);
            })
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('materials as screen_material')
                    ->whereColumn('screen_material.id', 'stock_items.material_id')
                    ->where('screen_material.tenant_id', TenantContext::tenantId())->whereNull('screen_material.deleted_at');
            });
    }

    public function stockItem($id, bool $lock = false)
    {
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        $query = $this->stockItems()->where('stock_items.id', $id);
        if ($lock) { $query->lockForUpdate(); }
        return $query->firstOrFail();
    }

    public function items()
    {
        $scope = app(MaterialInventoryScope::class);
        $scope->context();
        return Item::query()->where('items.tenant_id', TenantContext::tenantId())
            ->where('items.company_id', TenantContext::companyId())
            ->whereIn('items.stock_item_id', $this->stockItems()->select('stock_items.id'))
            ->whereIn('items.warehouse_id', $scope->warehouses(DB::table('warehouses'))->select('warehouses.id'))
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('locations as screen_location')
                    ->whereColumn('screen_location.id', 'items.location_id')
                    ->whereColumn('screen_location.warehouse_id', 'items.warehouse_id')
                    ->where('screen_location.tenant_id', TenantContext::tenantId())
                    ->where('screen_location.company_id', TenantContext::companyId());
            })
            ->whereIn('items.state_item', self::VISIBLE_ITEM_STATES);
    }

    public function item($id, bool $lock = false)
    {
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        $query = $this->items()->where('items.id', $id);
        if ($lock) { $query->lockForUpdate(); }
        return $query->firstOrFail();
    }
}
