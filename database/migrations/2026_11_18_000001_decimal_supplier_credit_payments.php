<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DecimalSupplierCreditPayments extends Migration
{
    public function up()
    {
        if (DB::table('credit_pays')->exists()) {
            throw new RuntimeException('Hay pagos históricos. Revisar precisión y conciliación antes de cambiar FLOAT a DECIMAL; no se redondeará historial automáticamente.');
        }
        Schema::table('credit_pays', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable()->default(0)->change();
        });
    }

    public function down()
    {
        if (DB::table('credit_pays')->exists()) {
            throw new RuntimeException('No se puede volver a FLOAT con pagos registrados.');
        }
        Schema::table('credit_pays', function (Blueprint $table) {
            $table->float('amount')->nullable()->default(0)->change();
        });
    }
}
