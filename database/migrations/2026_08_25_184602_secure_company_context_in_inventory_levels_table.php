<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureCompanyContextInInventoryLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_levels', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('company_id')
                ->nullable(false)
                ->change();


            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('restrict');


            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onUpdate('cascade')
                ->onDelete('restrict');


            $table->unique(
                [
                    'stock_item_id',
                    'location_id',
                ],
                'inventory_levels_stock_location_unique'
            );


            $table->index(
                [
                    'company_id',
                    'warehouse_id',
                ],
                'inventory_levels_company_warehouse_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_levels', function (Blueprint $table) {
            $table->dropIndex(
                'inventory_levels_company_warehouse_index'
            );

            $table->dropUnique(
                'inventory_levels_stock_location_unique'
            );

            $table->dropForeign([
                'company_id'
            ]);

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->change();
        });
    }
}
