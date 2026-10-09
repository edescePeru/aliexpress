<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantIdToWorkforcesTable extends Migration
{
    public function up()
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table
                ->index('tenant_id');

            $table
                ->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('workforces', function (Blueprint $table) {
            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropIndex([
                'tenant_id'
            ]);

            $table->dropColumn(
                'tenant_id'
            );
        });
    }
}
