<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSunatShippingIndicatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sunat_shipping_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();         // ej "IndicadorRetornoVehiculoVacio"
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
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
        Schema::dropIfExists('sunat_shipping_indicators');
    }
}
