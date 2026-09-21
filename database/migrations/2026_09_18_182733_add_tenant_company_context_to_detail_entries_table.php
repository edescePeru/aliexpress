<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantCompanyContextToDetailEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->index(
                'tenant_id',
                'detail_entries_tenant_id_index'
            );

            $table->index(
                'company_id',
                'detail_entries_company_id_index'
            );
        });

        /*
         * Backfill desde Entry.
         */
        DB::statement("
            UPDATE detail_entries de
            INNER JOIN entries e
                ON e.id = de.entry_id
            SET
                de.tenant_id = e.tenant_id,
                de.company_id = e.company_id
        ");

        /*
         * Validar que no haya detalles huérfanos.
         */
        $invalid = DB::table('detail_entries')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($invalid > 0) {
            throw new \RuntimeException(
                'Existen DetailEntry sin contexto Tenant/Company.'
            );
        }

        Schema::table('detail_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('company_id')
                ->nullable(false)
                ->change();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }

    public function down()
    {
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['company_id']);

            $table->dropIndex(
                'detail_entries_tenant_id_index'
            );

            $table->dropIndex(
                'detail_entries_company_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
