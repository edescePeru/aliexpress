<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalsDecimalesPrecisionToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_descuentos', 20, 10)->change();
            $table->decimal('op_exonerada', 20, 10)->change();
            $table->decimal('op_inafecta', 20, 10)->change();
            $table->decimal('op_gravada', 20, 10)->change();
            $table->decimal('igv', 20, 10)->change();
            $table->decimal('importe_total', 20, 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
}
