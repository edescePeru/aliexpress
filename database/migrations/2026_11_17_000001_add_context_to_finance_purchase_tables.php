<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContextToFinancePurchaseTables extends Migration
{
    public function up()
    {
        $tables = ['order_purchase_finances', 'order_purchase_finance_details'];
        // Verificar ambas antes de modificar ninguna. No adivinar la atribución histórica.
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Esta migración requiere ambas tablas financieras vacías. Revisar datos históricos antes de continuar.');
            }
            if (Schema::hasColumn($table, 'tenant_id') || Schema::hasColumn($table, 'company_id')) {
                throw new RuntimeException('Ya existen columnas de contexto en '.$table.'. Revisar el estado de la migración.');
            }
        }
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id');
                $t->unsignedBigInteger('company_id');
                $t->index(['tenant_id', 'company_id']);
            });
        }
    }

    public function down()
    {
        foreach (['order_purchase_finances', 'order_purchase_finance_details'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('No se puede quitar el contexto de tablas financieras con datos.');
            }
        }
        foreach (['order_purchase_finance_details', 'order_purchase_finances'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['tenant_id', 'company_id']);
                $t->dropColumn(['tenant_id', 'company_id']);
            });
        }
    }
}
