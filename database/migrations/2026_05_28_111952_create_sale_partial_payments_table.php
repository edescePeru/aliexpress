<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePartialPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_partial_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);

            $table->tinyInteger('state')->default(1); // 1 activo, 0 eliminado/anulado

            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('cash_movement_id')->references('id')->on('cash_movements');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_partial_payments');
    }
}
