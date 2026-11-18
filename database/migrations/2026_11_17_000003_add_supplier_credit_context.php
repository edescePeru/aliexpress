<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSupplierCreditContext extends Migration
{
    public function up()
    {
        if (DB::table('supplier_credits')->exists() || DB::table('credit_pays')->exists()) {
            throw new RuntimeException('Existen créditos o pagos históricos. Requieren diagnóstico de pertenencia antes de aplicar esta migración.');
        }
        if (Schema::hasColumn('supplier_credits', 'tenant_id') || Schema::hasColumn('supplier_credits', 'company_id')) {
            throw new RuntimeException('Ya existen columnas de contexto; revisar el estado antes de continuar.');
        }
        Schema::table('supplier_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->index(['tenant_id', 'company_id']);
        });
    }

    public function down()
    {
        if (DB::table('supplier_credits')->exists() || DB::table('credit_pays')->exists()) {
            throw new RuntimeException('No se puede quitar el contexto con créditos o pagos registrados.');
        }
        Schema::table('supplier_credits', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'company_id']);
            $table->dropColumn(['tenant_id', 'company_id']);
        });
    }
}
