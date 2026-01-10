<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPresentationFieldsToEquipmentConsumablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {
            $table->foreignId('material_presentation_id')
                ->nullable()
                ->after('material_id')
                ->constrained('material_presentations')
                ->nullOnDelete();

            // packs vendidos (cantidad de presentaciones)
            $table->integer('packs')
                ->nullable()
                ->after('quantity');

            // unidades por pack (ej: 3, 6, 12, 25, 50...)
            $table->integer('units_per_pack')
                ->nullable()
                ->after('packs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {
            $table->dropForeign(['material_presentation_id']);
            $table->dropColumn(['material_presentation_id', 'packs', 'units_per_pack']);
        });
    }
}
