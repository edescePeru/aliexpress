<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SupplierCreditPaymentRequests extends Migration
{
    public function up()
    {
        Schema::create('supplier_credit_payment_requests', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('company_id');
            $table->uuid('request_key'); $table->unsignedBigInteger('supplier_credit_id'); $table->unsignedBigInteger('credit_pay_id');
            $table->string('payload_hash', 64); $table->timestamps();
            $table->unique(['tenant_id', 'company_id', 'request_key'], 'supplier_payment_request_unique');
            $table->index('supplier_credit_id');
        });
    }
    public function down()
    {
        if (DB::table('supplier_credit_payment_requests')->exists()) {
            throw new RuntimeException('Hay referencias de operaciones registradas. No se borrarán al revertir la migración.');
        }
        Schema::dropIfExists('supplier_credit_payment_requests');
    }
}
