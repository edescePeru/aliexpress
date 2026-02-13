<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransportistaFieldsToShippingGuidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            // Transporte Público: empresa transportista
            $table->string('transportista_doc_type', 2)->nullable();     // "6"
            $table->string('transportista_doc_number', 20)->nullable(); // RUC
            $table->string('transportista_name', 200)->nullable();

            // MTC (lo muestra Nubefact como condicional)
            $table->string('mtc_registration_number', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->dropColumn([
                'transportista_doc_type',
                'transportista_doc_number',
                'transportista_name',
                'mtc_registration_number',
            ]);
        });
    }
}
