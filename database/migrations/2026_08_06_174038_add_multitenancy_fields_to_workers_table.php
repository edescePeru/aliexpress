<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultitenancyFieldsToWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('tenant_id');

            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('company_id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');

            $table->index('tenant_id');
            $table->index('company_id');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);

            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['branch_id']);

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
            ]);
        });
    }
}
