<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureCompanyContextInMaterialDetailSettingsTable extends Migration
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
                ->onDelete('cascade');

            $table->unique(
                'company_id',
                'material_detail_settings_company_unique'
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
            $table->dropUnique(
                'material_detail_settings_company_unique'
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
