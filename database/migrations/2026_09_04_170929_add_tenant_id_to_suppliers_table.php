<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantIdToSuppliersTable extends Migration
{
    public function up()
    {
        /*
         * 1. Agregar tenant_id nullable.
         */
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');
        });


        /*
         * 2. Backfill histórico.
         *
         * Todos los suppliers actuales pertenecen al Tenant 1.
         */
        DB::table('suppliers')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => 1,
            ]);


        /*
         * 3. Validación defensiva.
         */
        $invalidRows = DB::table('suppliers')
            ->whereNull('tenant_id')
            ->count();

        if ($invalidRows > 0) {
            throw new \RuntimeException(
                'Existen suppliers sin tenant_id después del backfill.'
            );
        }


        /*
         * 4. Quitar uniques globales actuales.
         */
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_ruc_unique');
            $table->dropUnique('suppliers_code_unique');
        });


        /*
         * 5. Convertir tenant_id a NOT NULL.
         */
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();
        });


        /*
         * 6. FK + uniques por Tenant.
         */
        Schema::table('suppliers', function (Blueprint $table) {

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->unique(
                ['tenant_id', 'RUC'],
                'suppliers_tenant_ruc_unique'
            );

            $table->unique(
                ['tenant_id', 'code'],
                'suppliers_tenant_code_unique'
            );

            $table->index(
                ['tenant_id', 'deleted_at'],
                'suppliers_tenant_deleted_idx'
            );
        });
    }


    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropUnique(
                'suppliers_tenant_ruc_unique'
            );

            $table->dropUnique(
                'suppliers_tenant_code_unique'
            );

            $table->dropIndex(
                'suppliers_tenant_deleted_idx'
            );
        });


        /*
         * Restaurar los uniques globales.
         *
         * Esto solo funcionará si no existen RUC/code repetidos
         * entre diferentes tenants al hacer rollback.
         */
        Schema::table('suppliers', function (Blueprint $table) {

            $table->unique(
                'RUC',
                'suppliers_ruc_unique'
            );

            $table->unique(
                'code',
                'suppliers_code_unique'
            );
        });


        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
}
