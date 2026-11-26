<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartialCreditSettlement extends Migration
{
    public function up()
    {
        Schema::table('credit_notes', function (Blueprint $t) {
            $t->unsignedTinyInteger('settlement_version')->nullable();
            $t->decimal('debt_reduction_amount', 18, 2)->nullable();
            $t->decimal('cash_refund_amount', 18, 2)->nullable();
            $t->unsignedBigInteger('refund_cash_box_id')->nullable();
            $t->unsignedBigInteger('refund_subtype_id')->nullable();
            $t->string('partial_request_key', 64)->nullable();
            $t->string('partial_request_hash', 64)->nullable();
            $t->unique(['sale_id', 'partial_request_key'], 'nc_partial_request_unique');
        });
    }
    public function down()
    {
        Schema::table('credit_notes', function (Blueprint $t) {
            $t->dropUnique('nc_partial_request_unique');
            $t->dropColumn(['settlement_version', 'debt_reduction_amount', 'cash_refund_amount', 'refund_cash_box_id',
                'refund_subtype_id', 'partial_request_key', 'partial_request_hash']);
        });
    }
}
