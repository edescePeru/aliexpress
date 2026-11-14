<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGreNumberingToCompanyNubefactConfigs extends Migration
{
    public function up()
    {
        Schema::table('company_nubefact_configs', function (Blueprint $table) {
            $table->string('serie_gre_remitente', 4)->nullable();
            $table->unsignedInteger('gre_remitente_numero_inicial')->nullable();
        });
    }

    public function down()
    {
        Schema::table('company_nubefact_configs', function (Blueprint $table) {
            $table->dropColumn(['serie_gre_remitente', 'gre_remitente_numero_inicial']);
        });
    }
}
