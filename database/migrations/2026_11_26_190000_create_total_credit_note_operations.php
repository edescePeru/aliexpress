<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateTotalCreditNoteOperations extends Migration {
 public function up(){
  Schema::create('sale_total_credit_operations',function(Blueprint $t){
   $t->bigIncrements('id');foreach(['tenant_id','company_id','branch_id','sale_id','credit_note_id','requested_by']as$c)$t->unsignedBigInteger($c);
   $t->unique('sale_id');$t->unique('credit_note_id');$t->string('reference',64)->unique();$t->string('request_key',64)->unique();$t->string('request_hash',64)->nullable();$t->string('connection_fingerprint',64)->nullable();$t->string('status',32);$t->unsignedBigInteger('reversal_operation_id')->nullable();$t->longText('generation_payload')->nullable();$t->longText('financial_data')->nullable();$t->string('diagnostic_reference',32)->nullable();$t->timestamps();$t->index(['tenant_id','company_id','branch_id'],'total_nc_context');
  });
  Schema::create('sale_total_credit_attempts',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('operation_id');$t->string('action',32);$t->string('status',32);$t->longText('request_payload');$t->longText('response_payload')->nullable();$t->unsignedSmallInteger('http_status')->nullable();$t->timestamps();$t->index(['operation_id','id'],'total_nc_attempt_operation');});
  Schema::table('sale_annulment_operations',function(Blueprint $t){$t->unsignedBigInteger('credit_note_id')->nullable()->unique();});
 }
 public function down(){Schema::table('sale_annulment_operations',function(Blueprint $t){$t->dropUnique(['credit_note_id']);$t->dropColumn('credit_note_id');});Schema::dropIfExists('sale_total_credit_attempts');Schema::dropIfExists('sale_total_credit_operations');}
}
