<?php

namespace App\Http\Controllers;

use App\Services\PublicCatalog;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog, string $companySlug, ?string $branchSlug = null)
    {
        $company = $catalog->company($companySlug);
        $branches = $catalog->branches($company);
        $branch = $this->branch($branches, $branchSlug);
        $filters = $request->validate([
            'local' => 'nullable|string|max:80', 'buscar' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|min:1', 'subcategory_id' => 'nullable|integer|min:1',
            'talla_id' => 'nullable|integer|min:1', 'color_id' => 'nullable|integer|min:1',
            'min_price' => 'nullable|numeric|min:0', 'max_price' => 'nullable|numeric|min:0',
        ]);
        $showPrices = $this->showPrices($catalog, $company);
        if (!$showPrices) {
            unset($filters['min_price'], $filters['max_price']);
        }
        if ($branch) {
            unset($filters['local']);
        }
        $selected = $branch ?: $this->branch($branches, $filters['local'] ?? null);
        $offers = $catalog->offers($company, $selected ? collect([$selected]) : $branches);
        $facets = $catalog->facets($company, $catalog->offers($company, $branch ? collect([$branch]) : $branches));
        $products = $catalog->products($offers, trim($filters['buscar'] ?? ''), $filters)
            ->appends(array_filter($filters, function ($value) { return $value !== null && $value !== ''; }));

        return view('shop.publicCatalog.index', compact('catalog', 'company', 'branches', 'branch', 'selected', 'products', 'showPrices', 'filters', 'facets'));
    }

    public function product(PublicCatalog $catalog, string $companySlug, string $material)
    {
        return $this->detail($catalog, $companySlug, null, (int) $material);
    }

    public function branchProduct(PublicCatalog $catalog, string $companySlug, string $branchSlug, string $material)
    {
        return $this->detail($catalog, $companySlug, $branchSlug, (int) $material);
    }

    private function detail(PublicCatalog $catalog, string $companySlug, ?string $branchSlug, int $materialId)
    {
        $company = $catalog->company($companySlug);
        $branches = $catalog->branches($company);
        $branch = $this->branch($branches, $branchSlug);
        $offers = $catalog->offers($company, $branch ? collect([$branch]) : $branches, $materialId)->get();
        $product = $offers->first();
        abort_unless($product, 404);

        $locations = $offers->groupBy('branch_id')->map(function ($rows) {
            $location = $rows->first();
            $prices = [];
            foreach (['PEN', 'USD'] as $currency) {
                $valid = $rows->where('currency', $currency)->where('price', '>', 0);
                if ($valid->isNotEmpty()) {
                    $prices[$currency] = [$valid->min('price'), $valid->max('price')];
                }
            }
            return ['name' => $location->branch_name, 'slug' => $location->branch_slug, 'prices' => $prices];
        });
        $showPrices = $this->showPrices($catalog, $company);
        $whatsapp = preg_replace('/\D/', '', (string) $catalog->setting($company, 'company.profile.whatsapp'));

        return view('shop.publicCatalog.detail', compact('catalog', 'company', 'branch', 'product', 'locations', 'showPrices', 'whatsapp'));
    }

    private function branch($branches, ?string $slug)
    {
        if ($slug === null || $slug === '') {
            return null;
        }
        $branch = $branches->first(function ($candidate) use ($slug) {
            return $candidate->catalog_slug === $slug;
        });
        abort_unless($branch, 404);
        return $branch;
    }

    private function showPrices(PublicCatalog $catalog, $company): bool
    {
        $value = $catalog->setting($company, 'catalog.show_prices');
        return is_numeric($value) ? (float) $value === 1.0 : in_array($value, [true, 'true', 'yes', 'on'], true);
    }
}
