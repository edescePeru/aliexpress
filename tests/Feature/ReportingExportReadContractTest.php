<?php

namespace Tests\Feature;

use App\Sale;
use App\Services\Sales\UserProfitReport;
use App\Exports\Sheets\GananciasReporteSheet;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ReportingExportReadContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'export_qa', 'database.connections.export_qa' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::setDefaultConnection('export_qa');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        session()->put('multitenancy', ['tenant_id'=>1, 'company_id'=>1, 'branch_id'=>1]);
    }

    public function test_unknown_cost_is_distinct_from_zero_in_report_mapping()
    {
        $report = new UserProfitReport();
        foreach ([null, 0, 3] as $cost) {
            $detail = (object) ['quantity'=>2, 'total'=>10, 'discount'=>0, 'total_cost'=>$cost, 'unit_cost'=>null];
            $sale = new Sale(['created_by'=>9, 'created_by_name'=>'Actor snapshot', 'date_sale'=>'2026-11-04']);
            $sale->setRelation('details', collect([$detail]));
            $row = (new GananciasReporteSheet(null, null, null))->map($sale);
            $this->assertSame('Actor snapshot', $row[2]);
            $this->assertSame($cost === null ? 'Sin costo registrado' : (float) $cost, $row[5]);
            $this->assertSame($cost === null ? 'Sin costo registrado' : (float) (10-$cost), $row[6]);
        }
        $sale->created_by = null; $sale->created_by_name = null; $sale->worker_id = 100;
        $this->assertSame('No registrado', $report->author($sale));
    }

    public function test_report_query_excludes_other_context_currency_annulled_and_reversed()
    {
        Schema::create('sales', function ($t) {
            $t->integer('id');
            foreach (['tenant_id','company_id','branch_id','created_by'] as $c) $t->integer($c)->nullable();
            $t->string('currency'); $t->boolean('state_annulled');
            $t->string('internal_reversal_status')->nullable(); $t->dateTime('date_sale');
        });
        $base = ['tenant_id'=>1,'company_id'=>1,'branch_id'=>1,'created_by'=>9,'currency'=>'PEN',
            'state_annulled'=>0,'internal_reversal_status'=>null,'date_sale'=>'2026-11-04 10:00:00'];
        foreach ([[],['tenant_id'=>2],['company_id'=>2],['branch_id'=>2],['currency'=>'USD'],
            ['state_annulled'=>1],['internal_reversal_status'=>'reversed'],['created_by'=>null],
            ['date_sale'=>'2026-11-05 00:00:00']] as $i=>$change) {
            DB::table('sales')->insert(array_merge($base, ['id'=>$i+1], $change));
        }
        $report = new UserProfitReport();
        $this->assertEquals([1,8], $report->query(null,'04/11/2026','04/11/2026')->pluck('id')->all());
        $this->assertEquals([1], $report->query(9,'04/11/2026','04/11/2026')->pluck('id')->all());
        $this->assertSame([], $report->query(99)->pluck('id')->all());
    }

    public function test_export_collection_does_not_paginate_the_report_query()
    {
        $sales = collect(range(1, 13))->map(function ($id) { return new Sale(['id'=>$id]); });
        $query = \Mockery::mock();
        $query->shouldReceive('with')->once()->andReturnSelf();
        $query->shouldReceive('orderByDesc')->with('date_sale')->once()->andReturnSelf();
        $query->shouldReceive('orderByDesc')->with('id')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($sales);
        $query->shouldNotReceive('paginate'); $query->shouldNotReceive('take');
        $report = \Mockery::mock(UserProfitReport::class);
        $report->shouldReceive('query')->with(9,'04/11/2026','04/11/2026')->once()->andReturn($query);
        $this->app->instance(UserProfitReport::class, $report);
        $result = (new GananciasReporteSheet(9,'04/11/2026','04/11/2026'))->collection();
        $this->assertCount(13, $result);
        $this->assertSame($sales, $result);
    }
}
