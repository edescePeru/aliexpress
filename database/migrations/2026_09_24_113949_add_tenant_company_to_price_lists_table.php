<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantCompanyToPriceListsTable extends Migration
{
    public function up()
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->index('tenant_id');
            $table->index('company_id');
        });

        /*
         * Históricamente las listas actuales pertenecían
         * a Tenant 1 / Company 1.
         */
        DB::table('price_lists')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => 1,
                'company_id' => 1,
            ]);

        $pending = DB::table('price_lists')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($pending > 0) {
            throw new \RuntimeException(
                'Existen PriceLists sin tenant/company luego del backfill.'
            );
        }

        DB::statement(
            'ALTER TABLE price_lists
             MODIFY tenant_id BIGINT UNSIGNED NOT NULL'
        );

        DB::statement(
            'ALTER TABLE price_lists
             MODIFY company_id BIGINT UNSIGNED NOT NULL'
        );

        Schema::table('price_lists', function (Blueprint $table) {
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
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropForeign([
                'company_id'
            ]);

            $table->dropIndex([
                'tenant_id'
            ]);

            $table->dropIndex([
                'company_id'
            ]);

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
