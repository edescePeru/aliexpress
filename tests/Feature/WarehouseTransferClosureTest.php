<?php

namespace Tests\Feature;

use App\Services\Transfers\{WarehouseTransferRequestService, WarehouseTransferPreparationService, WarehouseTransferMovementService, WarehouseTransferAccess};
use App\Services\SettingService;
use App\Transfers\WarehouseTransfer;
use Illuminate\Support\Facades\{DB, Schema, Auth, Gate};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

/** Synthetic prerequisites + actual transfer migration/services. Never connects to shared inventory. */
class WarehouseTransferClosureTest extends TestCase
{
    private $permissions = [];
    private $tables = ['warehouse_transfers','warehouse_transfer_lines','warehouse_transfer_allocations',
        'warehouse_transfer_allocation_items','warehouse_transfer_receipts','warehouse_transfer_receipt_lines',
        'warehouse_transfer_receipt_items','stock_lots','inventory_levels','items'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'transfer_qa','database.connections.transfer_qa'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true],
            'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('transfer_qa');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        session()->put('multitenancy',['tenant_id'=>1,'company_id'=>1,'branch_id'=>1]);
        $definitions = [
            'permissions'=>['name','guard_name'], 'roles'=>['name','guard_name'], 'role_has_permissions'=>['permission_id','role_id'], 'tenants'=>[], 'users'=>['tenant_id','name'],
            'companies'=>['tenant_id','is_active'], 'branches'=>['company_id','is_active'],
            'company_user'=>['company_id','user_id','is_active'], 'branch_user'=>['branch_id','user_id','is_active'],
            'warehouses'=>['tenant_id','company_id','branch_id'],
            'locations'=>['tenant_id','company_id','warehouse_id'],
            'materials'=>['tenant_id','tipo_venta_id','deleted_at'],
            'stock_items'=>['tenant_id','material_id','is_active','tracks_inventory','sku','display_name'],
            'company_stock_items'=>['tenant_id','company_id','stock_item_id','is_active'],
            'entries'=>['tenant_id','company_id','currency_invoice','currency_compra','currency_venta','deleted_at'],
            'detail_entries'=>['tenant_id','company_id','stock_item_id','entry_id','unit_price','total_detail','entered_quantity'],
            'stock_lots'=>['tenant_id','company_id','stock_item_id','warehouse_id','location_id','detail_entry_id',
                'lot_code','expiration_date','unit_cost','qty_on_hand','qty_reserved','created_at','updated_at'],
            'inventory_levels'=>['tenant_id','company_id','stock_item_id','warehouse_id','location_id',
                'qty_on_hand','qty_reserved','min_alert','max_alert','average_cost','last_cost','created_at','updated_at'],
            'items'=>['tenant_id','company_id','stock_item_id','warehouse_id','location_id','stock_lot_id','state_item','updated_at'],
            // Fixture of the kardex view input, not a mock of the cost algorithm or invented destination cost.
            'inventory_movements_view'=>['tenant_id','company_id','stock_item_id','movement_date','movement_id','movement_type','quantity','unit_cost'],
        ];
        foreach ($definitions as $table=>$columns) Schema::create($table, function ($t) use ($columns) {
            $t->bigIncrements('id'); foreach ($columns as $column) $t->string($column)->nullable();
        });
        require_once database_path('migrations/2026_10_19_000003_create_warehouse_transfer_tables.php');
        (new \CreateWarehouseTransferTables())->up();
        foreach ([1,2] as $id) {
            DB::table('tenants')->insert(['id'=>$id]);
            DB::table('users')->insert(['id'=>$id,'tenant_id'=>$id,'name'=>'Synthetic actor '.$id]);
        }
        foreach ([1=>1,2=>1,3=>2] as $id=>$tenant) DB::table('companies')->insert(['id'=>$id,'tenant_id'=>$tenant,'is_active'=>1]);
        foreach ([1=>1,2=>1,3=>1,4=>2,5=>3] as $id=>$company) {
            DB::table('branches')->insert(['id'=>$id,'company_id'=>$company,'is_active'=>1]);
            DB::table('branch_user')->insert(['branch_id'=>$id,'user_id'=>1,'is_active'=>1]);
            DB::table('warehouses')->insert(['id'=>$id,'tenant_id'=>$company===3?2:1,'company_id'=>$company,'branch_id'=>$id]);
            DB::table('locations')->insert(['id'=>$id,'tenant_id'=>$company===3?2:1,'company_id'=>$company,'warehouse_id'=>$id]);
        }
        DB::table('company_user')->insert(['company_id'=>1,'user_id'=>1,'is_active'=>1]);
        DB::table('warehouses')->insert(['id'=>6,'tenant_id'=>1,'company_id'=>1,'branch_id'=>null]);
        DB::table('locations')->insert(['id'=>6,'tenant_id'=>1,'company_id'=>1,'warehouse_id'=>6]);
        $user = new \App\User(); $user->forceFill(['id'=>1,'tenant_id'=>1,'is_platform_admin'=>false]);
        Auth::setUser($user);
        Gate::before(function ($user, $ability) { return in_array($ability,$this->permissions,true); });
        $settings = \Mockery::mock(SettingService::class);
        $settings->shouldReceive('get')->with('finance.base_currency')->andReturn('PEN');
        $this->app->instance(SettingService::class,$settings);
        DB::table('entries')->insert(['id'=>1,'tenant_id'=>1,'company_id'=>1,'currency_invoice'=>'PEN']);
        foreach ([1=>1,2=>3] as $stock=>$type) {
            DB::table('materials')->insert(['id'=>$stock,'tenant_id'=>1,'tipo_venta_id'=>$type]);
            DB::table('stock_items')->insert(['id'=>$stock,'tenant_id'=>1,'material_id'=>$stock,'is_active'=>1,
                'tracks_inventory'=>1,'sku'=>'QA-'.$stock,'display_name'=>'Synthetic '.$stock]);
            DB::table('company_stock_items')->insert(['tenant_id'=>1,'company_id'=>1,'stock_item_id'=>$stock,'is_active'=>1]);
            DB::table('detail_entries')->insert(['id'=>$stock,'tenant_id'=>1,'company_id'=>1,'stock_item_id'=>$stock,
                'entry_id'=>1,'unit_price'=>'5.00','total_detail'=>'50.00','entered_quantity'=>'10.00']);
            DB::table('stock_lots')->insert(['id'=>$stock,'tenant_id'=>1,'company_id'=>1,'stock_item_id'=>$stock,
                'warehouse_id'=>1,'location_id'=>1,'detail_entry_id'=>$stock,'unit_cost'=>'5.00','qty_on_hand'=>'10.00','qty_reserved'=>'0.00']);
            DB::table('inventory_levels')->insert(['id'=>$stock,'tenant_id'=>1,'company_id'=>1,'stock_item_id'=>$stock,
                'warehouse_id'=>1,'location_id'=>1,'qty_on_hand'=>'10.00','qty_reserved'=>'0.00']);
            DB::table('inventory_movements_view')->insert(['tenant_id'=>1,'company_id'=>1,'stock_item_id'=>$stock,
                'movement_date'=>'2026-01-01','movement_id'=>$stock,'movement_type'=>'IN','quantity'=>10,'unit_cost'=>5]);
        }
        foreach (range(1,10) as $id) DB::table('items')->insert(['id'=>$id,'tenant_id'=>1,'company_id'=>1,
            'stock_item_id'=>2,'warehouse_id'=>1,'location_id'=>1,'stock_lot_id'=>2,'state_item'=>'entered']);
    }

    private function request(array $lines = null, array $extra = [])
    {
        return app(WarehouseTransferRequestService::class)->create(array_merge(['request_key'=>(string) Str::uuid(),
            'source_warehouse_id'=>1,'destination_warehouse_id'=>2,
            'lines'=>$lines ?? [['stock_item_id'=>1,'requested_quantity'=>'4.00']]],$extra));
    }
    private function prepare($id) { return app(WarehouseTransferPreparationService::class)->prepare($id); }
    private function dispatch($id) { return app(WarehouseTransferMovementService::class)->dispatch($id); }
    private function cancel($id) { return app(WarehouseTransferPreparationService::class)->cancel($id,'Synthetic QA cancellation'); }
    private function receipt($transfer, $quantity, $key = null) {
        return app(WarehouseTransferMovementService::class)->receive($transfer->id,[
            'request_key'=>$key ?? (string) Str::uuid(),'lines'=>[['transfer_line_id'=>$transfer->lines->first()->id,'quantity'=>$quantity]]]);
    }
    private function snapshot() {
        $data=[]; foreach ($this->tables as $table) $data[$table]=DB::table($table)->orderBy('id')->get()->toJson(); return $data;
    }
    private function rejected(callable $action, $exception = ValidationException::class) {
        $before=$this->snapshot();
        try { $action(); $this->fail('Expected rejection'); }
        catch (\Exception $e) {
            $this->assertInstanceOf($exception,$e,$e->getMessage());
            $request=\Illuminate\Http\Request::create('/qa','GET',[],[],[],['HTTP_ACCEPT'=>'application/json']);
            $response=app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request,$e);
            $this->assertSame($exception===AuthorizationException::class ? 403 : 422,$response->getStatusCode());
        }
        $this->assertSame($before,$this->snapshot(),'Rejection must leave all inventory and transfer rows unchanged');
    }
    private function balances($onHand,$reserved,$destination=0) {
        $level=DB::table('inventory_levels')->where('id',1)->first();
        $lot=DB::table('stock_lots')->where('id',1)->first();
        foreach ([[$onHand,$level->qty_on_hand],[$onHand,$lot->qty_on_hand],[$reserved,$level->qty_reserved],[$reserved,$lot->qty_reserved]] as $pair)
            $this->assertSame(0,bccomp((string)$pair[0],(string)$pair[1],2));
        $this->assertEquals($destination,DB::table('inventory_levels')->where('warehouse_id',2)->sum('qty_on_hand'));
    }

    public function test_full_cycle_partial_receipts_costs_and_duplicate_actions()
    {
        $key=(string)Str::uuid(); $t=$this->request(null,['request_key'=>$key]); $this->balances(10,0);
        $this->assertEquals($t->id,$this->request(null,['request_key'=>$key])->id);
        $this->rejected(function () use ($key) { $this->request([['stock_item_id'=>1,'requested_quantity'=>'3']],['request_key'=>$key]); });
        $this->assertSame('prepared',$this->prepare($t->id)->status); $this->balances(10,4);
        $before=$this->snapshot(); $this->prepare($t->id); $this->assertSame($before,$this->snapshot());
        $this->assertSame('dispatched',$this->dispatch($t->id)->status); $this->balances(6,0);
        $before=$this->snapshot(); $this->dispatch($t->id); $this->assertSame($before,$this->snapshot());
        $key=(string)Str::uuid(); $receipt=$this->receipt($t,'1.50',$key); $this->balances(6,0,1.5);
        $this->assertSame('partially_received',$t->fresh()->status);
        $before=$this->snapshot(); $this->assertEquals($receipt->id,$this->receipt($t,'1.50',$key)->id); $this->assertSame($before,$this->snapshot());
        $this->rejected(function () use ($t,$key) { $this->receipt($t,'2',$key); });
        $this->receipt($t,'2.50'); $this->balances(6,0,4); $this->assertSame('received',$t->fresh()->status);
        $this->assertEquals(5,DB::table('inventory_levels')->where('warehouse_id',2)->value('average_cost'));
        $this->assertEquals(5,DB::table('warehouse_transfer_allocations')->value('base_unit_cost'));
        $this->rejected(function () use ($t) { $this->receipt($t,'1'); });
        $this->rejected(function () use ($t) { $this->cancel($t->id); });
    }

    public function test_invalid_states_quantities_and_reservations_are_atomic()
    {
        foreach (['0','-1','1.001','abc'] as $qty) $this->rejected(function () use ($qty) { $this->request([['stock_item_id'=>1,'requested_quantity'=>$qty]]); });
        $this->rejected(function () { $this->request([['stock_item_id'=>2,'requested_quantity'=>'1.5']]); });
        $t=$this->request();
        $this->rejected(function () use ($t) { $this->dispatch($t->id); });
        $this->rejected(function () use ($t) { $this->receipt($t,'1'); });
        DB::table('stock_lots')->where('id',1)->update(['qty_reserved'=>'8']);
        DB::table('inventory_levels')->where('id',1)->update(['qty_reserved'=>'8']);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $this->cancel($t->id); $before=$this->snapshot(); $this->cancel($t->id); $this->assertSame($before,$this->snapshot());
        $this->balances(10,8); $this->rejected(function () use ($t) { $this->prepare($t->id); });
    }

    public function test_physical_items_move_once_and_cancel_releases_them()
    {
        $t=$this->request([['stock_item_id'=>2,'requested_quantity'=>'2']]); $this->prepare($t->id);
        $this->assertEquals([1,2],DB::table('items')->where('state_item','reserved')->pluck('id')->all());
        $this->cancel($t->id); $this->assertEquals(10,DB::table('items')->where('state_item','entered')->count());
        $t=$this->request([['stock_item_id'=>2,'requested_quantity'=>'2']]); $this->prepare($t->id); $this->dispatch($t->id);
        $this->assertEquals([1,2],DB::table('items')->where('state_item','in_transit')->pluck('id')->all());
        $this->receipt($t,'2');
        $this->assertEquals([1,2],DB::table('items')->where('warehouse_id',2)->where('state_item','entered')->pluck('id')->all());
        $this->assertEquals(10,DB::table('items')->count()); $this->assertEquals(2,DB::table('warehouse_transfer_receipt_items')->count());
    }

    public function test_partial_prepare_dispatch_receive_and_cancel_failures_roll_back()
    {
        $t=$this->request([['stock_item_id'=>1,'requested_quantity'=>'2'],['stock_item_id'=>2,'requested_quantity'=>'2']]);
        DB::table('items')->update(['state_item'=>'scraped']);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        DB::table('items')->update(['state_item'=>'entered']); $this->prepare($t->id);
        DB::table('items')->where('id',2)->update(['company_id'=>2]);
        $this->rejected(function () use ($t) { $this->dispatch($t->id); });
        $this->rejected(function () use ($t) { $this->cancel($t->id); });
        DB::table('items')->where('id',2)->update(['company_id'=>1]); $this->dispatch($t->id);
        $this->rejected(function () use ($t) { $this->receipt($t,'3'); });
        DB::table('locations')->where('id',2)->update(['warehouse_id'=>3]);
        $this->rejected(function () use ($t) { $this->receipt($t,'1'); });
    }

    public function test_late_state_rejections_and_duplicate_dispatch_after_receiving()
    {
        $t=$this->request(); $this->prepare($t->id);
        $this->rejected(function () use ($t) { $this->receipt($t,'1'); });
        $this->dispatch($t->id);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $this->rejected(function () use ($t) { $this->cancel($t->id); });
        $this->receipt($t,'1');
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $this->rejected(function () use ($t) { $this->cancel($t->id); });
        $before=$this->snapshot(); $this->dispatch($t->id); $this->assertSame($before,$this->snapshot());
        $this->receipt($t,'3'); $before=$this->snapshot(); $this->dispatch($t->id); $this->assertSame($before,$this->snapshot());
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $other=$this->request(); $this->cancel($other->id);
        $this->rejected(function () use ($other) { $this->dispatch($other->id); });
        $this->rejected(function () use ($other) { $this->receipt($other,'1'); });
    }

    public function test_receive_item_failure_and_invalid_cost_roll_back()
    {
        $t=$this->request([['stock_item_id'=>2,'requested_quantity'=>'2']]);
        DB::table('detail_entries')->where('id',2)->update(['entry_id'=>999]);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        DB::table('detail_entries')->where('id',2)->update(['entry_id'=>1]);
        $this->prepare($t->id); $this->dispatch($t->id);
        DB::table('items')->where('id',2)->update(['warehouse_id'=>2]);
        $this->rejected(function () use ($t) { $this->receipt($t,'2'); });
    }

    public function test_item_selection_excludes_unavailable_and_foreign_units()
    {
        foreach ([['company_id'=>2],['warehouse_id'=>2],['state_item'=>'reserved'],['state_item'=>'scraped']] as $i=>$change)
            DB::table('items')->where('id',$i+1)->update($change);
        DB::table('items')->where('id',5)->delete(); // Synthetic missing unit only.
        $t=$this->request([['stock_item_id'=>2,'requested_quantity'=>'6']]);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $t=$this->request([['stock_item_id'=>2,'requested_quantity'=>'5']]); $this->prepare($t->id);
        $this->assertEquals([6,7,8,9,10],DB::table('warehouse_transfer_allocation_items')->pluck('item_id')->all());
    }

    public function test_warehouse_identity_duplicate_lines_and_stale_form_context()
    {
        $this->rejected(function () { $this->request(null,['destination_warehouse_id'=>1]); });
        $this->rejected(function () { $this->request([['stock_item_id'=>1,'requested_quantity'=>'1'],['stock_item_id'=>1,'requested_quantity'=>'2']]); });
        DB::table('locations')->insert(['id'=>99,'tenant_id'=>1,'company_id'=>1,'warehouse_id'=>2]);
        $this->rejected(function () { $this->request(); });
        DB::table('locations')->where('id',99)->delete();
        $t=$this->request(); DB::table('warehouses')->where('id',2)->update(['branch_id'=>3]);
        $this->rejected(function () use ($t) { $this->prepare($t->id); });
        $request=new \App\Http\Requests\WarehouseTransferOperationRequest();
        $route=new \Illuminate\Routing\Route('POST','qa',['uses'=>'App\\Http\\Controllers\\WarehouseTransferController@prepare']);
        $request->setRouteResolver(function () use ($route) { return $route; });
        foreach (['form_tenant_id','form_company_id','form_branch_id'] as $field) {
            $payload=['form_tenant_id'=>1,'form_company_id'=>1,'form_branch_id'=>1]; $payload[$field]=999;
            $this->assertTrue(validator($payload,$request->rules())->fails());
        }
    }

    /** Characterizes TRANSFER-01; product lookup excludes this product but write services do not. */
    public function test_non_inventory_stock_item_is_currently_accepted_by_write_services()
    {
        DB::table('stock_items')->where('id',1)->update(['tracks_inventory'=>0]);
        $this->rejected(function () { app(\App\Services\Inventory\WarehouseAvailabilityService::class)->product(1); });
        $t=$this->request(); $this->assertSame('prepared',$this->prepare($t->id)->status);
        $this->balances(10,4);
    }

    public function test_context_warehouse_product_and_permission_guards()
    {
        $t=$this->request(); $access=app(WarehouseTransferAccess::class);
        foreach (['view','prepare','dispatch','receive','cancel'] as $action)
            $this->rejected(function () use ($access,$t,$action) { $access->authorize($action,$t); },AuthorizationException::class);
        $this->rejected(function () use ($access) { $access->authorizeCreate(1,2); },AuthorizationException::class);
        $this->permissions=['list_transfer','create_transfer','prepare_transfer','dispatch_transfer','receive_transfer','cancel_transfer'];
        $this->assertTrue($access->allows('prepare',$t)); $this->assertFalse($access->allows('receive',$t));
        session()->put('multitenancy.branch_id',2);
        $this->assertTrue($access->allows('receive',$t)); $this->assertFalse($access->allows('dispatch',$t));
        $access->authorizeCreate(6,2); $this->assertTrue(true);
        $this->rejected(function () use ($access) { $access->authorizeCreate(1,6); },AuthorizationException::class);
        session()->put('multitenancy.branch_id',3); $this->assertFalse($access->allows('view',$t));
        $this->assertSame([], $access->scopeVisible(WarehouseTransfer::query())->pluck('id')->all());
        $this->permissions[]='manageAll_transfer'; $this->assertTrue($access->allows('view',$t)); $access->authorizeCreate(1,6);
        foreach ([4,5,999] as $warehouse) {
            $this->rejected(function () use ($access,$warehouse) { $access->warehouse($warehouse); },AuthorizationException::class);
            $this->rejected(function () use ($warehouse) { $this->request(null,['destination_warehouse_id'=>$warehouse]); });
        }
        foreach ([['is_active'=>0],['tenant_id'=>2]] as $change) {
            DB::table('stock_items')->where('id',1)->update($change);
            $this->rejected(function () { $this->request(); });
            DB::table('stock_items')->where('id',1)->update(['is_active'=>1,'tenant_id'=>1]);
        }
        DB::table('company_stock_items')->where('stock_item_id',1)->update(['is_active'=>0]);
        $this->rejected(function () { $this->request(); });
        session()->put('multitenancy.company_id',2);
        $this->assertSame([],WarehouseTransfer::query()->pluck('id')->all());
        $this->rejected(function () use ($t) { $this->dispatch($t->id); });
        $this->rejected(function () use ($access) { $access->context(); },AuthorizationException::class);
        session()->put('multitenancy.tenant_id',2); $this->assertSame([],WarehouseTransfer::query()->pluck('id')->all());
    }
}
