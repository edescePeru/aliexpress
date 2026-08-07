<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRolesUniqueIndexForMultitenancy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            /*
             * Usa el nombre real que verificaste
             * anteriormente con SHOW INDEX.
             */
            $table->dropUnique(
                'roles_name_guard_name_unique'
            );

            $table->unique(
                [
                    'tenant_id',
                    'name',
                    'guard_name',
                ],
                'roles_tenant_name_guard_unique'
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
        Schema::table('roles', function (Blueprint $table) {

            $table->dropUnique(
                'roles_tenant_name_guard_unique'
            );

            $table->unique(
                [
                    'name',
                    'guard_name',
                ],
                'roles_name_guard_name_unique'
            );
        });
    }
}
