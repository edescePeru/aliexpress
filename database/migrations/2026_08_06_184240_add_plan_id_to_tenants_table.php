<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanIdToTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')
                ->nullable()
                ->after('id');

            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->onDelete('restrict');

            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropIndex(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
}
