<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyNubefactConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('company_nubefact_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id')->unique();
            // RUC al que corresponde esta conexión: detectar cambio de emisor.
            $table->string('ruc', 11);
            $table->text('api_url');
            $table->text('api_token_encrypted');
            $table->boolean('is_enabled')->default(false);
            $table->string('serie_factura', 4)->default('FFF1');
            $table->string('serie_boleta', 4)->default('BBB1');
            $table->string('serie_nc_factura', 4)->nullable();
            $table->string('serie_nc_boleta', 4)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id'], 'company_nubefact_context_idx');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
        });
        // No leer .env ni copiar tokens durante una migración de estructura.
    }

    public function down()
    {
        Schema::dropIfExists('company_nubefact_configs');
    }
}
