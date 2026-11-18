<?php
namespace Tests\Feature;

use App\{Sale, ShippingGuide, ShippingGuideItem, ShippingGuideVehicle, ShippingGuideDriver};
use App\Http\Controllers\ShippingGuideController;
use App\Services\{NubefactGreService, NubefactCompanyConfigService, NubefactCompanyConnection};
use Illuminate\Support\Facades\{DB, Schema, Http, Route};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class ShippingGuideCreateContractTest extends TestCase
{
    private $gre;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'gre_create_memory','database.connections.gre_create_memory'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>''],'session.driver'=>'array']);
        DB::setDefaultConnection('gre_create_memory');
        $this->assertSame(':memory:',DB::connection()->getConfig('database'));
        Http::fake(function () { throw new \LogicException('Network forbidden in this test'); });
        session()->put('multitenancy',['tenant_id'=>1,'company_id'=>1,'branch_id'=>1]);
        foreach ([new ShippingGuide(),new ShippingGuideItem(),new ShippingGuideVehicle(),new ShippingGuideDriver()] as $model) {
            Schema::create($model->getTable(),function($t) use ($model) {
                $t->increments('id'); foreach ($model->getFillable() as $column) $t->text($column)->nullable(); $t->timestamps();
            });
        }
        Schema::table('shipping_guides',function($t){$t->unique(['tenant_id','company_id','tipo_de_comprobante','serie','numero']);});
        foreach (['transfer_reasons','shipping_transport_types','weight_units','sunat_shipping_indicators','identity_document_types'] as $table) {
            Schema::create($table,function($t){$t->increments('id');$t->string('code');$t->string('name');$t->integer('sort_order')->default(0);$t->boolean('is_active')->default(1);});
        }
        foreach (['transfer_reasons'=>'01','shipping_transport_types'=>'01','weight_units'=>'KGM','identity_document_types'=>'6'] as $table=>$code) DB::table($table)->insert(['code'=>$code,'name'=>'QA']);
        Schema::create('districts',function($t){$t->string('id');}); DB::table('districts')->insert(['id'=>'150101']);
        Schema::create('companies',function($t){$t->integer('id');$t->integer('tenant_id');}); DB::table('companies')->insert(['id'=>1,'tenant_id'=>1]);
        Schema::create('customers',function($t){$t->increments('id');$t->integer('tenant_id');$t->string('business_name');$t->string('RUC')->nullable();$t->string('address')->nullable();$t->softDeletes();});
        DB::table('customers')->insert([['id'=>1,'tenant_id'=>1,'business_name'=>'Own'],['id'=>2,'tenant_id'=>3,'business_name'=>'Foreign']]);
        Schema::create('sales',function($t){$t->increments('id');foreach(['tenant_id','company_id','branch_id','state_annulled'] as $c)$t->integer($c);foreach(['internal_reversal_status','serie_sunat','numero','type_document','nombre_cliente','numero_documento_cliente','fecha_emision','importe_total'] as $c)$t->string($c)->nullable();});
        Schema::create('sale_details',function($t){$t->increments('id');foreach(['tenant_id','company_id','sale_id','material_id','stock_item_id','material_presentation_id'] as $c)$t->integer($c)->nullable();$t->decimal('quantity',12,3);$t->decimal('packs',12,3)->nullable();});
        // Null material/stock/presentation references intentionally exercise the legacy description fallback.
        foreach (['materials','stock_items','material_presentations'] as $table) Schema::create($table,function($t){$t->increments('id');$t->integer('tenant_id');$t->softDeletes();});
        foreach ([[1,1,1,0,null],[1,1,8,0,null],[1,2,2,0,null],[3,3,4,0,null],[1,1,1,1,null],[1,1,1,0,'reversed']] as $i=>$v) {
            DB::table('sales')->insert(['id'=>$i+1,'tenant_id'=>$v[0],'company_id'=>$v[1],'branch_id'=>$v[2],'state_annulled'=>$v[3],'internal_reversal_status'=>$v[4],'serie_sunat'=>'FQA1','numero'=>(string)($i+1)]);
            DB::table('sale_details')->insert(['tenant_id'=>$v[0],'company_id'=>$v[1],'sale_id'=>$i+1,'quantity'=>1]);
        }
        $connection = new NubefactCompanyConnection('https://example.invalid','fake-not-a-credential','00000000000',['gre_remitente'=>'TQA1','gre_remitente_numero_inicial'=>1]);
        $config=\Mockery::mock(NubefactCompanyConfigService::class);
        $config->shouldReceive('forCompany')->with(1,1)->andReturn($connection);
        $this->app->instance(NubefactCompanyConfigService::class,$config);
        $this->gre=\Mockery::mock(NubefactGreService::class);
        $this->gre->shouldReceive('buildPayload')->andReturn(['qa'=>true]);
        $this->app->instance(NubefactGreService::class,$this->gre);
    }

    private function controller() { return app(ShippingGuideController::class); }
    private function payload(array $override=[]): Request
    {
        return new Request(array_replace(['gre_tenant'=>1,'gre_company'=>1,'gre_branch'=>1,'serie'=>'TQA1','numero'=>999,
            'fecha_emision'=>'2026-11-17','fecha_inicio_traslado'=>'2026-11-17','motivo_traslado_code'=>'01','tipo_transporte'=>'01',
            'peso_bruto_total'=>1,'peso_bruto_um_code'=>'KGM','numero_bultos'=>1,'partida_ubigeo'=>'150101','partida_direccion'=>'QA',
            'llegada_ubigeo'=>'150101','llegada_direccion'=>'QA','customer_id'=>1,'customer_doc_type'=>'6','customer_doc_number'=>'00000000000',
            'items_mode'=>'SALE','sale_id'=>1,'transportista_doc_type'=>'6','transportista_doc_number'=>'00000000000',
            'transportista_name'=>'QA','vehicle_primary_plate'=>'QA-000'], $override));
    }

    public function test_create_type_and_tenant_customer_catalog()
    {
        foreach ([[],['type'=>'remitente']] as $query) {
            $data=$this->controller()->create(new Request($query))->getData();
            $this->assertSame([1],$data['customers']->pluck('id')->all());
            $this->assertSame('TQA1',$data['defaultSerie']);
        }
        foreach (['transportista','REMITENTE','invalid'] as $type) {
            try {$this->controller()->create(new Request(['type'=>$type]));$this->fail('Invalid type accepted');}
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertSame(404,$e->getStatusCode());}
        }
    }

    public function test_sales_lookup_and_both_item_endpoints_reject_foreign_or_ineligible_sales()
    {
        $this->assertSame([1],array_column($this->controller()->select2(new Request())->getData(true)['results'],'id'));
        $this->assertCount(1,$this->controller()->items(Sale::findOrFail(1))->getData(true)['items']);
        $this->assertCount(1,$this->controller()->saleItems(new Request(['sale_ref'=>'FQA1-1']))->getData(true)['items']);
        foreach ([2,3,4,5,6,999] as $id) {
            foreach (['id','ref','store'] as $mode) {
                try {
                    if($mode==='id') {$sale=new Sale();$sale->id=$id;$this->controller()->items($sale);}
                    elseif($mode==='ref') $this->controller()->saleItems(new Request(['sale_ref'=>'FQA1-'.$id]));
                    else $this->controller()->store($this->payload(['sale_id'=>$id]));
                    $this->fail('Foreign/ineligible sale accepted');
                } catch (ModelNotFoundException $e) {$this->assertSame(Sale::class,$e->getModel());}
            }
        }
        $this->assertSame(0,ShippingGuide::count());
    }

    public function test_context_customer_and_manual_validation_before_persistence()
    {
        foreach ([['gre_tenant'=>3],['gre_company'=>2],['gre_branch'=>8],['customer_id'=>2],
            ['items_mode'=>'MANUAL','items'=>[]],['items_mode'=>'MANUAL','items'=>[['product_id'=>1,'material_id'=>1,'descripcion'=>'QA','cantidad'=>0]]]] as $overrides) {
            try {$this->controller()->store($this->payload($overrides));$this->fail('Invalid input accepted');}
            catch(ValidationException $e){$this->assertSame(422,$e->status);}
        }
        try {$this->controller()->customerPayload(2);$this->fail('Foreign customer accepted');}
        catch(ModelNotFoundException $e){$this->assertSame(\App\Customer::class,$e->getModel());}
        $this->assertSame(0,ShippingGuide::count());
    }

    public function test_fake_send_after_commit_and_duplicate_submit_is_not_idempotent()
    {
        $this->gre->shouldReceive('send')->twice()->andReturnUsing(function($guide){
            $this->assertSame(0,DB::transactionLevel());$this->assertSame('SENT',$guide->status);
            return ['aceptada_por_sunat'=>true];
        });
        foreach ([1,2] as $number) {
            $response=$this->controller()->store($this->payload(['type'=>'transportista','tenant_id'=>99,'company_id'=>99,'branch_id'=>99]));
            $this->assertSame(200,$response->getStatusCode());
            $guide=$response->getData(true)['data'];
            $this->assertSame('ACCEPTED',$guide['status']);$this->assertSame((string)$number,$guide['numero']);
            $this->assertSame('REMITENTE',$guide['guide_type']);$this->assertEquals([1,1,1],[$guide['tenant_id'],$guide['company_id'],$guide['branch_id']]);
        }
        $this->assertSame(2,ShippingGuide::count());
        Http::assertNothingSent();
    }

    public function test_provider_failure_keeps_sent_guide_and_returns_502()
    {
        $this->gre->shouldReceive('send')->once()->andThrow(new \RuntimeException('Fake timeout'));
        $response=$this->controller()->store($this->payload());
        $this->assertSame(502,$response->getStatusCode());$this->assertSame(1,ShippingGuide::count());
        $this->assertSame('SENT',ShippingGuide::first()->status);$this->assertSame(0,DB::transactionLevel());
        Http::assertNothingSent();
    }

    public function test_series_mismatch_and_payload_failure_do_not_leave_a_guide()
    {
        $this->assertSame(422,$this->controller()->store($this->payload(['serie'=>'TXXX']))->getStatusCode());
        $this->assertSame(0,ShippingGuide::count());
        $failing=\Mockery::mock(NubefactGreService::class);
        $failing->shouldReceive('buildPayload')->once()->andThrow(new \InvalidArgumentException('Fake unsupported indicator'));
        $failing->shouldNotReceive('send');
        $response=(new ShippingGuideController($failing))->store($this->payload());
        $this->assertSame(422,$response->getStatusCode());
        $this->assertSame(0,ShippingGuide::count());$this->assertSame(0,ShippingGuideItem::count());
        $this->assertSame(0,DB::transactionLevel());Http::assertNothingSent();
    }

    public function test_manual_catalog_and_item_ownership_use_real_catalog_service()
    {
        $user=new \App\User();$user->forceFill(['id'=>1,'tenant_id'=>1,'enable'=>1,'is_platform_admin'=>0]);
        \Illuminate\Support\Facades\Auth::setUser($user);
        Schema::table('companies',function($t){$t->boolean('is_active')->default(1);});
        foreach (['company_user'=>'company_id','branch_user'=>'branch_id'] as $table=>$key) {
            Schema::create($table,function($t)use($key){$t->integer($key);$t->integer('user_id');$t->boolean('is_active');});
            DB::table($table)->insert([$key=>1,'user_id'=>1,'is_active'=>1]);
        }
        Schema::create('branches',function($t){$t->integer('id');$t->integer('company_id');$t->boolean('is_active');});
        DB::table('branches')->insert(['id'=>1,'company_id'=>1,'is_active'=>1]);
        Schema::table('materials',function($t){$t->boolean('enable_status')->nullable();$t->string('full_name')->nullable();});
        Schema::table('stock_items',function($t){$t->integer('material_id')->nullable();$t->integer('variant_id')->nullable();$t->boolean('is_active')->nullable();foreach(['display_name','sku','barcode'] as $c)$t->string($c)->nullable();});
        Schema::create('variants',function($t){$t->integer('id');$t->integer('tenant_id');$t->integer('material_id');$t->boolean('is_active');});
        Schema::create('company_stock_items',function($t){foreach(['tenant_id','company_id','stock_item_id','is_active'] as $c)$t->integer($c);});
        foreach ([[1,1,1],[2,1,2],[3,3,3]] as $v) {
            DB::table('materials')->insert(['id'=>$v[0],'tenant_id'=>$v[1],'enable_status'=>1,'full_name'=>'QA']);
            DB::table('stock_items')->insert(['id'=>$v[0],'tenant_id'=>$v[1],'material_id'=>$v[0],'is_active'=>1,'display_name'=>'QA']);
            DB::table('company_stock_items')->insert(['stock_item_id'=>$v[0],'tenant_id'=>$v[1],'company_id'=>$v[2],'is_active'=>1]);
        }
        $this->assertEquals([1],array_column($this->controller()->products(new Request())->getData(true)['results'],'id'));
        foreach ([[2,2],[3,3],[1,2]] as $pair) {
            try {$this->controller()->store($this->payload(['items_mode'=>'MANUAL','items'=>[['product_id'=>$pair[0],'material_id'=>$pair[1],'descripcion'=>'QA','cantidad'=>1]]]));$this->fail('Foreign/mismatched manual product accepted');}
            catch(ValidationException $e){$this->assertArrayHasKey('items',$e->errors());}
        }
        $this->gre->shouldReceive('send')->once()->andReturn(['aceptada_por_sunat'=>false]);
        $response=$this->controller()->store($this->payload(['items_mode'=>'MANUAL','items'=>[['product_id'=>1,'material_id'=>1,'descripcion'=>'QA','cantidad'=>1.125]]]));
        $this->assertSame(200,$response->getStatusCode());$this->assertSame('PENDING_SUNAT',$response->getData(true)['status']);
        $this->assertSame('1.125',ShippingGuideItem::first()->cantidad);
        $this->assertSame('NIU',ShippingGuideItem::first()->unidad_medida);
        Http::assertNothingSent();
    }

    public function test_documents_missing_granular_create_and_store_permissions()
    {
        // Characterization, not a passing authorization policy: update after DEV adds gates.
        foreach (['shipping_guides.create','shipping_guides.store','sales.select2','sales.items','shipping_guides.sale_items','shipping_guides.customer_payload','shipping_guides.products'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertEmpty(array_filter($route->gatherMiddleware(),function($m){return strpos($m,'permission:')===0 || strpos($m,'can:')===0;}),$name);
        }
    }
}
