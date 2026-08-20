<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTallaIdToVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->unsignedBigInteger('talla_id')
                ->nullable()
                ->after('material_id');

            $table->index(
                'talla_id',
                'variants_talla_id_index'
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
        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex(
                'variants_talla_id_index'
            );

            $table->dropColumn(
                'talla_id'
            );
        });
    }
}
