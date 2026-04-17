<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteStockLotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_stock_lots', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('quote_detail_id')->nullable();

            $table->unsignedBigInteger('stock_item_id');
            $table->unsignedBigInteger('stock_lot_id');

            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();

            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);

            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('stock_item_id')->references('id')->on('stock_items')->onDelete('cascade');
            $table->foreign('stock_lot_id')->references('id')->on('stock_lots')->onDelete('cascade');

            // Si tienes estas tablas, puedes activar estas foreign keys
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');

            $table->index(['quote_id']);
            $table->index(['quote_detail_id']);
            $table->index(['stock_item_id']);
            $table->index(['stock_lot_id']);
            $table->index(['warehouse_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_stock_lots');
    }
}
