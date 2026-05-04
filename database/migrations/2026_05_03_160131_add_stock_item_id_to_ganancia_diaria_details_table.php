<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockItemIdToGananciaDiariaDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ganancia_diaria_details', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->after('material_id');

            $table->foreign('stock_item_id')
                ->references('id')
                ->on('stock_items')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ganancia_diaria_details', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
            $table->dropColumn('stock_item_id');
        });
    }
}
