<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingDefinitionsTable extends Migration
{
    public function up()
    {
        Schema::create('setting_definitions', function (Blueprint $table) {

            $table->id();

            $table->string('key', 191)
                ->unique();

            $table->string('label', 191);

            $table->string('module', 100)
                ->nullable();

            $table->string('scope', 30);

            $table->string('value_type', 30);

            $table->text('default_value')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->boolean('editable_by_owner')
                ->default(true);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(
            'setting_definitions'
        );
    }
}
