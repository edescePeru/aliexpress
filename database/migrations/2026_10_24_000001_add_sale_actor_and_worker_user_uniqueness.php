<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddSaleActorAndWorkerUserUniqueness extends Migration
{
    public function up()
    {
        $duplicates = DB::table('workers')->whereNotNull('user_id')->whereNotNull('tenant_id')->whereNotNull('company_id')
            ->select('tenant_id', 'company_id', 'user_id')->groupBy('tenant_id', 'company_id', 'user_id')->havingRaw('COUNT(*) > 1')->first();
        if ($duplicates) { throw new RuntimeException('Vínculo duplicado en workers. Revise tenant '.$duplicates->tenant_id.', empresa '.$duplicates->company_id.', usuario '.$duplicates->user_id.'. No se eliminaron ni reasignaron trabajadores.'); }
        if (!Schema::hasColumn('sales', 'created_by')) {
            Schema::table('sales', function (Blueprint $t) {
                $t->unsignedBigInteger('created_by')->nullable();
                $t->foreign('created_by', 'sales_created_by_user_fk')->references('id')->on('users')->onDelete('restrict');
            });
        }
        if (!Schema::hasColumn('sales', 'created_by_name')) { Schema::table('sales', function (Blueprint $t) { $t->string('created_by_name', 255)->nullable(); }); }
        // Permit retry after partially applied MySQL DDL, but only this named index.
        $exists = DB::getDriverName() === 'sqlite'
            ? collect(DB::select("PRAGMA index_list('workers')"))->contains('name', 'workers_tenant_company_user_unique')
            : DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'workers')->where('index_name', 'workers_tenant_company_user_unique')->exists();
        if (!$exists) { Schema::table('workers', function (Blueprint $t) { $t->unique(['tenant_id', 'company_id', 'user_id'], 'workers_tenant_company_user_unique'); }); }
        // Historical created_by remains NULL: current Worker.user_id is not evidence of authorship.
    }
    public function down()
    {
        if (Schema::hasColumn('sales', 'created_by') && DB::table('sales')->whereNotNull('created_by')->exists()) { throw new RuntimeException('Hay ventas con autor registrado. No se elimina su trazabilidad.'); }
        Schema::table('workers', function (Blueprint $t) { $t->dropUnique('workers_tenant_company_user_unique'); });
        if (Schema::hasColumn('sales', 'created_by')) { Schema::table('sales', function (Blueprint $t) { $t->dropForeign('sales_created_by_user_fk'); $t->dropColumn('created_by'); }); }
        if (Schema::hasColumn('sales', 'created_by_name')) { Schema::table('sales', function (Blueprint $t) { $t->dropColumn('created_by_name'); }); }
    }
}
