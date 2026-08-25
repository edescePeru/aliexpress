<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureCompanyContextInLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
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
                    'area_id',
                    'warehouse_id',
                    'shelf_id',
                    'level_id',
                    'container_id',
                    'position_id',
                ],
                'locations_full_path_unique'
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
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(
                'locations_full_path_unique'
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
