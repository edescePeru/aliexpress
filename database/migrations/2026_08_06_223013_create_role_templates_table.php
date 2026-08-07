<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code', 100)->unique();

            $table->string('name', 150);

            $table->string('description', 250)
                ->nullable();

            /*
             * Define si el Owner de un tenant puede
             * asignar este tipo de perfil a sus usuarios.
             *
             * Ejemplo:
             * Cajero       = true
             * Repartidor   = true
             * Owner        = false
             */
            $table->boolean('is_owner_assignable')
                ->default(true);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_templates');
    }
}
