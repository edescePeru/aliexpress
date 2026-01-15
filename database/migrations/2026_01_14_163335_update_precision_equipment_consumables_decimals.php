<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePrecisionEquipmentConsumablesDecimals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {
            $table->decimal('quantity', 20, 10)->change();
            $table->decimal('price', 20, 10)->change();
            $table->decimal('total', 20, 10)->change();
            $table->decimal('valor_unitario', 20, 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('price', 12, 4)->change();
            $table->decimal('total', 12, 4)->change();
            $table->decimal('valor_unitario', 12, 4)->change();
        });
    }
}
