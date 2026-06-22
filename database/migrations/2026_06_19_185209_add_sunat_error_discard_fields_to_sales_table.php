<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSunatErrorDiscardFieldsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            /*
             * Permite ocultar de la bandeja de errores
             * ventas ya solucionadas manualmente.
             */
            $table->timestamp('sunat_error_discarded_at')->nullable()
                ->after('sunat_message');

            $table->unsignedBigInteger('sunat_error_discarded_by')->nullable()
                ->after('sunat_error_discarded_at');

            $table->text('sunat_error_discard_reason')->nullable()
                ->after('sunat_error_discarded_by');

            $table->foreign('sunat_error_discarded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
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
            $table->dropForeign(['sunat_error_discarded_by']);

            $table->dropColumn([
                'sunat_error_discarded_at',
                'sunat_error_discarded_by',
                'sunat_error_discard_reason'
            ]);
        });
    }
}
