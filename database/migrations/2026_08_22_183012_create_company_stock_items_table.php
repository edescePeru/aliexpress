<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyStockItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_stock_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('company_id');

            $table->unsignedBigInteger('stock_item_id');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();


            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('restrict');


            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onUpdate('cascade')
                ->onDelete('cascade');


            $table->foreign('stock_item_id')
                ->references('id')
                ->on('stock_items')
                ->onUpdate('cascade')
                ->onDelete('cascade');


            $table->unique(
                [
                    'company_id',
                    'stock_item_id',
                ],
                'company_stock_items_company_stock_unique'
            );


            $table->index(
                'tenant_id',
                'company_stock_items_tenant_id_index'
            );


            $table->index(
                [
                    'company_id',
                    'is_active',
                ],
                'company_stock_items_company_active_index'
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
        Schema::dropIfExists('company_stock_items');
    }
}
