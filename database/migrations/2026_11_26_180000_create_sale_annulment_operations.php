<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSaleAnnulmentOperations extends Migration {
 public function up(){
  Schema::create('sale_annulment_operations',function(Blueprint $t){
   $t->bigIncrements('id');foreach(['tenant_id','company_id','branch_id','sale_id','requested_by']as$c)$t->unsignedBigInteger($c);
   $t->unique('sale_id');$t->string('reference',20)->unique();$t->string('status',32);$t->string('reversal_state',32)->default('none');
   $t->unsignedBigInteger('refund_cash_box_id')->nullable();$t->unsignedBigInteger('refund_subtype_id')->nullable();$t->unsignedBigInteger('refund_movement_id')->nullable();
   $t->decimal('refund_amount',18,2)->default(0);$t->decimal('debt_cancelled',18,2)->default(0);$t->longText('request_payload')->nullable();$t->longText('response_payload')->nullable();$t->longText('financial_evidence')->nullable();$t->string('diagnostic_reference',32)->nullable();$t->timestamps();
   $t->index(['tenant_id','company_id','branch_id']);
  });
  Schema::create('sale_annulment_attempts',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('operation_id');$t->string('reference',20);$t->string('action',32);$t->string('status',32);$t->longText('request_payload');$t->longText('response_payload')->nullable();$t->unsignedSmallInteger('http_status')->nullable();$t->timestamps();$t->index(['operation_id','id']);
  });
  Schema::create('sale_annulment_stock_returns',function(Blueprint $t){
   $t->bigIncrements('id');foreach(['tenant_id','company_id','branch_id','sale_id','operation_id','sale_detail_id','output_detail_id','stock_item_id','material_id','stock_lot_id','warehouse_id','location_id','recorded_by']as$c)$t->unsignedBigInteger($c);
   $t->unsignedBigInteger('item_id')->nullable();$t->unique('output_detail_id');$t->decimal('quantity',20,6);$t->decimal('unit_cost',24,10);$t->decimal('total_cost',24,10);$t->string('base_currency',3)->nullable();$t->string('cost_source',32);$t->string('currency_source',32);$t->dateTime('movement_date',6);$t->timestamps();$t->index(['tenant_id','company_id','stock_item_id','movement_date'],'sale_annul_stock_context');
  });
 }
 public function down(){Schema::dropIfExists('sale_annulment_stock_returns');Schema::dropIfExists('sale_annulment_attempts');Schema::dropIfExists('sale_annulment_operations');}
}
