<?php
namespace Tests\Feature;

use App\{Meta, User};
use App\Http\Controllers\MetaController;
use App\Services\Sales\MetaProgress;
use App\Helpers\MetaCalendarHelper;
use Illuminate\Support\Facades\{DB, Schema, Auth, Gate};
use Illuminate\Http\Request;
use Carbon\Carbon;
use Tests\TestCase;

/** Focused contract fixtures. All persistence uses a private in-memory connection. */
class GoalContextContractTest extends TestCase
{
    private $allowed = [];
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'goals_qa','database.connections.goals_qa'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true],
            'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('goals_qa');
        $this->assertSame(':memory:',DB::connection()->getConfig('database'));
        foreach (['permissions'=>['name','guard_name'], 'roles'=>['name','guard_name'], 'role_has_permissions'=>['permission_id','role_id'], 'users'=>['tenant_id','name','enable','is_platform_admin'],
            'companies'=>['tenant_id','is_active'], 'company_user'=>['company_id','user_id','is_active'],
            'sales'=>['tenant_id','company_id','branch_id','created_by','currency','date_sale','state_annulled','internal_reversal_status','importe_total'],
            'date_dimensions'=>['date','year','month','week_of_year']] as $table=>$columns) {
            Schema::create($table,function($t) use($columns){$t->bigIncrements('id');foreach($columns as $c){$t->string($c)->nullable();}});
        }
        require_once database_path('migrations/2025_11_26_155233_create_metas_table.php');
        require_once database_path('migrations/2026_11_13_000001_add_company_context_and_users_to_metas.php');
        (new \CreateMetasTable)->up(); (new \AddCompanyContextAndUsersToMetas)->up();
        foreach ([1=>1,2=>1,3=>3] as $id=>$tenant) DB::table('companies')->insert(['id'=>$id,'tenant_id'=>$tenant,'is_active'=>1]);
        foreach ([1=>1,2=>1,3=>3,4=>1] as $id=>$tenant) DB::table('users')->insert(['id'=>$id,'tenant_id'=>$tenant,'name'=>'Fixture '.$id,'enable'=>1,'is_platform_admin'=>0]);
        foreach ([[1,1],[2,2],[3,3]] as $pair) DB::table('company_user')->insert(['company_id'=>$pair[0],'user_id'=>$pair[1],'is_active'=>1]);
        $this->context(1,1,1);
        Auth::setUser(User::find(1));
        Gate::before(function($user,$ability){return in_array($ability,$this->allowed,true);});
        foreach ([1=>1,2=>1,3=>3] as $id=>$tenant) {
            $m=Meta::create(['tenant_id'=>$tenant,'company_id'=>$id,'tipo'=>'mensual','nombre'=>'Meta '.$id,'monto'=>200,'fecha_inicio'=>'2026-11-01','fecha_fin'=>'2026-11-30']);
            $m->users()->attach($id);
        }
    }
    private function context($t,$c,$b){session()->put('multitenancy',['tenant_id'=>$t,'company_id'=>$c,'branch_id'=>$b]);}
    public function test_context_and_read_binding(): void
    {
        foreach ([[1,1,1,1],[1,1,8,1],[1,2,2,2],[3,3,4,3]] as $ctx) {
            $this->context($ctx[0],$ctx[1],$ctx[2]);
            $this->assertSame([$ctx[3]],Meta::forCurrentCompany()->pluck('id')->all());
        }
        $this->context(1,1,1);
        $this->assertSame(1,app(MetaController::class)->edit(1)->getData()['meta']->id);
        foreach ([2,3,999999] as $id) {
            try { app(MetaController::class)->edit($id);$this->fail('Foreign or missing ID accepted'); }
            catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$this->assertSame(Meta::class,$e->getModel());}
        }
        session()->forget('multitenancy');$this->assertCount(0,Meta::forCurrentCompany()->get());
    }
    public function test_candidates_and_assignment_validation(): void
    {
        $controller=app(MetaController::class);
        $method=new \ReflectionMethod($controller,'eligibleUsers');$method->setAccessible(true);
        $this->assertSame([1],$method->invoke($controller)->pluck('id')->all());
        DB::table('company_user')->insert(['company_id'=>1,'user_id'=>4,'is_active'=>0]);
        $this->assertSame([1],$method->invoke($controller)->pluck('id')->all());
        $validate=new \ReflectionMethod($controller,'validateAssignments');$validate->setAccessible(true);
        foreach ([2,3,4] as $id) {
            try {$validate->invoke($controller,[$id],'2026-12-01','2026-12-31');$this->fail('Ineligible assignment accepted');}
            catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('users',$e->errors());}
        }
        $validate->invoke($controller,[1],'2026-12-01','2026-12-31');
        $this->assertEquals([1],Meta::find(1)->users()->pluck('users.id')->all());
    }
    public function test_exact_progress_exclusions_and_ranking(): void
    {
        $base=['tenant_id'=>1,'company_id'=>1,'branch_id'=>1,'created_by'=>1,'currency'=>'PEN','date_sale'=>'2026-11-01 00:00:00','state_annulled'=>0,'internal_reversal_status'=>null,'importe_total'=>50];
        DB::table('sales')->insert($base);
        DB::table('sales')->insert(array_merge($base,['branch_id'=>8,'date_sale'=>'2026-11-30 23:59:59','importe_total'=>100]));
        foreach ([['company_id'=>2],['tenant_id'=>3],['created_by'=>2],['currency'=>'USD'],['state_annulled'=>1],['internal_reversal_status'=>'reversed'],['date_sale'=>'2026-10-31 23:59:59'],['date_sale'=>'2026-12-01 00:00:00']] as $excluded) DB::table('sales')->insert(array_merge($base,$excluded,['importe_total'=>900]));
        $rows=app(MetaProgress::class)->rows('mensual',Carbon::parse('2026-11-01'),Carbon::parse('2026-11-30'));
        $this->assertCount(1,$rows);$this->assertEquals(150,$rows[0]['ventas_total']);$this->assertEquals(75,$rows[0]['porcentaje']);$this->assertSame(1,$rows[0]['puesto']);
        $settings=\Mockery::mock(\App\Services\SettingService::class);$settings->shouldReceive('get')->with('sales.goals.frequency')->andReturn('mensual');$this->app->instance(\App\Services\SettingService::class,$settings);
        $response=app(MetaController::class)->getRankingData(Request::create('/','GET',['year'=>2026,'month'=>11]));
        $this->assertEquals($rows,$response->getData(true)['data']);
        $this->assertStringContainsString('app(MetaProgress::class)->rows',file_get_contents(app_path('Http/Controllers/HomeController.php')));
    }
    public function test_calendar_periods(): void
    {
        foreach ([['mensual',['year'=>2026,'month'=>2],'2026-02-01','2026-02-28'],['quincenal',['year'=>2026,'month'=>11,'quincena'=>1],'2026-11-01','2026-11-15'],['quincenal',['year'=>2026,'month'=>11,'quincena'=>2],'2026-11-16','2026-11-30']] as $case){$r=MetaCalendarHelper::getRangeForPeriod($case[0],$case[1]);$this->assertSame($case[2],$r['start']->toDateString());$this->assertSame($case[3],$r['end']->toDateString());}
        foreach (['2026-10-31','2026-11-01'] as $date) DB::table('date_dimensions')->insert(['date'=>$date,'year'=>2026,'month'=>Carbon::parse($date)->month,'week_of_year'=>44]);
        $r=MetaCalendarHelper::getRangeForPeriod('semanal',['year'=>2026,'month'=>11,'week'=>44]);$this->assertSame('2026-11-01',$r['start']->toDateString());$this->assertSame('2026-11-01',$r['end']->toDateString());
    }
    public function test_actual_route_permission_middleware(): void
    {
        foreach (['index'=>'list_metas','create'=>'create_metas','edit'=>'edit_metas','destroy'=>'destroy_metas','ranking'=>'progress_metas','ranking.data'=>'progress_metas','weeks'=>'create_metas|progress_metas'] as $route=>$permission) {
            $r=app('router')->getRoutes()->getByName('metas.'.$route);$this->assertContains('permission:'.$permission,$r->gatherMiddleware());
            $middleware=app(\Spatie\Permission\Middlewares\PermissionMiddleware::class);$this->allowed=[];
            try{$middleware->handle(Request::create('/'),function(){return 'allowed';},$permission);$this->fail('Permission bypass');}catch(\Spatie\Permission\Exceptions\UnauthorizedException $e){$this->assertSame(403,$e->getStatusCode());}
            $this->allowed=[explode('|',$permission)[0]];$this->assertSame('allowed',$middleware->handle(Request::create('/'),function(){return 'allowed';},$permission));
        }
    }
    public function test_edit_view_can_render_without_shared_records(): void
    {
        $dir=sys_get_temp_dir().'/venti-goals-visual';
        if(!is_dir($dir.'/layouts')){mkdir($dir.'/layouts',0777,true);}
        file_put_contents($dir.'/layouts/appAdmin2.blade.php', '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="http://127.0.0.1:8080/admin/dist/css/adminlte.min.css"><link rel="stylesheet" href="http://127.0.0.1:8080/admin/dist/css/venti.min.css"><link rel="stylesheet" href="http://127.0.0.1:8080/admin/dist/css/venti-next.css">@yield("styles-plugins")<body><main class="content-wrapper ml-0"><section class="content-header"><p>QA aislado · SQLite · No guardar</p>@yield("page-header")</section><section class="content"><div class="card"><div class="card-header">@yield("page-title")</div><div class="card-body p-0">@yield("content")</div></div></section></main><script src="http://127.0.0.1:8080/admin/plugins/jquery/jquery.min.js"></script><script src="http://127.0.0.1:8080/admin/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>@yield("plugins")@yield("scripts")<script>document.addEventListener("submit",function(e){e.preventDefault();e.stopImmediatePropagation();},true);</script></body></html>');
        app('view')->getFinder()->prependLocation($dir);
        $this->allowed=['edit_metas'];
        app('view')->share('errors',new \Illuminate\Support\ViewErrorBag);
        $html=app(MetaController::class)->edit(1)->render();
        $html=str_replace(rtrim(config('app.url'),'/'),'http://127.0.0.1:8080',$html);
        $this->assertStringContainsString('name="_method" value="PUT"',$html);
        $this->assertStringContainsString('id="worker-select"',$html);
        $this->assertStringContainsString('Meta 1',$html);
        file_put_contents($dir.'/edit.html',$html);
        foreach (['create'=>['tipoMeta'=>'mensual','calendarData'=>[],'users'=>User::where('id',1)->get()],
            'ranking'=>['tipoMeta'=>'mensual','tipoMetaValida'=>true,'calendarData'=>[]],
            'index'=>['tipoMeta'=>'mensual','canCreateMetas'=>true,'metas'=>Meta::forCurrentCompany()->withCount('users')->paginate(10)]] as $name=>$data) {
            $output=view('metas.'.$name,$data)->render();
            $this->assertStringContainsString('Metas comerciales',$output);
            file_put_contents($dir.'/'.$name.'.html',$output);
        }
    }

}
