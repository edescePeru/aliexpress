<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\{DB,Schema};
use App\{SupplierCredit,CreditPay};
use App\Services\Purchases\{SupplierCreditBalance,SupplierCreditExpiration};
use Carbon\Carbon;
class SupplierCreditReadPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'credit_read_memory','database.connections.credit_read_memory'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'session.driver'=>'array','cache.default'=>'array']);
        DB::setDefaultConnection('credit_read_memory');
        $this->assertSame(':memory:',DB::connection()->getConfig('database'));
        Schema::create('supplier_credits',function($t){$t->increments('id');$t->integer('tenant_id');$t->integer('company_id');$t->integer('entry_id');$t->decimal('total_soles')->nullable();$t->decimal('total_dollars')->nullable();$t->decimal('advance')->default(0);$t->unique(['tenant_id','company_id','entry_id']);});
        Schema::create('credit_pays',function($t){$t->increments('id');$t->integer('supplier_credit_id');$t->decimal('amount');});
        foreach([[1,1,1],[2,1,2],[3,3,3]] as $c){DB::table('supplier_credits')->insert(['id'=>$c[0],'tenant_id'=>$c[1],'company_id'=>$c[2],'entry_id'=>$c[0],'total_soles'=>100]);DB::table('credit_pays')->insert(['id'=>$c[0],'supplier_credit_id'=>$c[0],'amount'=>20]);}
        $this->context(1,1);
    }
    private function context($tenant,$company){session()->put('multitenancy',['tenant_id'=>$tenant,'company_id'=>$company]);}
    public function test_credit_and_payment_reads_are_company_scoped_and_fail_closed()
    {
        foreach([[1,1,1],[1,2,2],[3,3,3]] as $c){$this->context($c[0],$c[1]);$this->assertSame([$c[2]],SupplierCredit::pluck('id')->all());$this->assertSame([$c[2]],CreditPay::pluck('id')->all());$this->assertNull(SupplierCredit::find(999));}
        $this->context(null,null);$this->assertSame(0,SupplierCredit::count());$this->assertSame(0,CreditPay::count());
    }
    public function test_balance_is_backend_authority_and_rejects_cross_context()
    {
        $credit=SupplierCredit::findOrFail(1);$balance=SupplierCreditBalance::forCredit($credit);
        $this->assertSame('PEN',$balance['currency']);$this->assertEquals(100,$balance['total']);$this->assertEquals(20,$balance['paid']);$this->assertEquals(80,$balance['balance']);$this->assertFalse($balance['legacy_review']);
        $this->context(1,2);$this->expectException(\Illuminate\Validation\ValidationException::class);SupplierCreditBalance::forCredit($credit);
    }
    public function test_expiration_evaluation_does_not_persist_and_respects_paid_state()
    {
        $credit=new SupplierCredit();$credit->date_expiration='2026-11-17';$credit->state_credit='outstanding';$credit->state_pay='pending';
        $today=Carbon::parse('2026-11-18','America/Lima');$r=SupplierCreditExpiration::evaluate($credit,7,$today);
        $this->assertSame('expired',$r['state']);$this->assertSame(-1,$r['days']);$this->assertSame('outstanding',$credit->state_credit);
        $credit->date_expiration='2026-11-20';$this->assertSame('by_expire',SupplierCreditExpiration::evaluate($credit,7,$today)['state']);
        $credit->state_credit='paid_out';$this->assertFalse(SupplierCreditExpiration::evaluate($credit,7,$today)['eligible']);
        $this->assertSame(3,DB::table('supplier_credits')->count());
    }
    public function test_unique_credit_entry_rejects_duplicate_in_private_fixture_schema()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('supplier_credits')->insert(['tenant_id'=>1,'company_id'=>1,'entry_id'=>1,'total_soles'=>100]);
    }
}