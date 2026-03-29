<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('qty_on_hand', 12,2)->nullable()->default(0.00);
            $table->decimal('qty_reserved', 12,2)->nullable()->default(0.00);
            $table->integer('min_alert')->nullable()->default(0);
            $table->integer('max_alert')->nullable()->default(0);
            $table->decimal('average_cost', 12,2)->nullable()->default(0.00);
            $table->decimal('last_cost', 12,2)->nullable()->default(0.00);
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
        Schema::dropIfExists('inventory_levels');
    }
}
