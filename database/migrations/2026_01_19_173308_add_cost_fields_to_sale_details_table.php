<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostFieldsToSaleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            // costo unitario aplicado al momento de emitir comprobante
            $table->decimal('unit_cost', 18, 6)->nullable()->after('valor_unitario');

            // costo total de la línea (unit_cost * quantity)
            $table->decimal('total_cost', 18, 6)->nullable()->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });
    }
}
