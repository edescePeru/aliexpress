<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxSnapshotToEquipmentConsumablesTable extends Migration
{
    public function up()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {

            $table->unsignedBigInteger('type_tax_id')
                ->nullable()
                ->after('material_id');

            $table->decimal('tax_rate', 8, 4)
                ->nullable()
                ->after('type_tax_id');

            $table->foreign('type_tax_id')
                ->references('id')
                ->on('type_taxes');

            $table->index('type_tax_id');
        });
    }

    public function down()
    {
        Schema::table('equipment_consumables', function (Blueprint $table) {

            $table->dropForeign([
                'type_tax_id',
            ]);

            $table->dropIndex([
                'type_tax_id',
            ]);

            $table->dropColumn([
                'type_tax_id',
                'tax_rate',
            ]);
        });
    }
}
