<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantIdToCustomersTable extends Migration
{
    public function up()
    {
        /*
         * 1. Agregar tenant_id temporalmente nullable.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');
        });


        /*
         * 2. Backfill histórico.
         *
         * Los Customers actuales pertenecen al Tenant original:
         * Grupo Empresarial EDESCE.
         */
        DB::table('customers')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => 1,
            ]);


        /*
         * 3. Validación defensiva.
         */
        $invalidRows = DB::table('customers')
            ->whereNull('tenant_id')
            ->count();

        if ($invalidRows > 0) {
            throw new \RuntimeException(
                'Existen customers sin tenant_id después del backfill.'
            );
        }


        /*
         * 4. Retirar uniques globales.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_ruc_unique');
            $table->dropUnique('customers_code_unique');
        });


        /*
         * 5. tenant_id pasa a NOT NULL.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();
        });


        /*
         * 6. FK + uniques por Tenant.
         */
        Schema::table('customers', function (Blueprint $table) {

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->unique(
                ['tenant_id', 'RUC'],
                'customers_tenant_ruc_unique'
            );

            $table->unique(
                ['tenant_id', 'code'],
                'customers_tenant_code_unique'
            );

            $table->index(
                ['tenant_id', 'deleted_at'],
                'customers_tenant_deleted_idx'
            );
        });
    }


    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropUnique(
                'customers_tenant_ruc_unique'
            );

            $table->dropUnique(
                'customers_tenant_code_unique'
            );

            $table->dropIndex(
                'customers_tenant_deleted_idx'
            );
        });


        /*
         * Atención:
         * este rollback solamente podrá restaurar los uniques
         * globales si no existen documentos/códigos repetidos
         * entre diferentes Tenants.
         */
        Schema::table('customers', function (Blueprint $table) {

            $table->unique(
                'RUC',
                'customers_ruc_unique'
            );

            $table->unique(
                'code',
                'customers_code_unique'
            );
        });


        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
}
