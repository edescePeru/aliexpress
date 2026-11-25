<?php

namespace Tests\Feature;

use App\{StockItem, Item, InventoryLevel};
use App\Services\Materials\{MaterialInventoryScope, StockItemScreenAccess};
use Illuminate\Support\Facades\{Auth, DB, Schema};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** R6 mutations run only on disposable SQLite; never on the shared inventory. */
class StockItemRecoveryContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'r6_memory','database.connections.r6_memory'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ],'cache.default'=>'array','session.driver'=>'array']);
        DB::setDefaultConnection('r6_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        foreach (['companies','company_user','branches','branch_user','warehouses','locations','materials','stock_items','company_stock_items','inventory_levels','items','variants'] as $table) {
            Schema::create($table,function($t){
                $t->increments('id');
                foreach(['tenant_id','company_id','branch_id','user_id','material_id','variant_id','stock_item_id','warehouse_id','location_id'] as $column)$t->integer($column)->nullable();
                $t->boolean('is_active')->default(true);$t->boolean('tracks_inventory')->default(true);
                $t->string('code')->nullable();$t->string('state_item')->nullable();
                foreach(['min_alert','max_alert','qty_on_hand','qty_reserved'] as $column)$t->decimal($column,16,4)->default(0);
                $t->timestamps();$t->softDeletes();
            });
        }
        $user=\Mockery::mock(\App\User::class)->makePartial();$user->id=1;$user->tenant_id=1;$user->enable=1;
        $user->shouldReceive('isPlatformAdmin')->andReturn(false);$user->shouldReceive('can')->andReturn(true);
        Auth::setUser($user);session(['multitenancy.tenant_id'=>1,'multitenancy.company_id'=>1,'multitenancy.branch_id'=>1]);
        DB::table('companies')->insert(['id'=>1,'tenant_id'=>1]);DB::table('company_user')->insert(['company_id'=>1,'user_id'=>1]);
        DB::table('branches')->insert(['id'=>1,'company_id'=>1]);DB::table('branch_user')->insert(['branch_id'=>1,'user_id'=>1]);
        foreach([1,2,3,4] as $id){
            $tenant=$id===4?2:1;$company=$id===2?2:1;$branch=$id===3?2:1;
            DB::table('materials')->insert(['id'=>$id,'tenant_id'=>$tenant]);
            DB::table('stock_items')->insert(['id'=>$id,'tenant_id'=>$tenant,'material_id'=>$id]);
            DB::table('company_stock_items')->insert(['tenant_id'=>$tenant,'company_id'=>$company,'stock_item_id'=>$id]);
            DB::table('warehouses')->insert(['id'=>$id,'tenant_id'=>$tenant,'company_id'=>$company,'branch_id'=>$branch]);
            DB::table('locations')->insert(['id'=>$id,'tenant_id'=>$tenant,'company_id'=>$company,'warehouse_id'=>$id]);
            DB::table('inventory_levels')->insert(['id'=>$id,'tenant_id'=>$tenant,'company_id'=>$company,'warehouse_id'=>$id,'location_id'=>$id,'stock_item_id'=>$id,'min_alert'=>1,'max_alert'=>10,'qty_on_hand'=>12,'qty_reserved'=>2]);
            DB::table('items')->insert(['id'=>$id,'tenant_id'=>$tenant,'company_id'=>$company,'warehouse_id'=>$id,'location_id'=>$id,'stock_item_id'=>$id,'code'=>'CODE-'.$id,'state_item'=>'entered']);
        }
    }
    private function request(array $data): Request
    {
        return Request::create('/r6-isolated','POST',array_merge(['material_inventory_tenant'=>1,'material_inventory_company'=>1,'material_inventory_branch'=>1],$data));
    }
    public function test_scope_rejects_foreign_company_tenant_branch_and_missing_items(): void
    {
        $access=app(StockItemScreenAccess::class);
        $this->assertSame([1],$access->items()->pluck('id')->all());
        foreach([2,4,999] as $id){try{$access->stockItem($id);$this->fail('Foreign StockItem allowed');}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$this->assertTrue(true);}}
        foreach([2,3,4,999] as $id){try{$access->item($id);$this->fail('Foreign Item allowed');}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$this->assertTrue(true);}}
    }
    public function test_min_max_accepts_zero_and_precision_without_touching_quantities(): void
    {
        app(\App\Http\Controllers\StockItemController::class)->updateInventoryLevels($this->request(['inventory_levels'=>[['id'=>1,'min_alert'=>'','max_alert'=>'3.125']]]),StockItem::findOrFail(1));
        $row=InventoryLevel::findOrFail(1);$this->assertEquals(0,$row->min_alert);$this->assertEquals(3.125,$row->max_alert);
        $this->assertEquals(12,$row->qty_on_hand);$this->assertEquals(2,$row->qty_reserved);$this->assertSame(0,DB::transactionLevel());
    }
    public function test_invalid_and_mixed_levels_leave_all_values_unchanged(): void
    {
        foreach([[['id'=>1,'min_alert'=>11,'max_alert'=>10]],[['id'=>1,'min_alert'=>-1]],[['id'=>1],['id'=>1]],[['id'=>1,'min_alert'=>2],['id'=>2,'min_alert'=>2]]] as $rows){
            try{app(\App\Http\Controllers\StockItemController::class)->updateInventoryLevels($this->request(['inventory_levels'=>$rows]),StockItem::findOrFail(1));$this->fail('Invalid levels accepted');}catch(ValidationException $e){$this->assertEquals(1,InventoryLevel::findOrFail(1)->min_alert);$this->assertSame(0,DB::transactionLevel());}
        }
    }
    public function test_flags_change_stockitem_and_active_variant_not_company_enable(): void
    {
        DB::table('variants')->insert(['id'=>1,'tenant_id'=>1,'material_id'=>1]);DB::table('stock_items')->where('id',1)->update(['variant_id'=>1]);
        $ctl=app(\App\Http\Controllers\StockItemController::class);
        $ctl->toggleInventory($this->request(['value'=>0]),1);$ctl->toggleActive($this->request(['value'=>0]),1);
        $this->assertEquals(0,StockItem::findOrFail(1)->tracks_inventory);$this->assertEquals(0,StockItem::findOrFail(1)->is_active);
        $this->assertEquals(0,DB::table('variants')->where('id',1)->value('is_active'));$this->assertEquals(1,DB::table('company_stock_items')->where('stock_item_id',1)->value('is_active'));
        $this->assertEquals(12,InventoryLevel::findOrFail(1)->qty_on_hand);
    }
    public function test_invalid_flag_and_stale_context_fail_without_write(): void
    {
        foreach([['value'=>'invalid'],['value'=>0,'material_inventory_company'=>2]] as $payload){try{app(\App\Http\Controllers\StockItemController::class)->toggleInventory($this->request($payload),1);$this->fail('Invalid accepted');}catch(ValidationException $e){$this->assertEquals(1,StockItem::findOrFail(1)->tracks_inventory);}}
    }
    public function test_item_code_trim_uniqueness_and_foreign_id(): void
    {
        $ctl=app(\App\Http\Controllers\MaterialController::class);
        $ctl->updateCode($this->request(['code'=>'  QA-CODE  ']),Item::findOrFail(1));$this->assertSame('QA-CODE',Item::findOrFail(1)->code);
        foreach(['CODE-2','',str_repeat('x',256)] as $value){try{$ctl->updateCode($this->request(['code'=>$value]),Item::findOrFail(1));$this->fail('Invalid code accepted');}catch(ValidationException $e){$this->assertSame('QA-CODE',Item::findOrFail(1)->code);$this->assertSame(0,DB::transactionLevel());}}
        try{$ctl->updateCode($this->request(['code'=>'FOREIGN']),Item::findOrFail(2));$this->fail('Foreign allowed');}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$this->assertSame('CODE-2',Item::findOrFail(2)->code);}
    }
    public function test_multirow_failure_rolls_back_prior_level_write(): void
    {
        DB::table('inventory_levels')->insert(['id'=>5,'tenant_id'=>1,'company_id'=>1,'warehouse_id'=>1,'location_id'=>1,'stock_item_id'=>1,'min_alert'=>1,'max_alert'=>10]);
        try{app(\App\Http\Controllers\StockItemController::class)->updateInventoryLevels($this->request(['inventory_levels'=>[['id'=>1,'min_alert'=>2],['id'=>5,'min_alert'=>20]]]),StockItem::findOrFail(1));$this->fail('Invalid accepted');}catch(ValidationException $e){$this->assertEquals(1,InventoryLevel::findOrFail(1)->min_alert);$this->assertEquals(1,InventoryLevel::findOrFail(5)->min_alert);$this->assertSame(0,DB::transactionLevel());}
    }
}
