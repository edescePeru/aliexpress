<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureCompanyContextInWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
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


            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onUpdate('cascade')
                ->onDelete('set null');


            $table->unique(
                [
                    'company_id',
                    'area_id',
                    'name',
                ],
                'warehouses_company_area_name_unique'
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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique(
                'warehouses_company_area_name_unique'
            );

            $table->dropForeign([
                'branch_id'
            ]);

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
