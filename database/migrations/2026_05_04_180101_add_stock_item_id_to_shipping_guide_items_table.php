<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockItemIdToShippingGuideItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_guide_items', function (Blueprint $table) {
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_guide_items', function (Blueprint $table) {
            //
        });
    }
}
