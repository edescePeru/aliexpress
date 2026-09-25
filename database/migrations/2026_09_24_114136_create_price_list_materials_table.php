<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceListMaterialsTable extends Migration
{
    public function up()
    {
        Schema::create(
            'price_list_materials',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'price_list_id'
                );

                $table->unsignedBigInteger(
                    'material_id'
                );

                $table->decimal(
                    'price',
                    15,
                    4
                );

                $table->timestamps();

                $table->foreign(
                    'price_list_id'
                )
                    ->references('id')
                    ->on('price_lists')
                    ->onDelete('cascade');

                $table->foreign(
                    'material_id'
                )
                    ->references('id')
                    ->on('materials');

                /*
                 * Un Material solo puede tener un precio base
                 * por PriceList.
                 */
                $table->unique([
                    'price_list_id',
                    'material_id',
                ]);
            }
        );
    }

    public function down()
    {
        Schema::dropIfExists(
            'price_list_materials'
        );
    }
}
