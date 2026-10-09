<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashSettlementsTable extends Migration
{
    public function up()
    {
        Schema::create('cash_settlements', function (Blueprint $table) {

            $table->bigIncrements('id');

            /*
             * =========================================================
             * CONTEXTO
             * =========================================================
             *
             * CashSettlement es una entidad nueva.
             *
             * No tenemos datos legacy que migrar aquí,
             * por eso estos campos sí pueden ser NOT NULL
             * desde el inicio.
             */

            $table
                ->unsignedBigInteger('tenant_id');

            $table
                ->unsignedBigInteger('company_id');

            $table
                ->unsignedBigInteger('branch_id');

            $table
                ->unsignedBigInteger('cash_box_id');

            /*
             * Una sesión solamente puede tener un arqueo definitivo.
             */
            $table
                ->unsignedBigInteger('cash_register_id')
                ->unique();

            /*
             * Usuario que realizó el arqueo.
             *
             * Funcionalmente será obligatorio al crear el registro,
             * pero nullable permite conservar histórico si algún usuario
             * fuese eliminado posteriormente.
             */
            $table
                ->unsignedBigInteger('user_id')
                ->nullable();

            /*
             * =========================================================
             * TOTALES DEL ARQUEO
             * =========================================================
             */

            /*
             * Total económico contenido en la sesión
             * al momento del arqueo.
             *
             * Efectivo:
             * saldo teórico.
             *
             * Bancario:
             * confirmado + diferido.
             */
            $table
                ->decimal(
                    'expected_amount',
                    14,
                    2
                )
                ->default(0);

            /*
             * Monto que se reconoce inmediatamente
             * en CashBox al realizar el arqueo.
             *
             * Efectivo:
             * lo efectivamente contado/rendido.
             *
             * Bancario:
             * movimientos ya confirmados.
             */
            $table
                ->decimal(
                    'confirmed_amount',
                    14,
                    2
                )
                ->default(0);

            /*
             * Monto que todavía no llegó realmente
             * a CashBox.
             *
             * Ejemplo:
             * POS pendiente de abono.
             */
            $table
                ->decimal(
                    'deferred_amount',
                    14,
                    2
                )
                ->default(0);

            /*
             * Aplica principalmente al efectivo.
             *
             * NULL para cajas donde no exista conteo físico.
             */
            $table
                ->decimal(
                    'counted_amount',
                    14,
                    2
                )
                ->nullable();

            /*
             * contado - esperado
             *
             * Puede ser negativo.
             */
            $table
                ->decimal(
                    'difference_amount',
                    14,
                    2
                )
                ->default(0);

            $table
                ->text('observation')
                ->nullable();

            $table
                ->dateTime('settled_at');

            $table->timestamps();

            /*
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'branch_id',
                ],
                'cash_settlements_context_index'
            );

            $table->index(
                [
                    'cash_box_id',
                    'settled_at',
                ],
                'cash_settlements_box_date_index'
            );

            $table->index(
                [
                    'user_id',
                    'settled_at',
                ],
                'cash_settlements_user_date_index'
            );

            /*
             * =========================================================
             * FOREIGN KEYS
             * =========================================================
             */

            $table
                ->foreign(
                    'tenant_id',
                    'cash_settlements_tenant_id_foreign'
                )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'company_id',
                    'cash_settlements_company_id_foreign'
                )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table
                ->foreign(
                    'branch_id',
                    'cash_settlements_branch_id_foreign'
                )
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');

            $table
                ->foreign(
                    'cash_box_id',
                    'cash_settlements_cash_box_id_foreign'
                )
                ->references('id')
                ->on('cash_boxes')
                ->onDelete('restrict');

            $table
                ->foreign(
                    'cash_register_id',
                    'cash_settlements_cash_register_id_foreign'
                )
                ->references('id')
                ->on('cash_registers')
                ->onDelete('restrict');

            $table
                ->foreign(
                    'user_id',
                    'cash_settlements_user_id_foreign'
                )
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });


        /*
         * =============================================================
         * RELACIONAR MOVIMIENTOS CON EL ARQUEO
         * =============================================================
         *
         * Un CashMovement NO se duplica al arquear.
         *
         * Simplemente registramos:
         *
         * cash_settlement_id = arqueo que lo incluyó.
         */

        Schema::table('cash_movements', function (Blueprint $table) {

            $table
                ->unsignedBigInteger('cash_settlement_id')
                ->nullable()
                ->after('cash_register_id');

            $table->index(
                'cash_settlement_id',
                'cash_movements_cash_settlement_id_index'
            );

            $table
                ->foreign(
                    'cash_settlement_id',
                    'cash_movements_cash_settlement_id_foreign'
                )
                ->references('id')
                ->on('cash_settlements')
                ->onDelete('set null');
        });
    }


    public function down()
    {
        Schema::table('cash_movements', function (Blueprint $table) {

            $table->dropForeign(
                'cash_movements_cash_settlement_id_foreign'
            );

            $table->dropIndex(
                'cash_movements_cash_settlement_id_index'
            );

            $table->dropColumn(
                'cash_settlement_id'
            );
        });


        Schema::dropIfExists(
            'cash_settlements'
        );
    }
}
