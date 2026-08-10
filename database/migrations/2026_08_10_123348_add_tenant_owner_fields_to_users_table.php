<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantOwnerFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_tenant_owner')
                ->default(false)
                ->after('is_platform_admin');

            $table->boolean('must_change_password')
                ->default(false)
                ->after('is_tenant_owner');

            $table->index('is_tenant_owner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex([
                'is_tenant_owner',
            ]);

            $table->dropColumn([
                'is_tenant_owner',
                'must_change_password',
            ]);
        });
    }
}
