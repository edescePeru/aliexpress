<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantCompanyContextToQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->index(
                'tenant_id',
                'quotes_tenant_id_index'
            );

            $table->index(
                'company_id',
                'quotes_company_id_index'
            );
        });

        /*
         * Backfill histórico.
         *
         * Las cotizaciones existentes pertenecen
         * a EDESCE CONSULTORA E.I.R.L.
         *
         * tenant_id  = 1
         * company_id = 1
         */
        DB::table('quotes')->update([
            'tenant_id' => 1,
            'company_id' => 1,
        ]);

        /*
         * Validación.
         */
        $invalid = DB::table('quotes')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($invalid > 0) {
            throw new \RuntimeException(
                'Existen cotizaciones sin Tenant o Company.'
            );
        }

        /*
         * Hacer obligatorios los campos.
         */
        DB::statement("
            ALTER TABLE quotes
            MODIFY tenant_id BIGINT UNSIGNED NOT NULL
        ");

        DB::statement("
            ALTER TABLE quotes
            MODIFY company_id BIGINT UNSIGNED NOT NULL
        ");

        Schema::table('quotes', function (Blueprint $table) {
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
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['company_id']);

            $table->dropIndex(
                'quotes_tenant_id_index'
            );

            $table->dropIndex(
                'quotes_company_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
