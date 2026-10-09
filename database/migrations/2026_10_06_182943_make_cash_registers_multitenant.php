<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCashRegistersMultitenant extends Migration
{
    public function up()
    {
        Schema::table('cash_registers', function (Blueprint $table) {

            /*
             * =========================================================
             * CONTEXTO MULTITENANT
             * =========================================================
             *
             * Nullable temporalmente porque ya existen registros legacy.
             */

            $table
                ->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table
                ->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table
                ->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('company_id');

            /*
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                'tenant_id',
                'cash_registers_tenant_id_index'
            );

            $table->index(
                'company_id',
                'cash_registers_company_id_index'
            );

            $table->index(
                'branch_id',
                'cash_registers_branch_id_index'
            );

            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'branch_id',
                    'status',
                ],
                'cash_registers_context_status_index'
            );

            $table->index(
                [
                    'user_id',
                    'cash_box_id',
                    'status',
                ],
                'cash_registers_user_box_status_index'
            );

            /*
             * =========================================================
             * FOREIGN KEYS
             * =========================================================
             */

            $table
                ->foreign(
                    'tenant_id',
                    'cash_registers_tenant_id_foreign'
                )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'company_id',
                    'cash_registers_company_id_foreign'
                )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'branch_id',
                    'cash_registers_branch_id_foreign'
                )
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('cash_registers', function (Blueprint $table) {

            $table->dropForeign(
                'cash_registers_branch_id_foreign'
            );

            $table->dropForeign(
                'cash_registers_company_id_foreign'
            );

            $table->dropForeign(
                'cash_registers_tenant_id_foreign'
            );

            $table->dropIndex(
                'cash_registers_user_box_status_index'
            );

            $table->dropIndex(
                'cash_registers_context_status_index'
            );

            $table->dropIndex(
                'cash_registers_branch_id_index'
            );

            $table->dropIndex(
                'cash_registers_company_id_index'
            );

            $table->dropIndex(
                'cash_registers_tenant_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
            ]);
        });
    }
}
