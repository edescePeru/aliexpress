<?php

namespace Tests\Feature;

use App\{CashBox, CashBoxSubtype};
use App\Http\Controllers\{CashBoxController, CashBoxSubtypeController};
use App\Services\Sales\PosCashService;
use App\Services\FinanceInvoiceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

/** Maintainer contract tests only; never call posting/session/payment services. */
class CashMaintainersClosureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'cash_qa_memory','database.connections.cash_qa_memory'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ],'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('cash_qa_memory');
        $this->assertSame(':memory:',DB::connection()->getConfig('database'));
        $this->context(1,1,1);
        Schema::create('branches',function($t){$t->increments('id');$t->integer('company_id');$t->string('name');});
        foreach([[1,1],[8,1],[2,2],[4,3]] as $b) DB::table('branches')->insert(['id'=>$b[0],'company_id'=>$b[1],'name'=>'QA Branch']);
        Schema::create('company_bank_accounts',function($t){$t->increments('id');$t->integer('tenant_id');$t->integer('company_id');$t->boolean('is_active');$t->string('currency');});
        foreach([[1,1,1],[2,1,2],[3,3,3]] as $a) DB::table('company_bank_accounts')->insert(['id'=>$a[0],'tenant_id'=>$a[1],'company_id'=>$a[2],'is_active'=>1,'currency'=>'PEN']);
        Schema::create('cash_boxes',function($t){
            $t->increments('id');$t->integer('tenant_id');$t->integer('company_id');$t->integer('branch_id')->nullable();
            $t->string('name');$t->string('type');$t->boolean('uses_subtypes');$t->boolean('is_active');$t->integer('position');
            foreach(['bank_name','account_label','account_number_mask','currency'] as $c)$t->string($c)->nullable();
            $t->decimal('current_balance',14,2);$t->integer('company_bank_account_id')->nullable();$t->timestamps();
        });
        Schema::create('cash_box_subtypes',function($t){
            $t->increments('id');$t->integer('tenant_id');$t->integer('company_id');$t->integer('cash_box_id')->nullable();
            $t->string('code');$t->string('name');$t->integer('position');$t->boolean('is_active');$t->boolean('is_deferred');$t->boolean('requires_commission');$t->timestamps();
        });
        // Synthetic references only: no CashRegister open/close or CashMovement posting.
        Schema::create('cash_registers',function($t){$t->increments('id');$t->integer('tenant_id');$t->integer('company_id');$t->integer('cash_box_id');$t->integer('status');});
        Schema::create('cash_movements',function($t){$t->increments('id');$t->integer('cash_box_id');$t->integer('cash_box_subtype_id')->nullable();});
    }
    private function context($t,$c,$b): void {session()->put('multitenancy',['tenant_id'=>$t,'company_id'=>$c,'branch_id'=>$b]);}
    private function request(array $data): Request {return Request::create('/','POST',$data);}
    private function boxPayload(array $data=[]): array {return array_merge(['name'=>'QA CASH BOX','type'=>'cash','branch_id'=>1,'currency'=>'PEN','position'=>0],$data);}
    private function subPayload(array $data=[]): array {return array_merge(['name'=>'QA CASH SUBTYPE','code'=>'qa_immediate','cash_box_id'=>null,'is_deferred'=>0,'requires_commission'=>0],$data);}
    private function box(array $data=[]): CashBox {
        $r=app(CashBoxController::class)->store($this->request($this->boxPayload($data)));
        $this->assertSame(201,$r->getStatusCode());return CashBox::findOrFail($r->getData()->data->id);
    }
    private function sub(array $data=[]): CashBoxSubtype {
        $r=app(CashBoxSubtypeController::class)->store($this->request($this->subPayload($data)));
        $this->assertSame(201,$r->getStatusCode());return CashBoxSubtype::findOrFail($r->getData()->data->id);
    }
    private function rejects(callable $call,$status=422): void {
        try{$r=$call();$this->assertSame($status,$r->getStatusCode());}
        catch(ValidationException $e){$this->assertSame($status,$e->status);}
        catch(ModelNotFoundException $e){$this->assertSame(404,$status);}
    }
    public function test_cash_create_authority_bank_currency_and_invalid_payloads()
    {
        $box=$this->box(['tenant_id'=>3,'company_id'=>3,'current_balance'=>999,'is_active'=>0,'uses_subtypes'=>1]);
        $this->assertSame(1,$box->tenant_id);$this->assertSame(1,$box->company_id);$this->assertSame('0.00',$box->current_balance);
        $this->assertTrue($box->is_active);$this->assertFalse($box->uses_subtypes);
        $bank=$this->box(['type'=>'bank','company_bank_account_id'=>1,'currency'=>'USD','uses_subtypes'=>1]);
        $this->assertSame('PEN',$bank->currency);$this->assertTrue($bank->uses_subtypes);
        foreach([['branch_id'=>2],['branch_id'=>4],['branch_id'=>999],['currency'=>'AB'],['type'=>'unknown'],['name'=>''],['type'=>'bank'],['type'=>'bank','company_bank_account_id'=>2],['type'=>'bank','company_bank_account_id'=>3],['type'=>'bank','company_bank_account_id'=>999]] as $bad)
            $this->rejects(function()use($bad){return app(CashBoxController::class)->store($this->request($this->boxPayload($bad)));});
        $this->assertSame(2,CashBox::count());
    }
    public function test_update_scope_guard_and_toggle_open_vs_closed_references()
    {
        $box=$this->box();$ctl=app(CashBoxController::class);
        $data=$this->boxPayload(['id'=>$box->id,'name'=>'QA renamed','tenant_id'=>3,'company_id'=>3,'current_balance'=>999]);
        $this->assertSame(200,$ctl->update($this->request($data))->getStatusCode());
        $this->assertSame(200,$ctl->update($this->request($data))->getStatusCode());
        $this->assertSame('QA renamed',$box->fresh()->name);$this->assertSame('0.00',$box->fresh()->current_balance);
        DB::table('cash_registers')->insert(['tenant_id'=>1,'company_id'=>1,'cash_box_id'=>$box->id,'status'=>1]);
        $this->rejects(function()use($ctl,$box){return $ctl->toggle($this->request(['id'=>$box->id]));});
        $this->rejects(function()use($ctl,$box){return $ctl->update($this->request($this->boxPayload(['id'=>$box->id,'branch_id'=>null])));});
        $this->assertTrue($box->fresh()->is_active);
        DB::table('cash_registers')->update(['status'=>0]);
        DB::table('cash_movements')->insert(['cash_box_id'=>$box->id]);
        $this->assertSame(200,$ctl->toggle($this->request(['id'=>$box->id]))->getStatusCode());$this->assertFalse($box->fresh()->is_active);
        $ctl->toggle($this->request(['id'=>$box->id]));$this->assertTrue($box->fresh()->is_active);
        $ctl->update($this->request($this->boxPayload(['id'=>$box->id,'branch_id'=>null])));$this->assertNull($box->fresh()->branch_id);
    }
    public function test_cross_context_object_ids_and_nonexistent_ids_fail_closed()
    {
        $box=$this->box();$sub=$this->sub();
        foreach([[1,2,2],[3,3,4]] as $ctx){$this->context(...$ctx);
            foreach(['update','toggle'] as $method){
                $this->rejects(function()use($method,$box){return app(CashBoxController::class)->$method($this->request($this->boxPayload(['id'=>$box->id])));},404);
                $this->rejects(function()use($method,$sub){return app(CashBoxSubtypeController::class)->$method($this->request($this->subPayload(['id'=>$sub->id])));},404);
            }
        }
        $this->context(1,1,1);
        foreach([CashBoxController::class,CashBoxSubtypeController::class] as $class)foreach(['update','toggle'] as $method){
            $this->rejects(function()use($class,$method){return app($class)->$method($this->request(['id'=>999]));},404);
            $this->rejects(function()use($class,$method){return app($class)->$method($this->request([]));});
        }
    }
    public function test_company_maintenance_and_operational_branch_shared_selection()
    {
        $local=$this->box();$shared=$this->box(['branch_id'=>null]);$outlet=$this->box(['branch_id'=>8]);
        $this->context(1,1,8);
        $this->assertSame(200,app(CashBoxController::class)->update($this->request($this->boxPayload(['id'=>$local->id])))->getStatusCode());
        foreach([app(PosCashService::class),app(FinanceInvoiceContext::class)] as $service){
            $ids=$service->boxes()->pluck('id')->all();$this->assertNotContains($local->id,$ids);$this->assertContains($shared->id,$ids);$this->assertContains($outlet->id,$ids);
        }
        $this->context(1,2,2);$this->assertSame(0,app(PosCashService::class)->boxes()->count());
        $own=$this->box(['branch_id'=>2]);$this->assertSame(2,$own->company_id);
        $this->context(3,3,4);$this->assertSame(0,app(PosCashService::class)->boxes()->count());
        $own=$this->box(['branch_id'=>4]);$this->assertSame(3,$own->tenant_id);
    }
    public function test_subtype_create_normalization_duplicates_and_authority()
    {
        $sub=$this->sub(['code'=>' QA Immediate ','tenant_id'=>3,'company_id'=>3,'requires_commission'=>1]);
        $this->assertSame('qa_immediate',$sub->code);$this->assertSame(1,$sub->company_id);$this->assertSame(1,$sub->tenant_id);$this->assertFalse($sub->requires_commission);
        $deferred=$this->sub(['code'=>'qa_deferred','is_deferred'=>1,'requires_commission'=>1]);$this->assertTrue($deferred->is_deferred);$this->assertTrue($deferred->requires_commission);
        $bank=$this->box(['type'=>'bank','company_bank_account_id'=>1,'uses_subtypes'=>1]);
        foreach([[],['cash_box_id'=>$bank->id],['code'=>'!!!'],['name'=>''],['cash_box_id'=>999]] as $bad)
            $this->rejects(function()use($bad){return app(CashBoxSubtypeController::class)->store($this->request($this->subPayload($bad)));});
        $specific=$this->sub(['code'=>'specific','cash_box_id'=>$bank->id]);
        $this->rejects(function(){return app(CashBoxSubtypeController::class)->store($this->request($this->subPayload(['code'=>'specific'])));});
        $this->context(1,2,2);
        $this->rejects(function()use($bank){return app(CashBoxSubtypeController::class)->store($this->request($this->subPayload(['cash_box_id'=>$bank->id])));});
        $this->assertSame('qa_immediate',$this->sub()->code);
    }
    public function test_subtype_update_toggle_and_consumer_exclusion()
    {
        $sub=$this->sub();$ctl=app(CashBoxSubtypeController::class);
        $data=$this->subPayload(['id'=>$sub->id,'name'=>'QA renamed','is_deferred'=>1,'requires_commission'=>1]);
        $this->assertSame(200,$ctl->update($this->request($data))->getStatusCode());$this->assertTrue($sub->fresh()->is_deferred);
        $this->assertSame(0,app(FinanceInvoiceContext::class)->subtypes()->count());
        $this->assertSame(1,app(PosCashService::class)->subtypes([])->count());
        $ctl->toggle($this->request(['id'=>$sub->id]));$this->assertFalse($sub->fresh()->is_active);
        $this->assertSame(0,app(PosCashService::class)->subtypes([])->count());
        $ctl->toggle($this->request(['id'=>$sub->id]));$this->assertTrue($sub->fresh()->is_active);
    }
    /** Characterization, not safety approval: keep these gaps visible until backend policy is fixed. */
    public function test_known_gaps_currency_and_historical_semantics_remain_open()
    {
        $box=$this->box(['currency'=>'XYZ']);$this->assertSame('XYZ',$box->currency);
        DB::table('cash_registers')->insert(['tenant_id'=>1,'company_id'=>1,'cash_box_id'=>$box->id,'status'=>1]);
        $sub=$this->sub(['is_deferred'=>1]);DB::table('cash_movements')->insert(['cash_box_id'=>$box->id,'cash_box_subtype_id'=>$sub->id]);
        $r=app(CashBoxController::class)->update($this->request($this->boxPayload(['id'=>$box->id,'currency'=>'USD'])));
        $this->assertSame(200,$r->getStatusCode());$this->assertSame('USD',$box->fresh()->currency);
        $r=app(CashBoxSubtypeController::class)->update($this->request($this->subPayload(['id'=>$sub->id,'is_deferred'=>0])));
        $this->assertSame(200,$r->getStatusCode());$this->assertFalse($sub->fresh()->is_deferred);
        $r=app(CashBoxSubtypeController::class)->toggle($this->request(['id'=>$sub->id]));$this->assertSame(200,$r->getStatusCode());
        $this->assertFalse($sub->fresh()->is_active);$this->assertSame(1,DB::table('cash_movements')->count());
    }
    public function test_validation_errors_render_as_json_422_for_ajax()
    {
        $request=$this->request([]);$request->headers->set('Accept','application/json');
        foreach([CashBoxController::class,CashBoxSubtypeController::class] as $class){
            try{app($class)->store($request);$this->fail('Incomplete payload accepted');}
            catch(ValidationException $e){
                $response=app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request,$e);
                $this->assertSame(422,$response->getStatusCode());
                $this->assertArrayHasKey('name',$response->getData(true)['errors']);
            }
        }
    }
    public function test_known_gap_omitted_nullable_subtype_scope_is_not_handled()
    {
        $data=$this->subPayload();unset($data['cash_box_id']);
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Undefined index: cash_box_id');
        app(CashBoxSubtypeController::class)->store($this->request($data));
    }
    public function test_inactive_and_ineligible_boxes_cannot_receive_specific_subtypes()
    {
        $cash=$this->box();
        $bank=$this->box(['type'=>'bank','company_bank_account_id'=>1,'uses_subtypes'=>1]);
        app(CashBoxController::class)->toggle($this->request(['id'=>$bank->id]));
        foreach([$cash,$bank] as $box)$this->rejects(function()use($box){
            return app(CashBoxSubtypeController::class)->store($this->request($this->subPayload(['cash_box_id'=>$box->id])));
        });
    }
    public function test_permission_middleware_denies_user_without_capabilities()
    {
        $user=\Mockery::mock(\App\User::class)->makePartial();$user->shouldReceive('can')->andReturn(false);
        \Illuminate\Support\Facades\Auth::guard()->setUser($user);
        foreach(['cashBox','cashBoxSubtype'] as $module)foreach(['list','create','edit','disable'] as $action){
            try{app(\Spatie\Permission\Middlewares\PermissionMiddleware::class)->handle($this->request([]),function(){throw new \RuntimeException('Unauthorized callback reached');},$action.'_'.$module);$this->fail('Permission accepted');}
            catch(\Spatie\Permission\Exceptions\UnauthorizedException $e){$this->assertSame(403,$e->getStatusCode());}
        }
    }
}
