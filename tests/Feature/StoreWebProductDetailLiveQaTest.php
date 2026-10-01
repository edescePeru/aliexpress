<?php

namespace Tests\Feature;

use App\Tenant;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Opt-in, existing authenticated contexts only, database-enforced READ ONLY transaction. */
class StoreWebProductDetailLiveQaTest extends TestCase
{
    public function test_authenticated_details_and_cross_tenant_binding()
    {
        if (getenv('STORE_WEB_LIVE_QA') !== '1') $this->markTestSkipped('STORE_WEB_LIVE_QA=1 required');
        $this->app->instance('env', 'local');
        config(['store-web.preview' => true, 'session.driver' => 'array']);
        DB::statement('SET TRANSACTION READ ONLY');
        DB::beginTransaction();
        $report = ['mode' => 'authenticated Laravel kernel sessions; READ ONLY transaction', 'tenants' => []];
        $snapshots = [];
        try {
            foreach (['Gamarra', 'EDESCE'] as $label) {
                session()->flush();
                $tenant = Tenant::where('name', 'like', '%' . $label . '%')->where('is_active', true)->firstOrFail();
                $user = User::where('tenant_id', $tenant->id)->where('enable', 1)->where('is_platform_admin', false)
                    ->whereHas('companies', function ($q) use ($tenant) {
                        $q->where('companies.tenant_id', $tenant->id)->where('companies.is_active', true)->where('company_user.is_active', true);
                    })->orderByDesc('is_tenant_owner')->firstOrFail();
                $company = $user->companies()->where('companies.tenant_id', $tenant->id)->where('companies.is_active', true)
                    ->wherePivot('is_active', true)->orderByDesc('company_user.is_default')->firstOrFail();
                $this->actingAs($user)->withSession(['multitenancy' => ['tenant_id' => $tenant->id, 'company_id' => $company->id]]);
                $catalog = $this->get('/store-web/products/data/1')->assertOk()->json();
                $products = $catalog['data'];
                for ($page = 2; $page <= $catalog['pagination']['totalPages']; $page++) {
                    $products = array_merge($products, $this->get('/store-web/products/data/' . $page)->assertOk()->json('data'));
                }
                $rows = [];
                foreach ($products as $product) {
                    $response = $this->get('/store-web/product/' . $product['id'])->assertOk()
                        ->assertHeader('X-Store-Web-Preview', 'local-authenticated-data-bridge-non-production')
                        ->assertSee('data-store-web-fixture="false"', false)
                        ->assertDontSee('Productos relacionados')->assertDontSee('Producto de ejemplo');
                    $material = $response->viewData('material');
                    $this->assertSame((int) $tenant->id, (int) $material->tenant_id);
                    $response->assertSee($material->full_name);
                    if ($material->brand) $response->assertSee($material->brand->name);
                    if ($material->code ?: $material->codigo) $response->assertSee($material->code ?: $material->codigo);
                    if ($response->viewData('descriptionFooterEmpresa')) $response->assertSee($response->viewData('descriptionFooterEmpresa'));
                    if ($response->viewData('showPricesCatalogEmpresa')) $response->assertSee($response->viewData('priceText'));
                    $response->assertSee($response->viewData('stockAvailable') > 0 ? 'Disponible' : 'No disponible');
                    foreach (['brand', 'presentations', 'variants', 'stockItems'] as $relation) $this->assertTrue($material->relationLoaded($relation));
                    foreach (['variants', 'stockItems'] as $relation) {
                        foreach ($material->getRelation($relation) as $item) $this->assertSame((int) $tenant->id, (int) $item->tenant_id);
                    }
                    $imageRows = collect($response->viewData('images'))->map(function ($image) {
                        $path = parse_url($image['image'], PHP_URL_PATH);
                        return ['path' => $path, 'exists' => is_file(public_path(ltrim($path, '/')))];
                    })->all();
                    $rows[] = ['id' => $material->id, 'variants' => $material->variants->count(), 'images' => $imageRows,
                        'sizes' => count($response->viewData('sizes')), 'colors' => count($response->viewData('colors')),
                        'priceVisible' => (bool) $response->viewData('showPricesCatalogEmpresa'),
                        'available' => $response->viewData('stockAvailable') > 0,
                        'whatsappConfigured' => (bool) $response->viewData('whatsappEmpresa')];
                    $snapshots[$label . '-' . $material->id] = ['html' => $response->getContent()];
                }
                $foreign = DB::table('materials')->where('tenant_id', '<>', $tenant->id)->whereNull('deleted_at')->value('id');
                $this->get('/store-web/product/' . $foreign . '?tenant_id=999&company_id=999')->assertNotFound();
                $this->get('/store-web/producto/' . $products[0]['id'])->assertNotFound();
                $this->get('/store-web/product/' . $products[0]['id'] . '?tenant_id=999&company_id=999')->assertOk();
                $report['tenants'][$label] = ['products' => $rows, 'crossTenantDenied' => true, 'contextSpoofIgnored' => true];
            }
            $report['status'] = 'PASS';
            file_put_contents(base_path('docs/store-web/detail-authenticated-live-qa.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if (getenv('STORE_WEB_DETAIL_QA_OUTPUT')) file_put_contents(getenv('STORE_WEB_DETAIL_QA_OUTPUT'), json_encode($snapshots));
        } finally { DB::rollBack(); }
    }
}
