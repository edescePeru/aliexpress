<?php
namespace Tests\Feature;

use App\Http\Controllers\MaterialController;
use App\Http\Requests\UpdateMaterialRequest;
use App\Services\Materials\{MaterialParameters,MaterialInventoryScope,MaterialCompanyCatalog};
use Illuminate\Support\Facades\{DB,Schema,Auth};
use Tests\TestCase;

/** Real guards/request/controller, synthetic SQLite schema. Never writes shared business data. */
class MaterialEditClosureTest extends TestCase
{
    private $tables=[];
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'material_qa','database.connections.material_qa'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'session.driver'=>'array','cache.default'=>'array','logging.default'=>'null']);
        DB::setDefaultConnection('material_qa');
        $this->assertSame(':memory:',DB::connection()->getConfig('database'));
        session()->put('multitenancy',['tenant_id'=>1,'company_id'=>1,'branch_id'=>1]);
        $definitions=[
            'companies'=>['tenant_id','is_active'], 'company_user'=>['company_id','user_id','is_active'],
            'branches'=>['company_id','is_active'], 'branch_user'=>['branch_id','user_id','is_active'],
            'material_detail_settings'=>['tenant_id','company_id','enabled_sections'],
            'materials'=>array_merge((new \App\Material())->getFillable(),['deleted_at']),
            'variants'=>(new \App\Variant())->getFillable(),
            'stock_items'=>(new \App\StockItem())->getFillable(),
            'items'=>(new \App\Item())->getFillable(),
            'inventory_levels'=>(new \App\InventoryLevel())->getFillable(),
            'company_stock_items'=>['tenant_id','company_id','stock_item_id','is_active'],
            'warehouses'=>['tenant_id','company_id','branch_id'], 'locations'=>['tenant_id','company_id','warehouse_id'],
            'type_taxes'=>['name','is_active','is_default','tax','code'],
            'tipo_ventas'=>['name'],
            'material_discount_quantities'=>['material_id','discount_quantity_id','percentage'],
            // Sentinel relations demonstrate absence of update writes; not their own contract certification.
            'material_presentations'=>['tenant_id','material_id','label','quantity','price','active'],
            'material_presentation_branches'=>['material_presentation_id','branch_id','price','currency','is_active'],
            'sale_details'=>['stock_item_id','description','item_snapshot'],
            'detail_entries'=>['tenant_id','stock_item_id','material_id','material_name'],
            'warehouse_transfer_lines'=>['stock_item_id','sku_snapshot','requested_quantity'],
        ];
        foreach (['unit_measures','categories','subcategories','material_types','subtypes','brands','examplers','generos','tallas','colors','typescraps'] as $table)
            $definitions[$table]=['tenant_id','deleted_at','name','short_name','category_id','subcategory_id','material_type_id','brand_id','length','width'];
        foreach ($definitions as $table=>$columns) {
            $this->tables[]=$table;
            Schema::create($table,function ($t) use ($columns,$table) {
                $t->increments('id'); foreach(array_unique($columns) as $c) $t->string($c)->nullable(); $t->timestamps();
                if($table==='stock_items') {$t->unique(['tenant_id','sku']);$t->unique(['tenant_id','barcode']);}
            });
        }
        $user=new \App\User(); $user->forceFill(['id'=>1,'tenant_id'=>1,'enable'=>1,'is_platform_admin'=>0]); Auth::setUser($user);
        DB::table('companies')->insert(['id'=>1,'tenant_id'=>1,'is_active'=>1]);
        DB::table('company_user')->insert(['company_id'=>1,'user_id'=>1,'is_active'=>1]);
        DB::table('branches')->insert(['id'=>1,'company_id'=>1,'is_active'=>1]);
        DB::table('branch_user')->insert(['branch_id'=>1,'user_id'=>1,'is_active'=>1]);
        DB::table('material_detail_settings')->insert(['id'=>1,'tenant_id'=>1,'company_id'=>1,
            'enabled_sections'=>json_encode(array_keys(config('material_details.sections')))]);
        foreach (['unit_measures','categories','subcategories','material_types','subtypes','brands','examplers','generos','tallas','colors','typescraps'] as $table) {
            DB::table($table)->insert(['id'=>1,'tenant_id'=>1,'name'=>'QA','short_name'=>'QA','length'=>2,'width'=>3]);
            DB::table($table)->insert(['id'=>2,'tenant_id'=>1,'name'=>'QA2','short_name'=>'Q2','length'=>8,'width'=>9]);
            DB::table($table)->insert(['id'=>9,'tenant_id'=>2,'name'=>'Foreign']);
        }
        foreach([1=>1,2=>0] as $id=>$active) DB::table('type_taxes')->insert(['id'=>$id,'is_active'=>$active,'is_default'=>$id===1?1:0]);
        DB::table('tipo_ventas')->insert(['id'=>1,'name'=>'QA']);
        foreach([1=>1,2=>1,9=>2] as $id=>$tenant) {
            DB::table('materials')->insert(['id'=>$id,'tenant_id'=>$tenant,'full_name'=>'Old','description'=>'Old',
                'unit_measure_id'=>1,'brand_id'=>1,'type_tax_id'=>1,'tipo_venta_id'=>1,'typescrap_id'=>1,
                'unit_price'=>17,'list_price'=>19,'image'=>'no_image.png','enable_status'=>1,'inventory'=>1]);
            DB::table('stock_items')->insert(['id'=>$id,'tenant_id'=>$tenant,'material_id'=>$id,'sku'=>'SKU-'.$id,
                'barcode'=>'BAR-'.$id,'is_active'=>1,'tracks_inventory'=>1,'display_name'=>'Old','unit_measure_id'=>1]);
            DB::table('company_stock_items')->insert(['tenant_id'=>$tenant,'company_id'=>$id===1?1:2,'stock_item_id'=>$id,'is_active'=>1]);
        }
        foreach ([1=>[1,1,1],2=>[1,1,null],3=>[1,1,2],4=>[1,2,3],9=>[2,9,9]] as $id=>$ctx) {
            DB::table('warehouses')->insert(['id'=>$id,'tenant_id'=>$ctx[0],'company_id'=>$ctx[1],'branch_id'=>$ctx[2]]);
            DB::table('locations')->insert(['id'=>$id,'tenant_id'=>$ctx[0],'company_id'=>$ctx[1],'warehouse_id'=>$id]);
            DB::table('inventory_levels')->insert(['id'=>$id,'tenant_id'=>$ctx[0],'company_id'=>$ctx[1],'stock_item_id'=>1,
                'warehouse_id'=>$id,'location_id'=>$id,'qty_on_hand'=>10,'qty_reserved'=>2,'min_alert'=>1,'max_alert'=>20,'average_cost'=>7]);
        }
        foreach ([1=>1,2=>2] as $id=>$company) DB::table('items')->insert(['id'=>$id,'tenant_id'=>1,'company_id'=>$company,
            'material_id'=>1,'stock_item_id'=>1,'warehouse_id'=>$id,'location_id'=>$id,'code'=>'SERIAL-'.$id,
            'state_item'=>'entered','typescrap_id'=>1,'length'=>2,'width'=>3]);
        DB::table('material_discount_quantities')->insert(['id'=>1,'material_id'=>1,'discount_quantity_id'=>1,'percentage'=>10]);
        DB::table('material_presentations')->insert(['id'=>1,'tenant_id'=>1,'material_id'=>1,'label'=>'QA','quantity'=>1,'price'=>0,'active'=>1]);
        DB::table('material_presentation_branches')->insert(['material_presentation_id'=>1,'branch_id'=>2,'price'=>25,'currency'=>'USD','is_active'=>1]);
        DB::table('sale_details')->insert(['stock_item_id'=>1,'description'=>'Old sale','item_snapshot'=>'[]']);
        DB::table('detail_entries')->insert(['tenant_id'=>1,'stock_item_id'=>1,'material_id'=>1,'material_name'=>'Old purchase']);
        DB::table('warehouse_transfer_lines')->insert(['stock_item_id'=>1,'sku_snapshot'=>'SKU-1','requested_quantity'=>2]);
    }
    private function row(array $extra=[]) { return array_merge(['stock_item_id'=>1,'sku'=>'SKU-1','codigo_barras'=>'BAR-1','is_active'=>1,'afecto_inventario'=>1],$extra); }
    private function request(array $extra=[],array $rows=null) {
        $policy=app(MaterialParameters::class);
        return UpdateMaterialRequest::create('/dashboard/material/update','POST',array_merge([
            'material_id'=>1,'name'=>'Edited','description'=>'Edited','type_tax_id'=>1,'tipo_variantes'=>'0',
            'variantes_json'=>json_encode($rows??[$this->row()]),
            'material_context_tenant'=>1,'material_context_company'=>1,'material_parameters_version'=>$policy->version($policy->current()),
            'material_inventory_tenant'=>1,'material_inventory_company'=>1,'material_inventory_branch'=>1],$extra));
    }
    private function validator($r) {
        $v=validator($r->all(),$r->rules()); $r->withValidator($v); return $v;
    }
    private function save($r) {
        $response=(new \App\Http\Middleware\MaterialParametersGuard())->handle($r,function ($r) {
            return (new \App\Http\Middleware\MaterialInventoryGuard())->handle($r,function ($r) {
                $v=$this->validator($r); $this->assertFalse($v->fails(),json_encode($v->errors()->toArray()));
                return app(MaterialController::class)->update($r);
            },'form');
        });
        return $response;
    }
    private function data($table) {return DB::table($table)->orderBy('id')->get()->toJson();}
    private function snapshot() {$all=[];foreach($this->tables as $t)$all[$t]=$this->data($t);return $all;}
    private function invalid($r,$field) {$v=$this->validator($r);$this->assertTrue($v->fails());$this->assertTrue($v->errors()->has($field),json_encode($v->errors()->toArray()));}
    private function variants() {
        DB::table('stock_items')->where('id',1)->update(['variant_id'=>1]);
        foreach([1,2] as $id) DB::table('variants')->insert(['id'=>$id,'tenant_id'=>1,'material_id'=>1,'talla_id'=>$id,'color_id'=>$id,'is_active'=>1]);
        DB::table('stock_items')->insert(['id'=>3,'tenant_id'=>1,'material_id'=>1,'variant_id'=>2,'sku'=>'SKU-3','barcode'=>'BAR-3','is_active'=>1,'tracks_inventory'=>1]);
        return $this->row(['variant_id'=>1,'talla_id'=>1,'color_id'=>1]);
    }
    public function test_prices_discounts_parameters_items_presentations_and_history_are_preserved() {
        $before=$this->snapshot();
        $r=$this->save($this->request(['unit_price'=>999,'list_price'=>999,'enable_status'=>0,'inventory'=>0]));
        $this->assertSame(200,$r->getStatusCode()); $m=DB::table('materials')->find(1);
        foreach(['unit_price'=>17,'list_price'=>19,'brand_id'=>1,'unit_measure_id'=>1,'enable_status'=>1,'inventory'=>1] as $key=>$value)$this->assertEquals($value,$m->$key);
        foreach(['material_discount_quantities','items','inventory_levels','material_presentations','material_presentation_branches','sale_details','detail_entries','warehouse_transfer_lines'] as $t)$this->assertSame($before[$t],$this->data($t));
        $this->assertSame(200,$this->save($this->request(['discount'=>[1=>1],'percentage'=>[1=>30]]))->getStatusCode());
        $this->assertSame($before['material_discount_quantities'],$this->data('material_discount_quantities'));
        DB::table('material_detail_settings')->where('id',1)->update(['enabled_sections'=>json_encode(['unit_measure'])]);
        $this->assertSame(200,$this->save($this->request(['brand'=>9]))->getStatusCode());
        $this->assertEquals(1,DB::table('materials')->where('id',1)->value('brand_id'));
    }
    public function test_sku_barcode_change_preserves_ids_history_and_rejects_duplicates() {
        $this->assertSame('SKU-1',\App\DetailEntry::find(1)->material_code);
        $before=$this->snapshot(); $this->assertSame(200,$this->save($this->request([],[$this->row(['sku'=>'NEW','codigo_barras'=>null])]))->getStatusCode());
        $this->assertSame('NEW',\App\DetailEntry::find(1)->material_code);
        $this->assertEquals('NEW',DB::table('stock_items')->where('id',1)->value('sku'));
        $this->assertNull(DB::table('stock_items')->where('id',1)->value('barcode'));
        foreach(['items','sale_details','detail_entries','warehouse_transfer_lines'] as $t)$this->assertSame($before[$t],$this->data($t));
        foreach([['sku'=>null],['sku'=>'SKU-2'],['codigo_barras'=>'BAR-2']] as $change)$this->invalid($this->request([],[$this->row($change)]),'variantes_json');
    }
    public function test_variants_omission_identity_and_foreign_ids() {
        $row=$this->variants(); $before=DB::table('variants')->find(2);$stock=DB::table('stock_items')->find(3);
        $this->assertSame(200,$this->save($this->request(['tipo_variantes'=>'1'],[$row]))->getStatusCode());
        $this->assertEquals($before,DB::table('variants')->find(2));$this->assertEquals($stock,DB::table('stock_items')->find(3));
        $this->assertEquals(2,DB::table('variants')->count());
        foreach([['talla_id'=>2],['color_id'=>2],['talla_id'=>null],['color_id'=>null],['variant_id'=>99],['talla_id'=>9],['color_id'=>9]] as $change)
            $this->invalid($this->request(['tipo_variantes'=>'1'],[array_merge($row,$change)]),'variantes_json');
    }
    public function test_tax_and_foreign_catalog_ids_and_stale_inventory() {
        foreach([999,2] as $tax)$this->invalid($this->request(['type_tax_id'=>$tax]),'type_tax_id');
        DB::table('materials')->where('id',1)->update(['type_tax_id'=>2]);
        $this->assertSame(200,$this->save($this->request(['type_tax_id'=>2]))->getStatusCode());
        foreach(['category','subcategory','brand','material_type','subtype','exampler','unit_measure','typescrap'] as $field)
            $this->invalid($this->request([$field=>9]),$field);
        $this->invalid($this->request(['material_id'=>9]),'material_id');
        $this->invalid($this->request(['material_inventory_branch'=>99]),'context');
        $this->invalid($this->request([],[$this->row(['stock_item_id'=>9])]),'variantes_json');
    }
    public function test_inventory_thresholds_scope_and_known_multiple_location_rejection() {
        foreach([1,2] as $id) {
            $row=$this->row(['inventory_levels'=>[['id'=>$id,'warehouse_id'=>$id,'min_alert'=>3,'max_alert'=>30,'location_id'=>999]]]);
            $this->assertSame(200,$this->save($this->request([],[$row]))->getStatusCode());
            $level=DB::table('inventory_levels')->find($id); $this->assertEquals(3,$level->min_alert);$this->assertEquals($id,$level->location_id);$this->assertEquals(10,$level->qty_on_hand);$this->assertEquals(2,$level->qty_reserved);
        }
        foreach([3,4,9] as $id)$this->invalid($this->request([],[$this->row(['inventory_levels'=>[['id'=>$id,'warehouse_id'=>$id]]])]),'variantes_json');
        foreach([[-1,10],[20,10]] as $limits)$this->invalid($this->request([],[$this->row(['inventory_levels'=>[['id'=>1,'warehouse_id'=>1,'min_alert'=>$limits[0],'max_alert'=>$limits[1]]]])]),'variantes_json');
        $this->assertSame(200,$this->save($this->request([],[$this->row(['inventory_levels'=>[['id'=>1,'warehouse_id'=>1,'min_alert'=>null,'max_alert'=>null]]])]))->getStatusCode());
        $this->assertEquals(0,DB::table('inventory_levels')->where('id',1)->value('min_alert'));
        DB::table('locations')->insert(['id'=>5,'tenant_id'=>1,'company_id'=>1,'warehouse_id'=>1]);
        DB::table('inventory_levels')->insert(['id'=>5,'tenant_id'=>1,'company_id'=>1,'stock_item_id'=>1,'warehouse_id'=>1,'location_id'=>5,'min_alert'=>0,'max_alert'=>0]);
        $levels=[['id'=>1,'warehouse_id'=>1,'min_alert'=>1,'max_alert'=>2],['id'=>5,'warehouse_id'=>1,'min_alert'=>3,'max_alert'=>4]];
        $v=$this->validator($this->request([],[$this->row(['inventory_levels'=>$levels])])); $this->assertTrue($v->fails());
        $this->assertStringContainsString('almacenes repetidos',implode(' ',$v->errors()->all()));
        DB::transaction(function () use ($levels) {app(MaterialInventoryScope::class)->sync(\App\StockItem::find(1),$levels);});
        $this->assertEquals(3,DB::table('inventory_levels')->where('id',5)->value('min_alert'));
        DB::table('locations')->where('id',1)->update(['warehouse_id'=>2]);
        $this->invalid($this->request([],[$this->row(['inventory_levels'=>[['id'=>1,'warehouse_id'=>1]]])]),'variantes_json');
    }
    public function test_current_flags_allow_disabling_with_stock_and_coerce_invalid_strings() {
        $before=$this->data('inventory_levels');
        $this->assertSame(200,$this->save($this->request([],[$this->row(['is_active'=>0,'afecto_inventario'=>0])]))->getStatusCode());
        $this->assertEquals(0,DB::table('stock_items')->where('id',1)->value('tracks_inventory'));
        $this->assertSame($before,$this->data('inventory_levels'));
        foreach(['is_active','afecto_inventario'] as $field) {
            $this->invalid($this->request([],[$this->row([$field=>2])]),'variantes_json');
            $this->assertSame(200,$this->save($this->request([],[$this->row([$field=>'invalid'])]))->getStatusCode());
            $this->assertEquals(0,DB::table('stock_items')->where('id',1)->value($field==='is_active'?$field:'tracks_inventory'));
        }
    }
    public function test_retaceria_currently_propagates_to_other_company_items() {
        DB::table('items')->where('id',2)->update(['state_item'=>'exited']);
        $this->assertSame(200,$this->save($this->request(['typescrap'=>2]))->getStatusCode());
        $this->assertEquals(8,DB::table('items')->where('id',2)->value('length'));
        $this->assertEquals(2,DB::table('items')->where('id',2)->value('company_id'));
    }
    public function test_current_update_accepts_material_enabled_only_in_other_company() {
        $this->assertSame([],app(MaterialCompanyCatalog::class)->apply(\App\Material::query())->where('id',2)->pluck('id')->all());
        $this->assertSame(200,$this->save($this->request(['material_id'=>2],[$this->row(['stock_item_id'=>2,'sku'=>'SKU-2','codigo_barras'=>'BAR-2'])]))->getStatusCode());
        $this->assertSame('Edited',DB::table('materials')->where('id',2)->value('full_name'));
    }
    public function test_controller_rolls_back_intermediate_material_variant_and_inventory_writes() {
        $row=$this->variants(); $before=$this->snapshot();
        $request=$this->request(['tipo_variantes'=>'1','typescrap'=>2],[array_merge($row,['inventory_levels'=>[['id'=>1,'warehouse_id'=>1,'min_alert'=>2,'max_alert'=>10]]]),
            $this->row(['variant_id'=>2,'talla_id'=>2,'color_id'=>2,'sku'=>'SKU-3','codigo_barras'=>'BAR-3','inventory_levels'=>[['id'=>999,'warehouse_id'=>1]]])]);
        // Bypass request ONLY to model inventory becoming invalid after validation. Controller/service must roll back.
        $response=app(MaterialController::class)->update($request);
        $this->assertSame(422,$response->getStatusCode());$this->assertSame($before,$this->snapshot());
    }
    public function test_omitted_flags_reactivate_and_inventory_change_keeps_physical_history() {
        $before=$this->snapshot();
        $this->assertSame(200,$this->save($this->request([],[$this->row(['is_active'=>0,'afecto_inventario'=>0])]))->getStatusCode());
        foreach(['items','inventory_levels','warehouse_transfer_lines','sale_details','detail_entries'] as $t)$this->assertSame($before[$t],$this->data($t));
        $row=$this->row();unset($row['is_active'],$row['afecto_inventario']);
        $this->assertSame(200,$this->save($this->request([],[$row]))->getStatusCode());
        $this->assertEquals(1,DB::table('stock_items')->where('id',1)->value('is_active'));
        $this->assertEquals(1,DB::table('stock_items')->where('id',1)->value('tracks_inventory'));
    }
    public function test_stale_parameters_and_unauthorized_company_guard_fail_closed() {
        $before=$this->snapshot();
        $r=$this->request(['material_parameters_version'=>'stale']);
        $response=app(MaterialController::class)->update($r);
        $this->assertSame(422,$response->getStatusCode());$this->assertSame($before,$this->snapshot());
        DB::table('company_user')->where('user_id',1)->update(['is_active'=>0]);
        try {app(MaterialInventoryScope::class)->context();$this->fail('Expected 403');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
    }
}
