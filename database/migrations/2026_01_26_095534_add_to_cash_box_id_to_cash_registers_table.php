<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToCashBoxIdToCashRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_box_id')->nullable()->after('id');

            $table->index(['cash_box_id']);
            $table->foreign('cash_box_id')->references('id')->on('cash_boxes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_box_id_to_cash_registers', function (Blueprint $table) {
            //
        });
    }
}
