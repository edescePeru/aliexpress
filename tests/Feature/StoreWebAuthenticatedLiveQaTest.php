<?php

namespace Tests\Feature;

use App\Tenant;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Opt-in read-only real-data QA. No migrations, seeders, credentials or business writes. */
class StoreWebAuthenticatedLiveQaTest extends TestCase
{
    public function test_gamarra_and_edesce_authenticated_sessions()
    {
        if (getenv('STORE_WEB_LIVE_QA') !== '1') {
            $this->markTestSkipped('Explicit STORE_WEB_LIVE_QA=1 required.');
        }
        $this->app->instance('env', 'local');
        config(['store-web.preview' => true, 'session.driver' => 'array']);
        DB::statement('SET TRANSACTION READ ONLY');
        DB::beginTransaction();
        $report = ['mode' => 'LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION',
            'sessions' => 'Laravel actingAs + isolated in-memory sessions with existing authorized users/memberships',
            'database' => 'READ ONLY transaction; no business writes', 'tenants' => []];
        try {
            $sets = []; $snapshots = [];
            foreach (['Gamarra', 'EDESCE'] as $label) {
                session()->flush();
                $tenant = Tenant::where('name', 'like', '%' . $label . '%')->where('is_active', true)->firstOrFail();
                $user = User::where('tenant_id', $tenant->id)->where('enable', 1)
                    ->where('is_platform_admin', false)->whereHas('companies', function ($q) use ($tenant) {
                        $q->where('companies.tenant_id', $tenant->id)->where('companies.is_active', true)
                            ->where('company_user.is_active', true);
                    })->orderByDesc('is_tenant_owner')->firstOrFail();
                $company = $user->companies()->where('companies.tenant_id', $tenant->id)
                    ->where('companies.is_active', true)->wherePivot('is_active', true)
                    ->orderByDesc('company_user.is_default')->firstOrFail();
                $this->actingAs($user)->withSession(['multitenancy' => ['tenant_id' => $tenant->id, 'company_id' => $company->id]]);
                // Home is intentionally still editorial fixture, even with an authenticated context.
                $home = $this->get('/store-web/inicio')->assertOk()
                    ->assertHeader('X-Store-Web-Preview', 'visual-qa-frontend-fixture')
                    ->assertSee('data-store-web-fixture="true"', false);
                $html = $this->get('/store-web/catalogo')->assertOk()
                    ->assertHeader('X-Store-Web-Preview', 'local-authenticated-data-bridge-non-production')
                    ->assertSee('data-store-web-fixture="false"', false)
                    ->assertDontSee('Producto destacado uno')
                    ->assertSee('LOCAL AUTHENTICATED BRIDGE', false);
                $response = $this->get('/store-web/products/data/1')->assertOk();
                $data = $response->json();
                $ids = array_column($data['data'], 'id'); $sets[$label] = $ids;
                $this->assertSame(count($ids), DB::table('materials')->where('tenant_id', $tenant->id)->whereIn('id', $ids)->count());
                $facets = []; $facetPayloads = []; $filterInputs = [];
                foreach (['categories' => 'categories', 'sizes' => 'tallas', 'colors' => 'colors'] as $path => $table) {
                    $facet = $this->get('/store-web/' . $path . '/data')->assertOk()->json('data');
                    $facetIds = array_column($facet, 'id');
                    $this->assertSame(count($facetIds), DB::table($table)->where('tenant_id', $tenant->id)->whereIn('id', $facetIds)->count());
                    $facets[$path] = count($facetIds);
                    $facetPayloads[$path] = ['data' => $facet];
                    if ($facetIds) {
                        $key = ['categories' => 'category_id', 'sizes' => 'size_ids', 'colors' => 'color_ids'][$path];
                        $filterInputs[$key] = $facetIds[0];
                        if ($path === 'categories' && !empty($facet[0]['subcategories'])) {
                            $filterInputs['subcategory_id'] = $facet[0]['subcategories'][0]['id'];
                        }
                    }
                }
                if ($ids) {
                    $first = $data['data'][0];
                    $this->get('/store-web/products/data/1?search=' . rawurlencode($first['full_name']))->assertOk();
                    $this->get('/store-web/products/data/1?min_price=0&max_price=999999')->assertOk();
                }
                foreach (range(2, max(2, $data['pagination']['totalPages'])) as $page) {
                    $nextIds = array_column($this->get('/store-web/products/data/' . $page)->assertOk()->json('data'), 'id');
                    $this->assertSame(count($nextIds), DB::table('materials')->where('tenant_id', $tenant->id)->whereIn('id', $nextIds)->count());
                    $sets[$label] = array_merge($sets[$label], $nextIds);
                }
                foreach ($filterInputs as $key => $value) {
                    $filter = [$key => $value];
                    if ($key === 'subcategory_id') $filter['category_id'] = $filterInputs['category_id'];
                    $filteredIds = array_column($this->get('/store-web/products/data/1?' . http_build_query($filter))->assertOk()->json('data'), 'id');
                    $this->assertSame(count($filteredIds), DB::table('materials')->where('tenant_id', $tenant->id)->whereIn('id', $filteredIds)->count());
                }
                $spoofed = $this->get('/store-web/products/data/1?tenant_id=999999&company_id=999999')->assertOk()->json('data');
                $this->assertSame($ids, array_column($spoofed, 'id'));
                $report['tenants'][] = ['label' => $label, 'tenant_id' => $tenant->id, 'company_id' => $company->id,
                    'firstPageCount' => count($ids), 'total' => $data['pagination']['totalFilteredRecords'],
                    'facets' => $facets, 'crossTenantIds' => 0, 'queryContextSpoofIgnored' => true];
                $snapshots[$label] = ['html' => $html->getContent(), 'homeHtml' => $home->getContent(), 'products' => $data, 'facets' => $facetPayloads];
            }
            $this->assertSame([], array_values(array_intersect($sets['Gamarra'], $sets['EDESCE'])));
            $report['status'] = 'PASS';
            file_put_contents(base_path('docs/store-web/shop-authenticated-live-qa.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // Optional local QA artifact; contains catalog responses, never auth cookies/credentials.
            if (getenv('STORE_WEB_QA_OUTPUT')) {
                file_put_contents(getenv('STORE_WEB_QA_OUTPUT'), json_encode($snapshots, JSON_UNESCAPED_UNICODE));
            }
        } finally {
            DB::rollBack();
        }
    }
}
