<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareGenerosCatalogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('generos', function (Blueprint $table) {
            $table->string('name', 191)
                ->nullable()
                ->after('id');

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
        Schema::table('generos', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'deleted_at',
            ]);
        });
    }
}
