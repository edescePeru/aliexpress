<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\OrderPurchaseFinance;
use Illuminate\Support\Facades\{DB, Schema};

class FinancePurchaseListReadPathTest extends TestCase
{
    public function test_company_tenant_and_soft_delete_isolation_in_private_database()
    {
        config(['database.default' => 'finance_read_memory', 'database.connections.finance_read_memory' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''
        ], 'session.driver' => 'array']);
        DB::setDefaultConnection('finance_read_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        Schema::create('order_purchase_finances', function ($t) {
            $t->increments('id'); $t->integer('tenant_id'); $t->integer('company_id'); $t->softDeletes();
        });
        foreach ([[1,1,1,null],[2,1,2,null],[3,3,3,null],[4,1,1,'2026-11-18']] as $r) {
            DB::table('order_purchase_finances')->insert(['id'=>$r[0], 'tenant_id'=>$r[1], 'company_id'=>$r[2], 'deleted_at'=>$r[3]]);
        }
        foreach ([[1,1,1],[1,2,2],[3,3,3]] as $ctx) {
            session()->put('multitenancy', ['tenant_id'=>$ctx[0], 'company_id'=>$ctx[1]]);
            $this->assertSame([$ctx[2]], OrderPurchaseFinance::pluck('id')->all());
            $this->assertNull(OrderPurchaseFinance::find(999999));
        }
        session()->put('multitenancy', []);
        $this->assertSame(0, OrderPurchaseFinance::count());
    }

    public function test_both_list_routes_require_list_permission()
    {
        foreach (['dashboard/ordenes/compra/finanzas', 'dashboard/all/order/finance'] as $uri) {
            $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/'.$uri, 'GET'));
            $this->assertContains('permission:list_orderPurchaseFinance', $route->gatherMiddleware());
        }
    }
}
