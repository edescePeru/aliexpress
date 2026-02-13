<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingGuideItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_guide_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_guide_id');

            $table->unsignedInteger('line')->default(1);

            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('codigo', 60)->nullable();
            $table->string('descripcion', 255);
            $table->string('detalle_adicional', 255)->nullable();

            $table->decimal('cantidad', 12, 3)->default(1);
            $table->string('unidad_medida', 3)->default('NIU');

            $table->timestamps();

            $table->foreign('shipping_guide_id')
                ->references('id')->on('shipping_guides')
                ->onDelete('cascade');

            $table->index(['shipping_guide_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_guide_items');
    }
}
