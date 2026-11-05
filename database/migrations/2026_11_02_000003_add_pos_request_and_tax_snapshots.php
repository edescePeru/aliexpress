<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddPosRequestAndTaxSnapshots extends Migration
{
    public function up()
    {
        Schema::table('sales',function(Blueprint $t){
            $t->string('pos_request_key',32)->nullable();
            $t->unique(['tenant_id','company_id','branch_id','pos_request_key'],'sales_pos_request_context_unique');
        });
        Schema::table('sale_details',function(Blueprint $t){
            $t->unsignedBigInteger('type_tax_id')->nullable();
            $t->string('tax_kind',20)->nullable();
            $t->unsignedSmallInteger('tax_provider_code')->nullable();
            $t->decimal('tax_rate_snapshot',8,4)->nullable();
        });
    }
    public function down()
    {
        Schema::table('sale_details',function(Blueprint $t){$t->dropColumn(['type_tax_id','tax_kind','tax_provider_code','tax_rate_snapshot']);});
        Schema::table('sales',function(Blueprint $t){$t->dropUnique('sales_pos_request_context_unique');$t->dropColumn('pos_request_key');});
    }
}
