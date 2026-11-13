<?php

namespace Tests\Feature;

use App\{Material, MaterialPresentation, MaterialPresentationBranch, PriceList, PriceListItem, PriceListMaterial, StockItem};
use App\Http\Controllers\PriceListController;
use App\Services\{BranchPriceListService, PriceResolverService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PriceListClosureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never migrate, truncate or write to the configured/shared database.
        config(['database.default' => 'pricing_memory', 'database.connections.pricing_memory' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array']);
        DB::setDefaultConnection('pricing_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        session()->put('multitenancy', ['tenant_id' => 1, 'company_id' => 1, 'branch_id' => 1]);
        Schema::create('companies', function ($t) { $t->increments('id'); $t->integer('tenant_id'); $t->boolean('is_active'); });
        Schema::create('branches', function ($t) { $t->increments('id'); $t->integer('company_id'); $t->integer('price_list_id')->nullable(); $t->boolean('is_active'); });
        Schema::create('price_lists', function ($t) {
            $t->increments('id'); $t->integer('tenant_id'); $t->integer('company_id');
            $t->string('name'); $t->string('currency'); $t->boolean('is_default'); $t->boolean('is_active'); $t->timestamps();
        });
        DB::table('companies')->insert([['id'=>1,'tenant_id'=>1,'is_active'=>1],['id'=>2,'tenant_id'=>1,'is_active'=>1],['id'=>3,'tenant_id'=>3,'is_active'=>1]]);
        DB::table('branches')->insert(['id'=>1,'company_id'=>1,'price_list_id'=>null,'is_active'=>1]);
    }

    private function request(array $overrides = []): Request
    {
        return Request::create('/', 'POST', array_merge(['name'=>'QA list','currency'=>'PEN','is_default'=>0,'is_active'=>1], $overrides));
    }

    private function list(array $overrides = []): PriceList
    {
        return PriceList::create(array_merge(['tenant_id'=>1,'company_id'=>1,'name'=>'Existing','currency'=>'PEN','is_default'=>1,'is_active'=>1], $overrides));
    }

    private function invalid(callable $action): void
    {
        try {
            $response = $action();
            $this->assertSame(422, $response->getStatusCode(), 'Invalid operation must not report success');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status);
        }
    }

    public function test_first_inactive_list_is_rejected_without_creating_inactive_default()
    {
        $this->invalid(function () { return app(PriceListController::class)->store($this->request(['is_active'=>0])); });
        $this->assertSame(0, PriceList::count());
    }

    public function test_first_active_list_becomes_default_and_later_inactive_list_does_not()
    {
        app(PriceListController::class)->store($this->request());
        app(PriceListController::class)->store($this->request(['name'=>'Inactive','is_active'=>0]));
        $this->assertSame(1, PriceList::where('is_default',1)->where('is_active',1)->count());
        $this->assertFalse(PriceList::where('name','INACTIVE')->first()->is_default);
    }

    public function test_invalid_promotion_returns_error_and_preserves_existing_default()
    {
        $old=$this->list(); $other=$this->list(['name'=>'Other','is_default'=>0]);
        $this->invalid(function () use ($other) { return app(PriceListController::class)->update($this->request(['is_default'=>1,'is_active'=>0]),$other); });
        $this->assertTrue($old->fresh()->is_default);
        $this->assertTrue($other->fresh()->is_active);
        $this->assertSame('Other',$other->fresh()->name);
    }

    public function test_default_cannot_be_disabled_and_successful_switch_is_company_local()
    {
        $old=$this->list(); $other=$this->list(['name'=>'Other','is_default'=>0]);
        $foreign=$this->list(['company_id'=>2]);
        $this->invalid(function () use ($old) { return app(PriceListController::class)->update($this->request(['is_active'=>0]),$old); });
        app(PriceListController::class)->update($this->request(['is_default'=>1]),$other);
        $this->assertFalse($old->fresh()->is_default); $this->assertTrue($other->fresh()->is_default); $this->assertTrue($foreign->fresh()->is_default);
    }

    public function test_currency_whitelist_on_create_and_update()
    {
        $list=$this->list();
        foreach (['EUR','XYZ','P',''] as $currency) {
            $this->invalid(function () use ($currency) { return app(PriceListController::class)->store($this->request(['name'=>'New','currency'=>$currency])); });
            $this->invalid(function () use ($currency,$list) { return app(PriceListController::class)->update($this->request(['currency'=>$currency]),$list); });
        }
        $this->assertSame(1,PriceList::count());
        app(PriceListController::class)->update($this->request(['currency'=>'usd','is_default'=>1]),$list);
        $this->assertSame('USD',$list->fresh()->currency);
    }

    public function test_id_scope_rejects_other_company_and_tenant_before_read_or_write()
    {
        foreach ([[1,2],[3,3]] as $context) {
            $list=$this->list(['tenant_id'=>$context[0],'company_id'=>$context[1]]);
            foreach (['update','getMaterials','saveMaterialPrices','getMaterialVariants','saveMaterialVariants'] as $method) {
                try {
                    $controller=app(PriceListController::class);
                    if ($method==='getMaterialVariants') $controller->$method($list,new Material());
                    elseif ($method==='saveMaterialVariants') $controller->$method($this->request(),$list,new Material());
                    else $controller->$method($this->request(),$list);
                    $this->fail('Foreign list accepted: '.$method);
                } catch (HttpException $e) { $this->assertSame(404,$e->getStatusCode()); }
            }
        }
    }

    public function test_failure_after_unsetting_defaults_rolls_back_all_changes()
    {
        $old=$this->list(); $other=$this->list(['name'=>'Other','is_default'=>0]);
        DB::unprepared("CREATE TRIGGER reject_test_update BEFORE UPDATE ON price_lists WHEN NEW.name = 'FAIL' BEGIN SELECT RAISE(ABORT, 'isolated failure'); END");
        try { app(PriceListController::class)->update($this->request(['name'=>'FAIL','is_default'=>1]),$other); $this->fail('Expected database failure'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertTrue($old->fresh()->is_default); $this->assertFalse($other->fresh()->is_default); }
    }

    public function test_resolvers_preserve_null_explicit_assignment_and_reject_ambiguity()
    {
        $branch=app(BranchPriceListService::class); $resolver=app(PriceResolverService::class);
        $this->assertNull($branch->current()); $this->assertNull($resolver->getDefaultPriceListForCompany(1));
        $one=$this->list(); $this->assertSame($one->id,$branch->current()->id);
        $two=$this->list(['name'=>'Second']);
        foreach ([$branch,$resolver] as $service) {
            try { $service instanceof BranchPriceListService ? $service->current() : $service->getDefaultPriceListForCompany(1); $this->fail('Ambiguous defaults accepted'); }
            catch (ValidationException $e) { $this->assertSame(422,$e->status); }
        }
        DB::table('branches')->update(['price_list_id'=>$two->id]);
        $this->assertSame($two->id,$branch->current()->id);
        $two->update(['is_active'=>0]);
        $this->expectException(ValidationException::class); $branch->current();
    }

    public function test_price_resolution_preserves_missing_zero_and_override_priority()
    {
        $list=$this->list(); $resolver=app(PriceResolverService::class);
        $material=new Material(); $material->setRawAttributes(['id'=>10,'tenant_id'=>1]);
        $material->setRelation('priceListMaterials',collect());
        $stock=new StockItem(); $stock->setRawAttributes(['id'=>20,'tenant_id'=>1,'material_id'=>10]);
        $stock->setRelation('material',$material); $stock->setRelation('priceListItems',collect());
        $this->assertNull($resolver->resolveUsingPriceList($stock,$list)['price']);
        $material->setRelation('priceListMaterials',collect([new PriceListMaterial(['price_list_id'=>$list->id,'material_id'=>10,'price'=>0])]));
        $this->assertSame(0.0,$resolver->resolveUsingPriceList($stock,$list)['price']);
        $stock->setRelation('priceListItems',collect([new PriceListItem(['price_list_id'=>$list->id,'stock_item_id'=>20,'price'=>12])]));
        $this->assertSame(12.0,$resolver->resolveUsingPriceList($stock,$list)['price']);
        $this->assertSame('stock_item',$resolver->resolveUsingPriceList($stock,$list)['source']);
    }

    public function test_branch_offerings_do_not_inherit_and_currency_mismatch_is_unconfigured()
    {
        Schema::create('material_presentation_branches',function($t) {
            $t->increments('id'); foreach(['tenant_id','company_id','branch_id','material_presentation_id'] as $c) $t->integer($c);
            $t->decimal('price',14,2); $t->string('currency'); $t->boolean('is_active'); $t->timestamps();
        });
        $settings=\Mockery::mock(\App\Services\SettingService::class);
        $settings->shouldReceive('get')->with('finance.base_currency')->andReturn('PEN');
        $this->app->instance(\App\Services\SettingService::class,$settings);
        $service=app(\App\Services\Sales\BranchPresentationService::class);
        $definition=new MaterialPresentation(); $definition->setRawAttributes(['id'=>1,'material_id'=>10,'tenant_id'=>1,'quantity'=>4,'label'=>'Pack','active'=>1,'price'=>999], true);
        MaterialPresentationBranch::create(['tenant_id'=>1,'company_id'=>1,'branch_id'=>1,'material_presentation_id'=>1,'price'=>20,'currency'=>'PEN','is_active'=>1]);
        $this->assertTrue($service->dto($definition,$service->configurations()->first())['active']);
        session()->put('multitenancy.branch_id',8);
        $this->assertNull($service->dto($definition,$service->configurations()->first())['price']);
        $offer=MaterialPresentationBranch::create(['tenant_id'=>1,'company_id'=>1,'branch_id'=>8,'material_presentation_id'=>1,'price'=>30,'currency'=>'PEN','is_active'=>1]);
        $this->assertSame('30.00',$service->dto($definition,$service->configurations()->first())['price']);
        $offer->update(['currency'=>'USD']);
        $dto=$service->dto($definition,$offer);
        $this->assertFalse($dto['configured']); $this->assertFalse($dto['active']); $this->assertNull($dto['price']);
        session()->put('multitenancy.branch_id',1);
        $this->assertSame('20.00',$service->dto($definition,$service->configurations()->first())['price']);
    }
}

