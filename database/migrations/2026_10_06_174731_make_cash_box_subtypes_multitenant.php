<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCashBoxSubtypesMultitenant extends Migration
{
    public function up()
    {
        Schema::table('cash_box_subtypes', function (Blueprint $table) {

            /*
             * =========================================================
             * MULTITENANCY
             * =========================================================
             *
             * Nullable temporalmente porque existen registros legacy.
             */

            $table
                ->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table
                ->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            /*
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                'tenant_id',
                'cash_box_subtypes_tenant_id_index'
            );

            $table->index(
                'company_id',
                'cash_box_subtypes_company_id_index'
            );

            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'is_active',
                ],
                'cash_box_subtypes_context_active_index'
            );

            /*
             * =========================================================
             * FOREIGN KEYS
             * =========================================================
             */

            $table
                ->foreign(
                    'tenant_id',
                    'cash_box_subtypes_tenant_id_foreign'
                )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'company_id',
                    'cash_box_subtypes_company_id_foreign'
                )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('cash_box_subtypes', function (Blueprint $table) {

            $table->dropForeign(
                'cash_box_subtypes_company_id_foreign'
            );

            $table->dropForeign(
                'cash_box_subtypes_tenant_id_foreign'
            );

            $table->dropIndex(
                'cash_box_subtypes_context_active_index'
            );

            $table->dropIndex(
                'cash_box_subtypes_company_id_index'
            );

            $table->dropIndex(
                'cash_box_subtypes_tenant_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
