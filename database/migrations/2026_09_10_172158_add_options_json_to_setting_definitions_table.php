<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionsJsonToSettingDefinitionsTable extends Migration
{
    public function up()
    {
        Schema::table('setting_definitions', function (Blueprint $table) {
            $table->json('options_json')
                ->nullable()
                ->after('default_value');
        });
    }

    public function down()
    {
        Schema::table('setting_definitions', function (Blueprint $table) {
            $table->dropColumn('options_json');
        });
    }
}
