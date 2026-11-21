<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GreRecoveryAndPrivateDiagnostics extends Migration
{
    public function up()
    {
        if (Schema::hasTable('shipping_guide_recovery_attempts') || Schema::hasTable('shipping_guide_provider_failures')) {
            throw new \RuntimeException('Existe una tabla de recuperación/diagnóstico. Revisar su versión antes de instalar; no borrar registros.');
        }
        Schema::create('shipping_guide_recovery_attempts', function (Blueprint $table) {
            $table->string('id',32)->primary();
            $table->unsignedBigInteger('shipping_guide_id')->unique();
            $table->unsignedBigInteger('tenant_id');$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');$table->string('state',40);$table->string('phase',16);
            $table->longText('before_encrypted');
            $table->string('diagnostic_reference',24)->nullable();$table->unsignedSmallInteger('http_status')->nullable();$table->string('provider_code',8)->nullable();
            $table->timestamps();
        });
        Schema::create('shipping_guide_provider_failures', function (Blueprint $table) {
            $table->string('reference',24)->primary();$table->unsignedBigInteger('shipping_guide_id');
            $table->unsignedBigInteger('tenant_id');$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('branch_id');
            $table->string('operation',16);$table->unsignedSmallInteger('http_status');$table->string('provider_code',8)->nullable();
            $table->longText('detail_encrypted');$table->timestamp('created_at');
        });
    }
    public function down()
    {
        throw new \RuntimeException('Conservar evidencia e intentos de envío. Revisar y respaldar antes de retirar estas tablas.');
    }
}
