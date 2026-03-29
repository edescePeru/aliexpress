<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('detail_entry_id')->nullable()->constrained('detail_entries')->nullOnDelete();
            $table->string('lot_code')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->decimal('qty_on_hand', 12,2)->nullable()->default(0.00);
            $table->decimal('qty_reserved', 12,2)->nullable()->default(0.00);
            $table->decimal('unit_cost', 12,2)->nullable()->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_lots');
    }
}
