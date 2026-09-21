<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantCompanyContextToEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->index(
                'tenant_id',
                'entries_tenant_id_index'
            );

            $table->index(
                'company_id',
                'entries_company_id_index'
            );
        });

        /*
         * 1. Derivar Company/Tenant desde StockLot cuando exista.
         */
        DB::statement("
            UPDATE entries e
            INNER JOIN (
                SELECT
                    de.entry_id,
                    MIN(sl.tenant_id) AS tenant_id,
                    MIN(sl.company_id) AS company_id
                FROM detail_entries de
                INNER JOIN stock_lots sl
                    ON sl.detail_entry_id = de.id
                WHERE
                    sl.tenant_id IS NOT NULL
                    AND sl.company_id IS NOT NULL
                GROUP BY de.entry_id
            ) x
                ON x.entry_id = e.id
            SET
                e.tenant_id = x.tenant_id,
                e.company_id = x.company_id
        ");

        /*
         * 2. Legacy sin StockLot:
         * negocio original = Company 1.
         *
         * Si tu Company histórica no es ID 1,
         * cambia esta consulta antes de ejecutar.
         */
        $company = DB::table('companies')
            ->where('id', 1)
            ->first();

        if (!$company) {
            throw new RuntimeException(
                'No existe la Company histórica para completar entries.'
            );
        }

        DB::table('entries')
            ->whereNull('company_id')
            ->update([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
            ]);

        /*
         * 3. Seguridad.
         */
        $invalid = DB::table('entries')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($invalid > 0) {
            throw new RuntimeException(
                'Existen Entries sin contexto Tenant/Company.'
            );
        }

        Schema::table('entries', function (Blueprint $table) {
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
        Schema::table('entries', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['company_id']);

            $table->dropIndex(
                'entries_tenant_id_index'
            );

            $table->dropIndex(
                'entries_company_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
