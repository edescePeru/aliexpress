<?php
namespace App\Services\Materials;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class LegacyItemReadQuery
{
    public function material($id): void
    {
        app(MaterialInventoryScope::class)->context();
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        abort_unless(DB::table('materials')->where('id', $id)->where('tenant_id', TenantContext::tenantId())
            ->whereNull('deleted_at')->exists(), 404);
    }

    public function items(bool $historical = false)
    {
        $scope = app(MaterialInventoryScope::class);
        $scope->context();
        $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
        $query = DB::table('items as i')->where('i.tenant_id', $tenant)->where('i.company_id', $company)
            ->whereIn('i.warehouse_id', $scope->warehouses(DB::table('warehouses'))->select('warehouses.id'))
            ->join('locations as loc', function ($j) use ($tenant, $company) {
                $j->on('loc.id', '=', 'i.location_id')->on('loc.warehouse_id', '=', 'i.warehouse_id')
                    ->where('loc.tenant_id', $tenant)->where('loc.company_id', $company);
            })
            ->join('warehouses as wh', function ($j) use ($tenant, $company) {
                $j->on('wh.id', '=', 'i.warehouse_id')->where('wh.tenant_id', $tenant)->where('wh.company_id', $company);
            })
            ->join('materials as mat', function ($j) use ($tenant) {
                $j->on('mat.id', '=', 'i.material_id')->where('mat.tenant_id', $tenant);
            })
            ->leftJoin('stock_items as si', function ($j) use ($tenant) {
                $j->on('si.id', '=', 'i.stock_item_id')->on('si.material_id', '=', 'i.material_id')->where('si.tenant_id', $tenant);
            })
            ->where(function ($q) use ($tenant, $company, $historical) {
                $q->whereNull('i.stock_item_id')->orWhere(function ($q) use ($tenant, $company, $historical) {
                    $q->whereNotNull('si.id')->whereExists(function ($s) use ($tenant, $company, $historical) {
                        $s->selectRaw('1')->from('company_stock_items as enabled')
                            ->whereColumn('enabled.stock_item_id', 'si.id')->where('enabled.tenant_id', $tenant)
                            ->where('enabled.company_id', $company);
                        if (!$historical) { $s->where('enabled.is_active', true); }
                    });
                });
            })
            ->leftJoin('detail_entries as de', function ($j) use ($tenant, $company) {
                $j->on('de.id', '=', 'i.detail_entry_id')->on('de.material_id', '=', 'i.material_id')
                    ->where('de.tenant_id', $tenant)->where('de.company_id', $company)
                    ->where(function ($q) { $q->whereColumn('de.stock_item_id', 'i.stock_item_id')
                        ->orWhere(function ($q) { $q->whereNull('de.stock_item_id')->whereNull('i.stock_item_id'); }); });
            })
            ->leftJoin('entries as en', function ($j) use ($tenant, $company) {
                $j->on('en.id', '=', 'de.entry_id')->where('en.tenant_id', $tenant)->where('en.company_id', $company)->whereNull('en.deleted_at');
            })
            ->where(function ($q) { $q->whereNull('i.detail_entry_id')->orWhere(function ($q) { $q->whereNotNull('de.id')->whereNotNull('en.id'); }); })
            ->leftJoin('typescraps as ts', function ($j) use ($tenant) {
                $j->on('ts.id', '=', 'i.typescrap_id')->where('ts.tenant_id', $tenant)->whereNull('ts.deleted_at');
            })
            ->leftJoin('areas as ar', function ($j) use ($tenant, $company) {
                $j->on('ar.id', '=', 'loc.area_id')->where('ar.tenant_id', $tenant)->where('ar.company_id', $company);
            })
            ->leftJoin('shelves as sh', function ($j) use ($tenant) { $j->on('sh.id', '=', 'loc.shelf_id')->on('sh.warehouse_id', '=', 'wh.id')->where('sh.tenant_id', $tenant); })
            ->leftJoin('levels as lv', function ($j) use ($tenant) { $j->on('lv.id', '=', 'loc.level_id')->on('lv.shelf_id', '=', 'sh.id')->where('lv.tenant_id', $tenant); })
            ->leftJoin('containers as co', function ($j) use ($tenant) { $j->on('co.id', '=', 'loc.container_id')->on('co.level_id', '=', 'lv.id')->where('co.tenant_id', $tenant); })
            ->leftJoin('positions as po', function ($j) use ($tenant) { $j->on('po.id', '=', 'loc.position_id')->on('po.container_id', '=', 'co.id')->where('po.tenant_id', $tenant); })
            ->select(['i.*', 'mat.full_name as material_name', 'mat.unit_price as material_price',
                'si.display_name as stock_name', 'de.id as safe_detail_id', 'ts.id as safe_typescrap_id',
                'ar.name as area_name', 'wh.name as warehouse_name', 'sh.name as shelf_name',
                'lv.name as level_name', 'co.name as container_name', 'po.name as position_name']);
        if (!$historical) { $query->whereNull('mat.deleted_at'); }
        return $query->orderBy('i.id');
    }

    public function details($entryId)
    {
        $entry = app(LegacyItemReadAccess::class)->entry($entryId);
        $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
        return DB::table('detail_entries as d')->where('d.entry_id', $entry->id)
            ->where('d.tenant_id', $tenant)->where('d.company_id', $company)
            ->leftJoin('materials as m', function ($j) use ($tenant) { $j->on('m.id', '=', 'd.material_id')->where('m.tenant_id', $tenant); })
            ->leftJoin('stock_items as s', function ($j) use ($tenant) { $j->on('s.id', '=', 'd.stock_item_id')->on('s.material_id', '=', 'd.material_id')->where('s.tenant_id', $tenant); })
            ->where(function ($q) { $q->whereNull('d.material_id')->orWhereNotNull('m.id'); })
            ->where(function ($q) use ($tenant, $company) { $q->whereNull('d.stock_item_id')->orWhere(function ($q) use ($tenant, $company) {
                $q->whereNotNull('s.id')->whereExists(function ($s) use ($tenant, $company) {
                    $s->selectRaw('1')->from('company_stock_items as detail_enabled')->whereColumn('detail_enabled.stock_item_id', 'd.stock_item_id')
                        ->where('detail_enabled.tenant_id', $tenant)->where('detail_enabled.company_id', $company);
                });
            }); })
            ->select(['d.*', 'm.full_name as safe_material_name', 'm.code as safe_material_code', 's.display_name as safe_stock_name', 's.sku as safe_sku'])->orderBy('d.id')->get();
    }

    public function location($item, bool $position = false): string
    {
        $label = 'AR:'.$item->area_name.'|AL:'.$item->warehouse_name.'|AN:'.$item->shelf_name.'|NIV:'.$item->level_name.'|CON:'.$item->container_name;
        return $label.($position ? '|POS:'.$item->position_name : '');
    }
}
