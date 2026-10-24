<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmissionTrackingToSales extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Default conservador mientras existen escritores legacy.
            $table->string('emission_status', 32)->default('unknown');
            // UUID estable de la operación. No generar otro UUID en un reintento.
            $table->string('emission_key', 36)->nullable();
            $table->unique('emission_key', 'sales_emission_key_unique');
            $table->index(
                ['tenant_id', 'company_id', 'emission_status'],
                'sales_context_emission_idx'
            );
        });

        Schema::create('sale_emission_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('attempt_key', 36)->unique();
            // Copia de sales.emission_key: identifica la operación enviada.
            $table->string('emission_key', 36);
            $table->string('status', 32)->default('processing');
            $table->string('failure_stage', 32)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();

            $table->string('document_type', 2);
            $table->string('document_series', 20)->nullable();
            $table->string('document_number', 30)->nullable();

            // Payload y respuesta sin tokens, cabeceras ni credenciales.
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'status'], 'sale_emission_attempts_sale_status_idx');
            $table->index(
                ['tenant_id', 'company_id', 'status'],
                'sale_emission_attempts_context_status_idx'
            );
            $table->index('emission_key', 'sale_emission_attempts_operation_idx');

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_emission_attempts');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_emission_key_unique');
            $table->dropIndex('sales_context_emission_idx');
            $table->dropColumn(['emission_status', 'emission_key']);
        });
    }
}
