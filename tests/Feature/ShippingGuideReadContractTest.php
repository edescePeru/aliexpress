<?php

namespace Tests\Feature;

use App\ShippingGuide;
use App\Http\Controllers\ShippingGuideController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ShippingGuideReadContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated fixtures only: never migrate or write the shared MySQL database.
        config(['database.default'=>'guide_memory', 'database.connections.guide_memory'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'session.driver'=>'array']);
        DB::setDefaultConnection('guide_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        Schema::create('shipping_guides', function ($t) {
            $t->increments('id');
            foreach (['tenant_id','company_id','branch_id','tipo_de_comprobante'] as $column) $t->integer($column);
            foreach (['serie','numero','fecha_emision','motivo_traslado_code'] as $column) $t->string($column);
        });
        Schema::create('transfer_reasons', function ($t) { $t->string('code'); $t->string('name'); });
        foreach (['shipping_guide_items','shipping_guide_vehicles','shipping_guide_drivers'] as $table) {
            Schema::create($table, function ($t) { $t->increments('id'); $t->integer('shipping_guide_id'); });
        }
        foreach ([[1,1,1,7],[1,1,8,7],[1,2,2,7],[3,3,4,7],[1,1,1,8]] as $i=>$scope) {
            DB::table('shipping_guides')->insert(['id'=>$i+1,'tenant_id'=>$scope[0],'company_id'=>$scope[1],
                'branch_id'=>$scope[2],'tipo_de_comprobante'=>$scope[3],'serie'=>'TEST','numero'=>(string)($i+1),
                'fecha_emision'=>'2026-11-14','motivo_traslado_code'=>'01']);
        }
    }

    public function test_list_and_detail_restrict_every_context_dimension_and_document_type()
    {
        $controller = app(ShippingGuideController::class);
        foreach ([[1,1,1,1],[1,1,8,2],[1,2,2,3],[3,3,4,4]] as $context) {
            session()->put('multitenancy',['tenant_id'=>$context[0],'company_id'=>$context[1],'branch_id'=>$context[2]]);
            $result = $controller->index(new Request(['tenant_id'=>99,'company_id'=>99,'branch_id'=>99]))->getData(true);
            $this->assertSame([$context[3]], array_column($result['data']['data'], 'id'));
            $own = $controller->show(ShippingGuide::findOrFail($context[3]))->getData()['guide'];
            $this->assertSame($context[3], $own->id);
            foreach (array_diff([1,2,3,4,5,999999], [$context[3]]) as $id) {
                $bound = new ShippingGuide(); $bound->id = $id;
                try { $controller->show($bound); $this->fail('Foreign/missing guide must be rejected'); }
                catch (ModelNotFoundException $e) { $this->assertSame(ShippingGuide::class, $e->getModel()); }
            }
        }
    }

    public function test_real_filters_and_missing_context_fail_closed()
    {
        session()->put('multitenancy',['tenant_id'=>1,'company_id'=>1,'branch_id'=>1]);
        $controller = app(ShippingGuideController::class);
        foreach ([['buscar_doc'=>'TEST-1', 'expected'=>1],['buscar_doc'=>'TEST-2','expected'=>0],
            ['date_from'=>'2026-11-15','expected'=>0],['date_to'=>'2026-11-13','expected'=>0]] as $case) {
            $expected=$case['expected']; unset($case['expected']);
            $this->assertSame($expected,$controller->index(new Request($case))->getData(true)['data']['total']);
        }
        session()->forget('multitenancy');
        $this->expectException(\RuntimeException::class);
        $controller->index(new Request());
    }
}
