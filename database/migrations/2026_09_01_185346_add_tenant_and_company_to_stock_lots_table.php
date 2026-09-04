<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTenantAndCompanyToStockLotsTable extends Migration
{
    public function up()
    {
        /*
         * 1. Agregar columnas nullable.
         */
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');
        });


        /*
         * 2. Backfill tenant_id desde StockItem.
         */
        DB::statement("
            UPDATE stock_lots sl
            INNER JOIN stock_items si
                ON si.id = sl.stock_item_id
            SET sl.tenant_id = si.tenant_id
        ");


        /*
         * 3. Backfill company_id desde Warehouse.
         */
        DB::statement("
            UPDATE stock_lots sl
            INNER JOIN warehouses w
                ON w.id = sl.warehouse_id
            SET sl.company_id = w.company_id
        ");


        /*
         * 4. Validación defensiva.
         */
        $invalidRows = DB::table('stock_lots')
            ->whereNull('tenant_id')
            ->orWhereNull('company_id')
            ->count();

        if ($invalidRows > 0) {
            throw new \RuntimeException(
                'Existen StockLots sin tenant_id o company_id después del backfill.'
            );
        }


        /*
         * 5. Convertir a NOT NULL.
         *
         * Laravel 7 + MySQL:
         * usamos change().
         */
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('company_id')
                ->nullable(false)
                ->change();
        });


        /*
         * 6. FKs e índices.
         */
        Schema::table('stock_lots', function (Blueprint $table) {

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->index([
                'tenant_id',
                'company_id',
            ], 'stock_lots_tenant_company_idx');

            $table->index([
                'company_id',
                'stock_item_id',
            ], 'stock_lots_company_stock_item_idx');

            $table->index([
                'company_id',
                'warehouse_id',
                'location_id',
            ], 'stock_lots_company_location_idx');
        });
    }


    public function down()
    {
        Schema::table('stock_lots', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id',
            ]);

            $table->dropForeign([
                'company_id',
            ]);

            $table->dropIndex(
                'stock_lots_tenant_company_idx'
            );

            $table->dropIndex(
                'stock_lots_company_stock_item_idx'
            );

            $table->dropIndex(
                'stock_lots_company_location_idx'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
