<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyContextToWarehousesTable extends Migration
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
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('company_id');


            $table->index(
                'tenant_id',
                'warehouses_tenant_id_index'
            );

            $table->index(
                'company_id',
                'warehouses_company_id_index'
            );

            $table->index(
                'branch_id',
                'warehouses_branch_id_index'
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
            $table->dropIndex(
                'warehouses_tenant_id_index'
            );

            $table->dropIndex(
                'warehouses_company_id_index'
            );

            $table->dropIndex(
                'warehouses_branch_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
            ]);
        });
    }
}
