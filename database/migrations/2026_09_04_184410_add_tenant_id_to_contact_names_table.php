<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantIdToContactNamesTable extends Migration
{
    public function up()
    {
        /*
         * 1. Agregar tenant_id nullable.
         */
        Schema::table('contact_names', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');
        });


        /*
         * 2. Backfill desde Customer.
         *
         * Customer ya tiene tenant_id.
         */
        DB::statement("
            UPDATE contact_names cn
            INNER JOIN customers c
                ON c.id = cn.customer_id
            SET cn.tenant_id = c.tenant_id
        ");


        /*
         * 3. Validar que todos hayan podido obtener Tenant.
         */
        $invalidRows = DB::table('contact_names')
            ->whereNull('tenant_id')
            ->count();

        if ($invalidRows > 0) {
            throw new \RuntimeException(
                'Existen contactos sin tenant_id después del backfill.'
            );
        }


        /*
         * 4. Retirar unique global del código.
         */
        Schema::table('contact_names', function (Blueprint $table) {
            $table->dropUnique(
                'contact_names_code_unique'
            );
        });


        /*
         * 5. tenant_id NOT NULL.
         */
        Schema::table('contact_names', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();
        });


        /*
         * 6. FK + índices.
         */
        Schema::table('contact_names', function (Blueprint $table) {

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->unique(
                [
                    'tenant_id',
                    'code'
                ],
                'contact_names_tenant_code_unique'
            );

            $table->index(
                [
                    'tenant_id',
                    'customer_id'
                ],
                'contact_names_tenant_customer_idx'
            );

            $table->index(
                [
                    'tenant_id',
                    'deleted_at'
                ],
                'contact_names_tenant_deleted_idx'
            );
        });
    }


    public function down()
    {
        Schema::table('contact_names', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropUnique(
                'contact_names_tenant_code_unique'
            );

            $table->dropIndex(
                'contact_names_tenant_customer_idx'
            );

            $table->dropIndex(
                'contact_names_tenant_deleted_idx'
            );
        });


        Schema::table('contact_names', function (Blueprint $table) {

            /*
             * Solo funcionará si no existen códigos repetidos
             * entre diferentes Tenants al hacer rollback.
             */
            $table->unique(
                'code',
                'contact_names_code_unique'
            );
        });


        Schema::table('contact_names', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
}
