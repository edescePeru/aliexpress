<?php
namespace App\Services\Sales;

use App\InventoryLevel;
use App\Material;
use App\PriceList;
use App\StockItem;
use App\Services\Materials\MaterialInventoryScope;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCatalogReadService
{
    public function authorize(string $version = 'either'): void
    {
        app(MaterialInventoryScope::class)->context();
        $user = Auth::user();
        $allowed = $version === 'v1' ? $user->can('create_puntoVenta')
            : ($version === 'v2' ? $user->can('createV2_puntoVenta')
                : ($user->can('create_puntoVenta') || $user->can('createV2_puntoVenta')));
        abort_unless($allowed, 403);
    }

    public function catalog($query = null)
    {
        app(MaterialInventoryScope::class)->context();
        $query = $query ?: StockItem::query();
        return $query->where('stock_items.tenant_id', TenantContext::tenantId())->where('stock_items.is_active', true)
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('company_stock_items as pos_enabled')
                    ->whereColumn('pos_enabled.stock_item_id', 'stock_items.id')
                    ->where('pos_enabled.tenant_id', TenantContext::tenantId())
                    ->where('pos_enabled.company_id', TenantContext::companyId())->where('pos_enabled.is_active', true);
            })->whereHas('material', function ($q) {
                $q->where('materials.tenant_id', TenantContext::tenantId())->where('enable_status', true);
            })->where(function ($q) {
                $q->whereNull('stock_items.variant_id')->orWhereHas('variant', function ($v) {
                    $v->where('variants.tenant_id', TenantContext::tenantId())->where('variants.is_active', true)
                        ->whereColumn('variants.material_id', 'stock_items.material_id');
                });
            });
    }

    public function localLevels($query)
    {
        return app(MaterialInventoryScope::class)->levels($query)
            ->whereIn('inventory_levels.warehouse_id', DB::table('warehouses')->select('id')
                ->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                ->where('branch_id', TenantContext::branchId()));
    }

    private function priceList()
    {
        return app(\App\Services\BranchPriceListService::class)->current();
    }

    private function relations($list): array
    {
        return ['material.priceListMaterials' => function ($q) use ($list) { $list ? $q->where('price_list_id', $list->id) : $q->whereRaw('1 = 0'); }, 'material.category', 'material.unitMeasure', 'material.typeTax', 'material.tipoVenta',
            'variant.talla', 'variant.color',
            'inventoryLevels' => function ($q) { $this->localLevels($q); },
            'priceListItems' => function ($q) use ($list) {
                $list ? $q->where('price_list_id', $list->id) : $q->whereRaw('1 = 0');
            }];
    }

    public function totals($levels): array
    {
        $physical = 0.0; $reserved = 0.0; $valid = true;
        foreach ($levels as $level) {
            $on = $level->qty_on_hand; $res = $level->qty_reserved;
            if (!is_numeric($on) || !is_numeric($res) || !is_finite((float) $on) || !is_finite((float) $res)
                || (float) $on < 0 || (float) $res < 0 || (float) $res > (float) $on) { $valid = false; continue; }
            $physical += (float) $on; $reserved += (float) $res;
        }
        return ['on_hand' => round($physical, 10), 'reserved' => round($reserved, 10),
            'available' => $valid ? round($physical - $reserved, 10) : 0.0, 'requires_review' => !$valid];
    }

    private function price($item, $list): array
    {
        if (!$list) return ['price' => null, 'has_price' => false, 'price_list_id' => null, 'price_source' => 'none'];
        $resolved = app(\App\Services\PriceResolverService::class)->resolveUsingPriceList($item, $list);
        return ['price' => $resolved['price'], 'has_price' => $resolved['price'] !== null,
            'price_list_id' => $resolved['price_list_id'], 'price_source' => $resolved['source']];
    }

    private function dto($item, $list): array
    {
        $material = $item->material;
        $totals = $this->totals($item->inventoryLevels);
        $price = $this->price($item, $list);
        $variant = $item->variant;
        $typeTax=$material->typeTax;
        $taxCode=strtoupper((string) optional($typeTax)->code);
        $taxName=strtolower((string) optional($typeTax)->name);
        $taxKind=(float) optional($typeTax)->tax > 0 ? 'gravada'
            : (($taxCode==='30'||strpos($taxCode,'INAF')!==false||strpos($taxName,'inafect')!==false)?'inafecta':'exonerada');
        $variantText = 'Presentación única';
        if ($variant) {
            $variantText = $variant->attribute_summary ?: collect([
                optional($variant->talla)->short_name ?: optional($variant->talla)->name,
                optional($variant->color)->name])->filter()->implode(' / ');
        }
        return array_merge($price, ['source' => 'stock_item', 'id' => $item->id, 'stock_item_id' => $item->id,
            'material_id' => $item->material_id, 'variant_id' => $item->variant_id, 'variant_text' => $variantText,
            'full_name' => $item->display_name ?: $material->full_name, 'display_name' => $item->display_name ?: $material->full_name,
            'sku' => $item->sku ?: '', 'barcode' => $item->barcode ?: '', 'stock' => $totals['available'],
            'stock_available' => $totals['available'], 'stock_current' => $totals['on_hand'], 'stock_reserved' => $totals['reserved'],
            'requires_review' => $totals['requires_review'], 'currency' => $list ? $list->currency : '',
            'category' => optional($material->category)->description ?: optional($material->category)->name ?: '',
            'unit' => optional($material->unitMeasure)->description ?: optional($material->unitMeasure)->name ?: 'UND',
            'tax_kind' => $taxKind, 'tax' => (float) (optional($material->typeTax)->tax ?? 18), 'type' => (int) ($material->tipo_venta_id ?: 0),
            'image' => $item->display_image, 'image_url' => $item->display_image_url, 'rating' => 4]);
    }

    private function validate($request, $page): int
    {
        $request->validate(['category_id' => 'nullable|integer|min:1', 'product_search' => 'nullable|string|max:200']);
        if (!is_scalar($page) || !preg_match('/^[1-9][0-9]*$/D', (string) $page)) {
            throw ValidationException::withMessages(['page' => 'La página debe ser un entero positivo.']);
        }
        return (int) $page;
    }

    private function stockSearch($query, string $text)
    {
        if ($text === '') { return $query; }
        return $query->where(function ($q) use ($text) {
            $q->where('sku', 'like', '%'.$text.'%')->orWhere('barcode', 'like', '%'.$text.'%')
                ->orWhere('display_name', 'like', '%'.$text.'%')->orWhereHas('material', function ($m) use ($text) {
                    $m->where('full_name', 'like', '%'.$text.'%')->orWhere('code', 'like', '%'.$text.'%')
                        ->orWhere('codigo', 'like', '%'.$text.'%');
                });
        });
    }

    private function page($query, int $page): array
    {
        $count = $query->count(); $pages = max(1, (int) ceil($count / 12)); $page = min($page, $pages);
        return [$query->skip(($page - 1) * 12)->take(12)->get(),
            ['currentPage' => $page, 'totalPages' => $pages, 'startRecord' => $count ? ($page - 1) * 12 + 1 : 0,
                'endRecord' => min($count, $page * 12), 'totalRecords' => $count, 'totalFilteredRecords' => $count]];
    }

    public function v1($request, $page): array
    {
        $this->authorize('v1'); app(MaterialInventoryScope::class)->snapshot($request);
        $page = $this->validate($request, $page); $list = $this->priceList();
        $available = $this->localLevels(DB::table('inventory_levels'))->select('inventory_levels.stock_item_id')
            ->groupBy('inventory_levels.stock_item_id')->havingRaw('SUM(qty_on_hand - qty_reserved) > 0')
            ->havingRaw('MIN(CASE WHEN qty_on_hand >= 0 AND qty_reserved >= 0 AND qty_reserved <= qty_on_hand THEN 1 ELSE 0 END) = 1');
        $query = $this->stockSearch($this->catalog(), trim((string) $request->input('product_search', '')))
            ->whereIn('stock_items.id', $available)->with($this->relations($list));
        if ($request->filled('category_id')) { $query->whereHas('material', function ($m) use ($request) { $m->where('category_id', $request->input('category_id')); }); }
        [$items, $pagination] = $this->page($query->orderBy('stock_items.id'), $page);
        return ['data' => $items->map(function ($item) use ($list) { return $this->dto($item, $list); })->values()->all(), 'pagination' => $pagination];
    }

    public function v2($request, $page): array
    {
        $this->authorize('v2'); app(MaterialInventoryScope::class)->snapshot($request);
        $page = $this->validate($request, $page); $list = $this->priceList();
        $query = Material::query()->where('materials.tenant_id', TenantContext::tenantId())->where('enable_status', true)
            ->whereIn('materials.id', $this->catalog()->select('stock_items.material_id'))
            ->with(['category', 'unitMeasure', 'typeTax', 'tipoVenta', 'stockItems' => function ($q) use ($list) {
                $this->catalog($q)->with($this->relations($list));
            }]);
        $text = trim((string) $request->input('product_search', ''));
        if ($text !== '') {
            $query->whereIn('materials.id', $this->stockSearch($this->catalog(), $text)->select('stock_items.material_id'));
        }
        if ($request->filled('category_id')) { $query->where('materials.category_id', $request->input('category_id')); }
        [$materials, $pagination] = $this->page($query->orderBy('materials.id'), $page);
        $data = $materials->map(function ($material) use ($list) {
            $options = $material->stockItems->map(function ($item) use ($list) { return $this->dto($item, $list); });
            $first = $options->first();
            $priced = $options->where('has_price', true)->sortBy('price')->first();
            return ['source' => 'material', 'id' => $material->id, 'material_id' => $material->id,
                'full_name' => $material->full_name, 'category' => optional($material->category)->description ?: optional($material->category)->name ?: '',
                'price' => $priced ? $priced['price'] : null, 'has_price' => (bool) $priced,
                'price_is_reference' => $options->count() > 1, 'stock_items_count' => $options->count(),
                'currency' => $list ? $list->currency : '', 'stock' => (float) $options->sum('stock_available'),
                'image' => $material->image ?: ($first['image'] ?? null), 'image_url' => $material->image_url ?: ($first['image_url'] ?? null),
                'unit' => $first['unit'] ?? 'UND', 'tax' => $first['tax'] ?? 18, 'type' => $first['type'] ?? 0,
                'rating' => 4, 'sku' => $first['sku'] ?? '', 'barcode' => $first['barcode'] ?? ''];
        });
        return ['data' => $data->values()->all(), 'pagination' => $pagination];
    }

    public function variants($request, $materialId): array
    {
        $this->authorize('v2'); app(MaterialInventoryScope::class)->snapshot($request);
        abort_unless(is_scalar($materialId) && preg_match('/^[1-9][0-9]*$/D', (string) $materialId), 404);
        $query = $this->catalog()->where('stock_items.material_id', $materialId);
        abort_unless((clone $query)->exists(), 404);
        $list = $this->priceList();
        return ['success' => true, 'data' => $query->with($this->relations($list))->orderBy('stock_items.id')->get()
            ->map(function ($item) use ($list) { return $this->dto($item, $list); })->values()->all()];
    }

    public function search($request): array
    {
        $this->authorize(); app(MaterialInventoryScope::class)->snapshot($request);
        $request->validate(['q' => 'nullable|string|max:200', 'page' => 'nullable|integer|min:1']);
        $term = trim((string) $request->input('q', ''));
        if ($term === '') {
            return ['data' => [], 'pagination' => ['currentPage' => 1, 'totalPages' => 1,
                'startRecord' => 0, 'endRecord' => 0, 'totalRecords' => 0, 'totalFilteredRecords' => 0]];
        }
        $query = $this->stockSearch($this->catalog(), $term)->orderBy('stock_items.id');
        [$items, $pagination] = $this->page($query, (int) $request->input('page', 1));
        return ['data' => $items->map(function ($item) { return ['id' => $item->id, 'sku' => $item->sku,
            'text' => ($item->display_name ?: optional($item->material)->full_name).' · '.$item->sku]; })->values()->all(), 'pagination' => $pagination];
    }

    public function availability($request, $stockId): array
    {
        $this->authorize(); app(MaterialInventoryScope::class)->snapshot($request);
        abort_unless(is_scalar($stockId) && preg_match('/^[1-9][0-9]*$/D', (string) $stockId), 404);
        $item = $this->catalog()->where('stock_items.id', $stockId)->with('material')->firstOrFail();
        // Explicit company-wide read, limited to active branches. No costs, item IDs or transfer actions.
        $warehouses = DB::table('warehouses')->where('warehouses.tenant_id', TenantContext::tenantId())
            ->where('warehouses.company_id', TenantContext::companyId())
            ->leftJoin('branches as pos_branch', function ($j) { $j->on('pos_branch.id', '=', 'warehouses.branch_id')->where('pos_branch.company_id', '=', TenantContext::companyId())->where('pos_branch.is_active', '=', true); })
            ->where(function ($q) { $q->whereNull('warehouses.branch_id')->orWhereNotNull('pos_branch.id'); })
            ->orderBy('warehouses.name')->get(['warehouses.id', 'warehouses.branch_id', 'warehouses.name', 'pos_branch.name as branch_name']);
        $levels = InventoryLevel::query()->where('inventory_levels.tenant_id', TenantContext::tenantId())
            ->where('inventory_levels.company_id', TenantContext::companyId())->where('stock_item_id', $item->id)
            ->whereIn('warehouse_id', $warehouses->pluck('id')->all())
            ->whereExists(function ($q) { $q->selectRaw('1')->from('locations as pos_location')
                ->whereColumn('pos_location.id', 'inventory_levels.location_id')->whereColumn('pos_location.warehouse_id', 'inventory_levels.warehouse_id')
                ->where('pos_location.tenant_id', TenantContext::tenantId())->where('pos_location.company_id', TenantContext::companyId()); })
            ->get()->groupBy('warehouse_id');
        $groups = []; $review = false;
        foreach ($warehouses as $warehouse) {
            if ($warehouse->branch_id !== null && (int) $warehouse->branch_id === TenantContext::branchId()) { continue; }
            $balance = $this->totals($levels->get($warehouse->id, collect())); $review = $review || $balance['requires_review'];
            if ($balance['requires_review'] || $balance['available'] <= 0) { continue; }
            $key = $warehouse->branch_id === null ? 'general' : 'branch-'.$warehouse->branch_id;
            if (!isset($groups[$key])) { $groups[$key] = ['key' => $key, 'title' => $key === 'general' ? 'Almacenes generales' : $warehouse->branch_name, 'available' => 0.0, 'warehouses' => []]; }
            $groups[$key]['available'] = round($groups[$key]['available'] + $balance['available'], 10);
            $groups[$key]['warehouses'][] = array_merge(['id' => $warehouse->id, 'name' => $warehouse->name], $balance);
        }
        $groups = collect($groups)->sortBy(function ($g) { return $g['key'] === 'general' ? '' : $g['title']; })->values()->all();
        return ['product' => ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->display_name ?: $item->material->full_name],
            'local' => $this->totals($this->localLevels($item->inventoryLevels())->get()), 'groups' => $groups,
            'requires_review' => $review, 'checked_at' => now()->format('d/m/Y H:i:s')];
    }
}
