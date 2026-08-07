<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleTemplatePermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_template_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('role_template_id');
            $table->unsignedBigInteger('permission_id');

            $table->foreign('role_template_id')
                ->references('id')
                ->on('role_templates')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->primary([
                'role_template_id',
                'permission_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_template_permission');
    }
}
