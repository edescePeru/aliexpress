<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SecureTenantContextInLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('levels', function (Blueprint $table) {
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
                    'shelf_id',
                    'name',
                ],
                'levels_shelf_name_unique'
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
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique(
                'levels_shelf_name_unique'
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
