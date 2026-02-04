<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashBoxSubtypeIdToCashMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_box_subtype_id')->nullable()->after('description');

            $table->index(['cash_box_subtype_id']);

            $table->foreign('cash_box_subtype_id')->references('id')->on('cash_box_subtypes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            //
        });
    }
}
