<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Disposable fixtures only; exporter is intercepted before any workbook/file creation. */
class DashboardEntryExportContractTest extends TestCase
{
    private $allowed = ['list_entryPurchase','list_entryInventory'];
    private $captured;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'r7_memory','database.connections.r7_memory'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('r7_memory');$this->assertSame(':memory:',DB::connection()->getConfig('database'));
        foreach(['entries','detail_entries','materials','suppliers'] as $table)Schema::create($table,function($t){
            $t->increments('id');foreach(['tenant_id','company_id','supplier_id','entry_id','material_id'] as $c)$t->integer($c)->nullable();
            foreach(['purchase_order','invoice','entry_type','observation','currency_invoice','code','full_name','business_name'] as $c)$t->string($c)->nullable();
            $t->boolean('finance')->default(false);$t->date('date_entry')->nullable();
            foreach(['entered_quantity','unit_price','total_detail'] as $c)$t->decimal($c,16,4)->nullable();
            $t->timestamps();$t->softDeletes();
        });
        session(['multitenancy.tenant_id'=>1,'multitenancy.company_id'=>1]);
        $u=\Mockery::mock(\App\User::class)->makePartial();$u->id=1;$u->tenant_id=1;
        $u->shouldReceive('can')->andReturnUsing(function($p){return in_array($p,$this->allowed,true);});Auth::setUser($u);
        $exporter=\Mockery::mock(\Maatwebsite\Excel\Exporter::class);
        $exporter->shouldReceive('download')->andReturnUsing(function($export,$filename){$this->captured=$export;$this->assertSame('reporteEntradasCompraInventario.xlsx',$filename);return response('SIMULATED XLSX');});
        app()->instance(\Maatwebsite\Excel\Exporter::class,$exporter);
        DB::table('materials')->insert(['id'=>1,'tenant_id'=>1,'code'=>'QA-MAT','full_name'=>'SIMULADO']);
        foreach([[1,1,1,'Por compra',0,'PEN'],[2,1,1,'Inventario',0,'USD'],[3,1,2,'Por compra',0,'PEN'],[4,2,3,'Por compra',0,'PEN'],[5,1,1,'Por compra',1,'PEN'],[6,1,1,'Retacería',0,'PEN']] as $r){
            DB::table('entries')->insert(['id'=>$r[0],'tenant_id'=>$r[1],'company_id'=>$r[2],'entry_type'=>$r[3],'finance'=>$r[4],'currency_invoice'=>$r[5],'purchase_order'=>'ENTRY-'.$r[0],'date_entry'=>'2026-11-20','created_at'=>'2026-11-20 12:00:00']);
            DB::table('detail_entries')->insert(['entry_id'=>$r[0],'tenant_id'=>$r[1],'material_id'=>1,'entered_quantity'=>2,'unit_price'=>3.125,'total_detail'=>6.25]);
        }
    }
    private function runExport(array $query): void
    {
        $r=Request::create('/dashboard/exportar/entradas/almacen/v2/','GET',$query);$r->headers->set('Accept','application/json');app()->instance('request',$r);
        \Illuminate\Support\Facades\Request::clearResolvedInstance('request');
        app(\App\Http\Controllers\EntryController::class)->exportEntriesAlmacen();
    }
    public function test_export_is_company_tenant_and_nonfinance_scoped_without_conversion(): void
    {
        $this->runExport(['typeEntry'=>1]);$rows=$this->captured->entries;
        $this->assertCount(2,$rows);$this->assertEqualsCanonicalizing(['ENTRY-1','ENTRY-2'],array_column($rows,'orden'));
        $this->assertEqualsCanonicalizing(['PEN','USD'],array_column($rows,'moneda'));$this->assertEquals([6.25,6.25],array_column($rows,'total_precio'));
        $this->assertSame(6,DB::table('entries')->count());
    }
    public function test_type_and_date_filters_keep_current_contract(): void
    {
        foreach([2=>'Por compra',3=>'Inventario'] as $type=>$label){$this->runExport(['typeEntry'=>$type,'start'=>'01/11/2026','end'=>'30/11/2026']);$this->assertCount(1,$this->captured->entries);$this->assertSame($label,$this->captured->entries[0]['tipo']);}
        $this->runExport(['typeEntry'=>1,'start'=>'01/10/2026','end'=>'31/10/2026']);$this->assertSame([],$this->captured->entries);
    }
    public function test_types_require_individual_permissions_and_all_requires_both(): void
    {
        foreach([[],['list_entryPurchase'],['list_entryInventory']] as $allowed){$this->allowed=$allowed;
            try{$this->runExport(['typeEntry'=>1]);$this->fail('Both permissions required');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        }
        $this->allowed=['list_entryPurchase'];$this->runExport(['typeEntry'=>2]);$this->assertCount(1,$this->captured->entries);
        try{$this->runExport(['typeEntry'=>3]);$this->fail('Inventory permission required');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
    }
    public function test_invalid_filters_and_missing_context_fail_before_export(): void
    {
        foreach([[],['typeEntry'=>4],['typeEntry'=>1,'start'=>'01/11/2026'],['typeEntry'=>1,'start'=>'2026-11-01','end'=>'2026-11-30'],['typeEntry'=>1,'start'=>'31/11/2026','end'=>'30/11/2026'],['typeEntry'=>1,'start'=>'30/11/2026','end'=>'01/11/2026']] as $q){
            try{$this->runExport($q);$this->fail('Invalid query accepted');}catch(ValidationException $e){$this->assertNull($this->captured);}
        }
        session()->forget('multitenancy.company_id');try{$this->runExport(['typeEntry'=>1]);$this->fail('Context required');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
    }
}
