<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockItemIdToOrderPurchaseDetailsAndMaterialOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_purchase_details', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->after('material_id');
        });

        Schema::table('material_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->after('material_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_purchase_details', function (Blueprint $table) {
            $table->dropColumn('stock_item_id');
        });

        Schema::table('material_orders', function (Blueprint $table) {
            $table->dropColumn('stock_item_id');
        });
    }
}
