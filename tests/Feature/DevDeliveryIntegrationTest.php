<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\{Auth, DB, Schema};
use Illuminate\Http\Request;
use App\{User, Tenant};
use App\Services\{OwnerWorkspace, PlatformDashboardSummary, TenantPlanService};

/** R7.5: disposable database only; no provider, business DB or document downloads. */
class DevDeliveryIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'delivery_memory','database.connections.delivery_memory'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('delivery_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        Schema::create('plans',function($t){$t->increments('id');$t->string('name');$t->boolean('is_active');$t->integer('max_active_users');$t->timestamps();});
        require_once base_path('database/migrations/2026_11_21_000001_add_structure_limits_to_plans.php');
        (new \AddStructureLimitsToPlans())->up();
        foreach(['users','tenants','companies','branches','company_user','branch_user'] as $table)Schema::create($table,function($t){
            $t->increments('id');foreach(['tenant_id','company_id','branch_id','user_id','plan_id'] as $c)$t->integer($c)->nullable();
            foreach(['name','business_name','trade_name'] as $c)$t->string($c)->nullable();
            foreach(['is_active','enable','is_platform_admin','is_tenant_owner','is_default','is_main'] as $c)$t->boolean($c)->default(false);
            $t->timestamps();
        });
        DB::table('plans')->insert(['id'=>1,'name'=>'QA','is_active'=>1,'max_active_users'=>5,'max_active_companies'=>1,'max_active_branches'=>2]);
        DB::table('tenants')->insert(['id'=>1,'name'=>'QA tenant','is_active'=>1,'plan_id'=>1]);
        foreach([[1,1],[2,0]] as $c)DB::table('companies')->insert(['id'=>$c[0],'tenant_id'=>1,'business_name'=>'QA '.$c[0],'is_active'=>$c[1]]);
        foreach([1,2] as $id)DB::table('branches')->insert(['id'=>$id,'company_id'=>$id,'name'=>'QA '.$id,'is_active'=>1]);
        foreach([[1,1,0],[2,0,0],[3,0,1]] as $u)DB::table('users')->insert(['id'=>$u[0],'tenant_id'=>1,'name'=>'QA','enable'=>1,'is_tenant_owner'=>$u[1],'is_platform_admin'=>$u[2]]);
        DB::table('company_user')->insert(['company_id'=>1,'user_id'=>1,'is_active'=>1,'is_default'=>1]);
        DB::table('branch_user')->insert(['branch_id'=>1,'user_id'=>1,'is_active'=>1,'is_default'=>1]);
        Auth::setUser(User::find(1));session(['multitenancy.tenant_id'=>1]);
    }
    private function request($name='dashboard.principal', $json=false)
    {
        $r=Request::create('/dashboard/principal','GET');$r->setLaravelSession(session()->driver());$r->setUserResolver(function(){return Auth::user();});
        $r->setRouteResolver(function()use($name){return app('router')->getRoutes()->getByName($name);});
        if($json)$r->headers->set('Accept','application/json');
        app()->instance('request',$r);\Illuminate\Support\Facades\Request::clearResolvedInstance('request');return $r;
    }
    public function test_owner_workspace_is_tenant_only_and_company_selection_is_not_implicit(): void
    {
        $r=$this->request();
        app(\App\Http\Middleware\InitializeTenantContext::class)->handle($r,function(){return response('ok');});
        $this->assertNull(session('multitenancy.company_id'));
        $data=app(OwnerWorkspace::class)->data();
        $this->assertSame([1],$data['workspaceCompanies']->pluck('id')->all());
        $this->assertSame([1],$data['workspaceCompanies'][0]->workspaceBranches->pluck('id')->all());
        $this->assertNull($data['workspaceCompany']);$this->assertSame(2,$data['workspaceStructure']['branches']['used']);
        session(['multitenancy.company_id'=>1,'multitenancy.branch_id'=>1]);
        $view=app(\App\Http\Controllers\HomeController::class)->dashboard();$this->assertSame('dashboard.owner',$view->name());
        $this->assertEquals(1,$view->getData()['workspaceBranch']->id);
    }
    public function test_owner_login_clears_context_and_operational_json_is_denied_without_selection(): void
    {
        session(['multitenancy.company_id'=>1,'multitenancy.branch_id'=>1,'url.intended'=>'/dashboard/listado/ventas']);
        $r=$this->request();$ctl=app(\App\Http\Controllers\Auth\LoginController::class);
        $m=new \ReflectionMethod($ctl,'authenticated');$m->setAccessible(true);$response=$m->invoke($ctl,$r,Auth::user());
        $this->assertSame(route('dashboard.principal'),$response->getTargetUrl());$this->assertNull(session('multitenancy.company_id'));$this->assertNull(session('url.intended'));
        try{app(\App\Http\Middleware\InitializeTenantContext::class)->handle($this->request('puntoVenta.list',true),function(){return response('bad');});$this->fail('Missing context accepted');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
    }
    public function test_platform_summary_is_global_read_only_and_rejects_owner(): void
    {
        try{app(PlatformDashboardSummary::class)->data();$this->fail('Owner entered platform');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        Auth::setUser(User::find(3));session(['multitenancy.company_id'=>1,'multitenancy.branch_id'=>1]);
        DB::enableQueryLog();$data=app(PlatformDashboardSummary::class)->data();
        $this->assertSame(1,$data['platformStats']['tenants']);$this->assertSame(1,$data['platformStats']['active_companies']);
        $this->assertSame(2,$data['platformStats']['active_branches']);$this->assertSame(2,$data['platformStats']['active_users']);
        $this->assertSame(0,$data['platformReviewCount']);$this->assertNull(session('multitenancy.company_id'));
        foreach(DB::getQueryLog() as $q)$this->assertMatchesRegularExpression('/^select/i',$q['query']);
    }
    public function test_structure_limits_are_installed_on_isolated_db_and_capacity_fails_closed(): void
    {
        $this->assertTrue(Schema::hasColumns('plans',['max_active_companies','max_active_branches']));
        $service=app(TenantPlanService::class);$tenant=Tenant::find(1);
        $this->assertSame(0,$service->structureSummary($tenant)['companies']['available']);
        try{DB::transaction(function()use($service){$service->ensureStructureCapacity($service->lockTenant(1),1,1);});$this->fail('Exceeded quota');}
        catch(\App\Exceptions\TenantCapacityException $e){$this->assertStringContainsString('cupo',$e->getMessage());}
        $this->assertSame(0,DB::transactionLevel());$this->assertSame(2,DB::table('companies')->count());
        DB::table('plans')->where('id',1)->update(['max_active_companies'=>null]);
        $this->expectException(\App\Exceptions\TenantCapacityException::class);$service->ensureStructureCapacity(Tenant::find(1),1);
    }
    public function test_all_financial_purchase_actions_are_retired_without_queries(): void
    {
        $this->request('dashboard.principal',true);DB::enableQueryLog();DB::flushQueryLog();$ctl=app(\App\Http\Controllers\OrderPurchaseFinanceController::class);$count=0;
        foreach((new \ReflectionClass($ctl))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            if($method->getDeclaringClass()->getName()!==get_class($ctl))continue;
            $this->assertSame(410,$method->invoke($ctl)->getStatusCode());$count++;
        }
        $this->assertSame(25,$count);$this->assertSame([],DB::getQueryLog());
    }
    public function test_paused_module_definitions_remain_disabled(): void
    {
        foreach(['industrial-quotes','legacy-output-requests','purchase-express','legacy-settings','legacy-access','free-sale'] as $key){
            $this->assertFalse(\App\Support\OperationalModules::enabled($key));
            $this->assertContains(\App\Support\OperationalModules::definition($key)['status'],[410,423]);
        }
    }

    /** Characterizes ENTRY-R75-01; does not grant a new permission or hide the defect. */
    public function test_entry_edit_current_service_rejects_the_existing_route_permission(): void
    {
        $actor=\Mockery::mock(User::class)->makePartial();
        $actor->shouldReceive('can')->with('edit_entryPurchase')->once()->andReturn(false);
        Auth::setUser($actor);
        $this->assertStringContainsString("permission:update_entryPurchase",file_get_contents(base_path('routes/web.php')));
        $this->assertSame('edit_entryPurchase',\App\Services\Inventory\PurchaseFormReadAccess::PERMISSIONS['entry_edit']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Services\Inventory\PurchaseFormReadAccess::class)->entryForEdit(1);
    }
}
