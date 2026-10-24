<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddBranchContextToQuotes extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('quotes', 'branch_id')) {
            throw new RuntimeException('quotes.branch_id ya existe. Revisar su definición antes de aplicar esta migración.');
        }
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->index(['tenant_id', 'company_id', 'branch_id'], 'quotes_company_branch_idx');
            $table->foreign('branch_id', 'quotes_branch_context_fk')->references('id')->on('branches')->onDelete('restrict');
        });
        // No inferir el local histórico desde el local de sesión ni desde los lotes.
        // La asignación histórica se realizará en un paso separado, con regla aprobada.
    }

    public function down()
    {
        if (DB::table('quotes')->whereNotNull('branch_id')->exists()) {
            throw new RuntimeException('No se puede retirar la sucursal de cotizaciones que ya tienen contexto.');
        }
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign('quotes_branch_context_fk');
            $table->dropIndex('quotes_company_branch_idx');
            $table->dropColumn('branch_id');
        });
    }
}
