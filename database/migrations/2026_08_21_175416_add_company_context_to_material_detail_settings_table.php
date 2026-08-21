<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyContextToMaterialDetailSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('material_detail_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->index(
                'tenant_id',
                'material_detail_settings_tenant_id_index'
            );

            $table->index(
                'company_id',
                'material_detail_settings_company_id_index'
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
        Schema::table('material_detail_settings', function (Blueprint $table) {
            $table->dropIndex(
                'material_detail_settings_tenant_id_index'
            );

            $table->dropIndex(
                'material_detail_settings_company_id_index'
            );

            $table->dropColumn([
                'tenant_id',
                'company_id',
            ]);
        });
    }
}
