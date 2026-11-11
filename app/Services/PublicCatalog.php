<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PublicCatalog
{
    public function company(string $slug)
    {
        $company = DB::table('companies as c')->join('tenants as t', 't.id', '=', 'c.tenant_id')
            ->where('c.catalog_slug', $slug)->where('c.is_active', true)->where('t.is_active', true)
            ->first(['c.id', 'c.tenant_id', 'c.catalog_slug', 'c.trade_name', 'c.business_name']);
        abort_unless($company, 404);
        return $company;
    }

    public function branches($company)
    {
        return DB::table('branches')->where('company_id', $company->id)->where('is_active', true)
            ->whereNotNull('catalog_slug')->orderByDesc('is_main')->orderBy('name')
            ->get(['id', 'name', 'catalog_slug', 'price_list_id']);
    }

    public function setting($company, string $key)
    {
        $definition = DB::table('setting_definitions')->where('key', $key)
            ->where('scope', 'company')->where('is_active', true)->first();
        if (!$definition) {
            return null;
        }
        $value = DB::table('setting_values')->where('setting_definition_id', $definition->id)
            ->where('tenant_id', $company->tenant_id)->where('company_id', $company->id)->whereNull('branch_id')->first();
        return $value ? ($definition->value_type === 'boolean' ? ($value->value_number ?? $value->value_text) : $value->value_text) : $definition->default_value;
    }

    public function offers($company, $branches, ?int $materialId = null)
    {
        $ids = $branches->pluck('id');
        $lists = DB::table('price_lists')->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)->where('is_active', true);
        $defaults = (clone $lists)->where('is_default', true)->limit(2)->pluck('id');
        $defaultId = $defaults->count() === 1 ? $defaults->first() : null;

        $availability = DB::table('inventory_levels as i')
            ->join('warehouses as w', 'w.id', '=', 'i.warehouse_id')
            ->where('i.tenant_id', $company->tenant_id)->where('i.company_id', $company->id)
            ->where('w.tenant_id', $company->tenant_id)->where('w.company_id', $company->id)
            ->whereIn('w.branch_id', $ids)
            ->groupBy('w.branch_id', 'i.stock_item_id')
            ->havingRaw('SUM(i.qty_on_hand - i.qty_reserved) > 0')
            ->havingRaw('MIN(CASE WHEN i.qty_on_hand >= i.qty_reserved AND i.qty_reserved >= 0 THEN 1 ELSE 0 END) = 1')
            ->select('w.branch_id', 'i.stock_item_id');

        $offers = DB::query()->fromSub($availability, 'available')
            ->join('branches as b', 'b.id', '=', 'available.branch_id')
            ->join('stock_items as s', 's.id', '=', 'available.stock_item_id')
            ->join('materials as m', 'm.id', '=', 's.material_id')
            ->leftJoin('variants as v', 'v.id', '=', 's.variant_id')
            ->leftJoinSub($lists, 'pl', function ($join) use ($defaultId) {
                $join->on('pl.id', '=', 'b.price_list_id')->orOn(function ($join) use ($defaultId) {
                    $join->whereNull('b.price_list_id')->where('pl.id', '=', $defaultId);
                });
            })
            ->leftJoin('price_list_items as pi', function ($join) {
                $join->on('pi.price_list_id', '=', 'pl.id')->on('pi.stock_item_id', '=', 's.id');
            })
            ->leftJoin('price_list_materials as pm', function ($join) {
                $join->on('pm.price_list_id', '=', 'pl.id')->on('pm.material_id', '=', 'm.id');
            })
            ->where('b.company_id', $company->id)->where('b.is_active', true)->whereNotNull('b.catalog_slug')
            ->where('s.tenant_id', $company->tenant_id)->where('s.is_active', true)
            ->where('m.tenant_id', $company->tenant_id)->where('m.enable_status', true)->whereNull('m.deleted_at')
            ->where(function ($query) use ($company) {
                $query->whereNull('s.variant_id')->orWhere(function ($query) use ($company) {
                    $query->where('v.tenant_id', $company->tenant_id)->where('v.is_active', true)
                        ->whereColumn('v.material_id', 'm.id');
                });
            })
            ->whereExists(function ($query) use ($company) {
                $query->selectRaw('1')->from('company_stock_items as csi')
                    ->whereColumn('csi.stock_item_id', 's.id')->where('csi.is_active', true)
                    ->where('csi.tenant_id', $company->tenant_id)->where('csi.company_id', $company->id);
            });

        if ($materialId !== null) {
            $offers->where('m.id', $materialId);
        }
        return $offers->selectRaw('m.id, m.full_name, m.image, m.category_id, m.subcategory_id, v.talla_id, v.color_id, b.id as branch_id, b.name as branch_name, b.catalog_slug as branch_slug, pl.currency, CASE WHEN pi.id IS NOT NULL THEN pi.price ELSE pm.price END as price');
    }

    public function products($offers, string $search, array $filters = [])
    {
        $query = DB::query()->fromSub($offers, 'offers')->groupBy('id', 'full_name', 'image')
            ->select('id', 'full_name', 'image')->orderBy('full_name')->orderBy('id');
        foreach (['PEN' => 'pen', 'USD' => 'usd'] as $currency => $alias) {
            $query->selectRaw("MIN(CASE WHEN currency = ? AND price > 0 THEN price END) AS {$alias}_min", [$currency])
                ->selectRaw("MAX(CASE WHEN currency = ? AND price > 0 THEN price END) AS {$alias}_max", [$currency]);
        }
        if ($search !== '') {
            $query->where('full_name', 'like', '%'.$search.'%');
        }
        foreach (['category_id', 'subcategory_id', 'talla_id', 'color_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['min_price' => '>=', 'max_price' => '<='] as $field => $operator) {
            if (isset($filters[$field])) {
                $query->where('currency', 'PEN')->where('price', '>', 0)->where('price', $operator, $filters[$field]);
            }
        }
        return $query->paginate(24);
    }

    public function facets($company, $offers): array
    {
        $facets = [];
        foreach (['category_id' => 'categories', 'subcategory_id' => 'subcategories', 'talla_id' => 'tallas', 'color_id' => 'colors'] as $field => $table) {
            $ids = DB::query()->fromSub(clone $offers, 'offers')->select($field)->whereNotNull($field)->distinct();
            $options = DB::table($table)->where('tenant_id', $company->tenant_id)->whereIn('id', $ids);
            if ($table !== 'colors') {
                $options->whereNull('deleted_at');
            }
            $facets[$field] = $options->orderBy('name')->get(['id', 'name']);
        }
        return $facets;
    }

    public function image($filename): string
    {
        if (!$filename || in_array(strtolower(basename($filename)), ['no_image.png', 'no-image.png'], true)) {
            return asset('store-web/img/no_image.png');
        }
        return asset('images/material/'.rawurlencode(basename($filename)));
    }
}
