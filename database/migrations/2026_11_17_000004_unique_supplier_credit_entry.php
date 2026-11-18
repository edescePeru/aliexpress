<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UniqueSupplierCreditEntry extends Migration
{
    public function up()
    {
        $duplicates = DB::table('supplier_credits')->select('tenant_id', 'company_id', 'entry_id')
            ->whereNotNull('entry_id')->groupBy('tenant_id', 'company_id', 'entry_id')->havingRaw('COUNT(*) > 1')->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Hay entradas con más de un crédito. Revisar duplicados y pagos antes de crear la restricción; no se elimina ni fusiona historial automáticamente.');
        }
        Schema::table('supplier_credits', function (Blueprint $table) {
            $table->unique(['tenant_id', 'company_id', 'entry_id'], 'supplier_credit_entry_context_unique');
        });
    }

    public function down()
    {
        Schema::table('supplier_credits', function (Blueprint $table) {
            $table->dropUnique('supplier_credit_entry_context_unique');
        });
    }
}
