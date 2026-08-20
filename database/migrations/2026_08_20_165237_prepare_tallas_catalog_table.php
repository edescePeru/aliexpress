<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareTallasCatalogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->string('name', 191)
                ->nullable()
                ->after('id');

            $table->string('short_name', 191)
                ->nullable()
                ->after('description');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'short_name',
                'deleted_at',
            ]);
        });
    }
}
