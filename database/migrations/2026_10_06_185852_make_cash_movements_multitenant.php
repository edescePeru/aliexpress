<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCashMovementsMultitenant extends Migration
{
    public function up()
    {
        Schema::table('cash_movements', function (Blueprint $table) {

            /*
             * =========================================================
             * CONTEXTO MULTITENANT
             * =========================================================
             *
             * Nullable temporalmente porque ya existen movimientos
             * históricos.
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
             * CashBox directa.
             *
             * Antes el movimiento llegaba a CashBox de forma indirecta
             * por medio de CashRegister.
             *
             * Ahora necesitamos la relación directa porque:
             *
             * - las regularizaciones futuras pueden no tener sesión
             * - CashRegister puede estar cerrado
             */
            $table
                ->unsignedBigInteger('cash_box_id')
                ->nullable()
                ->after('branch_id');

            /*
             * Usuario que originó o registró el movimiento.
             *
             * En movimientos operativos:
             * usuario dueño de la sesión.
             *
             * En regularizaciones:
             * usuario que realizó la regularización.
             */
            $table
                ->unsignedBigInteger('user_id')
                ->nullable()
                ->after('cash_register_id');

            /*
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                'tenant_id',
                'cash_movements_tenant_id_index'
            );

            $table->index(
                'company_id',
                'cash_movements_company_id_index'
            );

            $table->index(
                'branch_id',
                'cash_movements_branch_id_index'
            );

            $table->index(
                'cash_box_id',
                'cash_movements_cash_box_id_index'
            );

            $table->index(
                'user_id',
                'cash_movements_user_id_index'
            );

            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'branch_id',
                ],
                'cash_movements_context_index'
            );

            $table->index(
                [
                    'cash_box_id',
                    'cash_register_id',
                    'type',
                ],
                'cash_movements_box_register_type_index'
            );

            $table->index(
                [
                    'cash_box_id',
                    'regularize',
                ],
                'cash_movements_box_regularize_index'
            );

            /*
             * =========================================================
             * FOREIGN KEYS
             * =========================================================
             */

            $table
                ->foreign(
                    'tenant_id',
                    'cash_movements_tenant_id_foreign'
                )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'company_id',
                    'cash_movements_company_id_foreign'
                )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'branch_id',
                    'cash_movements_branch_id_foreign'
                )
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');

            $table
                ->foreign(
                    'cash_box_id',
                    'cash_movements_cash_box_id_foreign'
                )
                ->references('id')
                ->on('cash_boxes')
                ->onDelete('restrict');

            $table
                ->foreign(
                    'user_id',
                    'cash_movements_user_id_foreign'
                )
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }


    public function down()
    {
        Schema::table('cash_movements', function (Blueprint $table) {

            $table->dropForeign(
                'cash_movements_user_id_foreign'
            );

            $table->dropForeign(
                'cash_movements_cash_box_id_foreign'
            );

            $table->dropForeign(
                'cash_movements_branch_id_foreign'
            );

            $table->dropForeign(
                'cash_movements_company_id_foreign'
            );

            $table->dropForeign(
                'cash_movements_tenant_id_foreign'
            );

            $table->dropIndex(
                'cash_movements_box_regularize_index'
            );

            $table->dropIndex(
                'cash_movements_box_register_type_index'
            );

            $table->dropIndex(
                'cash_movements_context_index'
            );

            $table->dropIndex(
                'cash_movements_user_id_index'
            );

            $table->dropIndex(
                'cash_movements_cash_box_id_index'
            );

            $table->dropIndex(
                'cash_movements_branch_id_index'
            );

            $table->dropIndex(
                'cash_movements_company_id_index'
            );

            $table->dropIndex(
                'cash_movements_tenant_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
                'cash_box_id',
                'user_id',
            ]);
        });
    }
}
