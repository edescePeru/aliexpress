<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureTenantContextInContainersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable(false)
                ->change();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(
                [
                    'level_id',
                    'name',
                ],
                'containers_level_name_unique'
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
        Schema::table('containers', function (Blueprint $table) {
            $table->dropUnique(
                'containers_level_name_unique'
            );

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->change();
        });
    }
}
