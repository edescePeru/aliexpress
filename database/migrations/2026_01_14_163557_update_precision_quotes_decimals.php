<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePrecisionQuotesDecimals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('descuento', 20, 10)->change();
            $table->decimal('gravada', 20, 10)->change();
            $table->decimal('igv_total', 20, 10)->change();
            $table->decimal('total_importe', 20, 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('descuento', 12, 4)->change();
            $table->decimal('gravada', 12, 4)->change();
            $table->decimal('igv_total', 12, 4)->change();
            $table->decimal('total_importe', 12, 4)->change();
        });
    }
}
