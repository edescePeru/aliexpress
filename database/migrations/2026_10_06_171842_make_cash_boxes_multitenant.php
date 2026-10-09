<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCashBoxesMultitenant extends Migration
{
    public function up()
    {
        Schema::table('cash_boxes', function (Blueprint $table) {

            /*
             * =========================================================
             * MULTITENANCY
             * =========================================================
             *
             * Nullable temporalmente porque existen CashBoxes legacy.
             *
             * Después del backfill podremos evaluar convertir:
             *
             * tenant_id
             * company_id
             *
             * a NOT NULL.
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
             * Una CashBox puede:
             *
             * - pertenecer a una Branch concreta
             * - ser general para toda la Company
             *
             * Ejemplos:
             *
             * Efectivo Tienda 1  -> branch_id = 1
             * BCP                -> branch_id = null
             */
            $table
                ->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('company_id');

            /*
             * =========================================================
             * SALDO MAESTRO
             * =========================================================
             *
             * Reemplazará progresivamente el current_balance
             * del CashRegister central del antiguo owner.
             *
             * IMPORTANTE:
             * todavía NO cambiaremos la lógica que lo utiliza.
             */
            $table
                ->decimal(
                    'current_balance',
                    14,
                    2
                )
                ->default(0)
                ->after('currency');

            /*
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                'tenant_id',
                'cash_boxes_tenant_id_index'
            );

            $table->index(
                'company_id',
                'cash_boxes_company_id_index'
            );

            $table->index(
                'branch_id',
                'cash_boxes_branch_id_index'
            );

            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'is_active',
                ],
                'cash_boxes_context_active_index'
            );

            /*
             * =========================================================
             * FOREIGN KEYS
             * =========================================================
             */

            $table
                ->foreign(
                    'tenant_id',
                    'cash_boxes_tenant_id_foreign'
                )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'company_id',
                    'cash_boxes_company_id_foreign'
                )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'branch_id',
                    'cash_boxes_branch_id_foreign'
                )
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });
    }


    public function down()
    {
        Schema::table('cash_boxes', function (Blueprint $table) {

            $table->dropForeign(
                'cash_boxes_branch_id_foreign'
            );

            $table->dropForeign(
                'cash_boxes_company_id_foreign'
            );

            $table->dropForeign(
                'cash_boxes_tenant_id_foreign'
            );

            $table->dropIndex(
                'cash_boxes_context_active_index'
            );

            $table->dropIndex(
                'cash_boxes_branch_id_index'
            );

            $table->dropIndex(
                'cash_boxes_company_id_index'
            );

            $table->dropIndex(
                'cash_boxes_tenant_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
                'current_balance',
            ]);
        });
    }
}
