<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreWebLocalPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Fail before any SQL can reach a real database, including model binding.
        $deny = function () {
            throw new \RuntimeException('Store Web preview attempted a database connection.');
        };
        DB::connection()->setPdo($deny)->setReadPdo($deny);
        $this->app->instance('env', 'local');
        config(['store-web.preview' => true, 'app.debug' => false]);
    }

    public function test_local_preview_uses_fixtures_before_binding_or_controller()
    {
        foreach (['/store-web/inicio', '/store-web/catalogo', '/store-web/product/frontend-fixture',
            '/store-web/product/1', '/store-web/product/999999999'] as $url) {
            $this->get($url)->assertOk()->assertHeader('X-Store-Web-Preview', 'visual-qa-frontend-fixture')
                ->assertSee('[FIXTURE FRONTEND]')->assertSee('data-store-web-fixture="true"', false)
                ->assertSee('/store-web/css/style.css', false)->assertDontSee('project-catalog');
        }
    }

    public function test_disabled_preview_fails_closed_even_with_query_parameters()
    {
        config(['store-web.preview' => false]);
        foreach ($this->storeUrls() as $url) {
            $this->get($url . '?preview=1&storeWebFixture=true&company_id=1')->assertNotFound();
        }
    }

    public function test_home_search_navigation_and_fixture_slots_do_not_query_business_data()
    {
        $this->get('/store-web/inicio')->assertOk()
            ->assertSee('data-home-catalog-search', false)
            ->assertSee('name="search"', false)
            ->assertSee('method="get"', false)
            ->assertSee('/store-web/js/home-preview.js', false)
            ->assertSee('aria-disabled="true"', false);
        $this->get('/store-web/catalogo?search=azul%20%26%20ni%C3%B1o')->assertOk()
            ->assertSee('data-store-web-fixture="true"', false)
            ->assertDontSee('/store-web/js/home-preview.js', false);
        $this->get('/store-web/product/frontend-fixture')->assertOk()
            ->assertDontSee('/store-web/js/home-preview.js', false);
    }

    public function test_non_local_environments_cannot_enable_preview()
    {
        foreach (['production', 'staging', 'testing'] as $environment) {
            $this->app->instance('env', $environment);
            foreach ($this->storeUrls() as $url) {
                $this->get($url)->assertNotFound();
            }
        }
    }

    public function test_data_endpoints_and_legacy_aliases_do_not_fall_through_in_preview()
    {
        foreach (array_slice($this->storeUrls(), 3) as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    private function storeUrls()
    {
        return ['/store-web/inicio', '/store-web/catalogo', '/store-web/product/1',
            '/store-web/products/data/1', '/store-web/categories/data', '/store-web/sizes/data',
            '/store-web/colors/data', '/store-web/search-product', '/store-web/producto/1', '/store-web/tienda'];
    }

    public function test_authenticated_bridge_requires_existing_valid_session_context()
    {
        $this->partialMock(\App\Http\Controllers\StoreWebController::class, function ($mock) {
            $mock->shouldReceive('getDataProductsV2')->once()->andReturn(['data' => [], 'pagination' => []]);
        });
        $user = $this->bridgeUser();
        $this->actingAs($user);
        $this->get('/store-web/product/1?tenant_id=701&company_id=702')->assertNotFound();
        $this->get('/store-web/products/data/1?tenant_id=701&company_id=702')->assertNotFound();
        $this->withSession(['multitenancy' => ['tenant_id' => 799, 'company_id' => 702]])
            ->get('/store-web/products/data/1')->assertNotFound();
        $this->withSession(['multitenancy' => ['tenant_id' => 701, 'company_id' => 702]]);

        $this->get('/store-web/products/data/1?tenant_id=799&company_id=999')->assertOk()
            ->assertHeader('X-Store-Web-Preview', 'local-authenticated-data-bridge-non-production');
        $this->assertSame(701, session('multitenancy.tenant_id'));
        $this->assertSame(702, session('multitenancy.company_id'));
        $this->app->instance('env', 'production');
        $this->get('/store-web/product/1')->assertNotFound();
        $this->get('/store-web/products/data/1')->assertNotFound();
        $this->app->instance('env', 'local');
        config(['store-web.preview' => false]);
        $this->get('/store-web/product/1')->assertNotFound();
        $this->get('/store-web/products/data/1')->assertNotFound();
    }

    public function test_authenticated_bridge_rejects_inactive_or_unauthorized_context()
    {
        foreach ([[true, false, 1, false], [false, true, 1, false], [true, true, 0, false], [true, true, 1, true]] as $case) {
            $user = $this->bridgeUser($case[0], $case[1]);
            $user->enable = $case[2]; $user->is_platform_admin = $case[3];
            $this->actingAs($user)->withSession(['multitenancy' => ['tenant_id' => 701, 'company_id' => 702]])
                ->get('/store-web/products/data/1')->assertNotFound();
            $this->get('/store-web/product/1')->assertNotFound();
        }
    }

    private function bridgeUser($companyAllowed = true, $tenantActive = true)
    {
        $user = \Mockery::mock(\App\User::class)->makePartial();
        $user->setRawAttributes(['id' => 700, 'tenant_id' => 701, 'enable' => 1, 'is_platform_admin' => false]);
        $user->setRelation('tenant', new \App\Tenant(['is_active' => $tenantActive]));
        $relation = \Mockery::mock();
        $relation->shouldReceive('where')->andReturnSelf();
        $relation->shouldReceive('wherePivot')->andReturnSelf();
        $relation->shouldReceive('exists')->andReturn($companyAllowed);
        $user->shouldReceive('companies')->andReturn($relation);
        return $user;
    }
}
