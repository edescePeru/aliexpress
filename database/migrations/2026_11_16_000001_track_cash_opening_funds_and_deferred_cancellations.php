<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrackCashOpeningFundsAndDeferredCancellations extends Migration
{
    public function up()
    {
        Schema::create('cash_opening_funds', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_register_id')->primary();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('cash_box_id');
            $table->unsignedBigInteger('user_id');
            $table->string('origin', 16);
            $table->decimal('amount', 18, 2);
            $table->string('provenance', 150)->nullable();
            $table->string('observation', 500)->nullable();
            $table->dateTime('created_at');
            $table->index(['tenant_id', 'company_id', 'created_at'], 'cash_funds_company_date_idx');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dateTime('deferred_cancelled_at')->nullable();
            $table->unsignedBigInteger('deferred_cancelled_by')->nullable();
            $table->string('deferred_cancellation_reason', 40)->nullable();
        });
    }

    public function down()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropColumn(['deferred_cancelled_at', 'deferred_cancelled_by', 'deferred_cancellation_reason']);
        });
        Schema::dropIfExists('cash_opening_funds');
    }
}
