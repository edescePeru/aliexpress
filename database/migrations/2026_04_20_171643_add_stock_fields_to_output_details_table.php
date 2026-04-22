<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockFieldsToOutputDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('output_details', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->after('material_id');
            $table->unsignedBigInteger('stock_lot_id')->nullable()->after('stock_item_id');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('stock_lot_id');
            $table->unsignedBigInteger('location_id')->nullable()->after('warehouse_id');

            $table->index('stock_item_id');
            $table->index('stock_lot_id');
            $table->index('warehouse_id');
            $table->index('location_id');

            $table->foreign('stock_item_id')
                ->references('id')
                ->on('stock_items')
                ->onDelete('set null');

            $table->foreign('stock_lot_id')
                ->references('id')
                ->on('stock_lots')
                ->onDelete('set null');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
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
        Schema::table('output_details', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
            $table->dropForeign(['stock_lot_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['location_id']);

            $table->dropIndex(['stock_item_id']);
            $table->dropIndex(['stock_lot_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['location_id']);

            $table->dropColumn([
                'stock_item_id',
                'stock_lot_id',
                'warehouse_id',
                'location_id',
            ]);
        });
    }
}
