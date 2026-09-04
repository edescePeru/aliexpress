<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantAndCompanyToItemsTable extends Migration
{
    public function up()
    {
        /*
         * 1. Agregar columnas nullable
         */
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');
        });


        /*
         * 2. Backfill desde StockLot
         *
         * Todos los Items actuales tienen stock_lot_id,
         * según la validación previa.
         */
        DB::statement("
            UPDATE items i
            INNER JOIN stock_lots sl
                ON sl.id = i.stock_lot_id
            SET
                i.tenant_id = sl.tenant_id,
                i.company_id = sl.company_id
        ");


        /*
         * 3. Validación defensiva
         */
        $invalidRows = DB::table('items')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($invalidRows > 0) {
            throw new \RuntimeException(
                'Existen Items sin tenant_id o company_id después del backfill.'
            );
        }


        /*
         * 4. Convertir a NOT NULL
         */
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('company_id')
                ->nullable(false)
                ->change();
        });


        /*
         * 5. Foreign keys + índices
         */
        Schema::table('items', function (Blueprint $table) {

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->index(
                ['tenant_id', 'company_id'],
                'items_tenant_company_idx'
            );

            $table->index(
                ['company_id', 'stock_item_id'],
                'items_company_stock_item_idx'
            );

            $table->index(
                ['company_id', 'warehouse_id', 'location_id'],
                'items_company_location_idx'
            );

            $table->index(
                ['company_id', 'state_item'],
                'items_company_state_idx'
            );
        });
    }


    public function down()
    {
        Schema::table('items', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropForeign([
                'company_id'
            ]);

            $table->dropIndex(
                'items_tenant_company_idx'
            );

            $table->dropIndex(
                'items_company_stock_item_idx'
            );

            $table->dropIndex(
                'items_company_location_idx'
            );

            $table->dropIndex(
                'items_company_state_idx'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
