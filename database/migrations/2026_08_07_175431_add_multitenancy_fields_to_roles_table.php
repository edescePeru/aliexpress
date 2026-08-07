<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultitenancyFieldsToRolesTable extends Migration
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
            * Nullable durante la migración.
            *
            * Los roles actuales todavía son globales
            * y debemos asignarlos después al tenant.
            */
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            /*
             * Plantilla de la cual nació el rol.
             *
             * Puede ser null para roles personalizados
             * creados directamente para un tenant.
             */
            $table->unsignedBigInteger('role_template_id')
                ->nullable()
                ->after('tenant_id');

            $table->boolean('is_owner_assignable')
                ->default(true)
                ->after('guard_name');

            $table->boolean('is_customized')
                ->default(false)
                ->after('is_owner_assignable');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_customized');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('role_template_id')
                ->references('id')
                ->on('role_templates')
                ->onDelete('set null');

            $table->index('tenant_id');
            $table->index('role_template_id');
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
            $table->dropForeign([
                'tenant_id',
            ]);

            $table->dropForeign([
                'role_template_id',
            ]);

            $table->dropIndex([
                'tenant_id',
            ]);

            $table->dropIndex([
                'role_template_id',
            ]);

            $table->dropColumn([
                'tenant_id',
                'role_template_id',
                'is_owner_assignable',
                'is_customized',
                'is_active',
            ]);
        });
    }
}
