<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingGuideVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_guide_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_guide_id');

            $table->boolean('is_primary')->default(false);
            $table->string('plate_number', 20);
            $table->string('tuc', 30)->nullable();

            $table->timestamps();

            $table->foreign('shipping_guide_id')
                ->references('id')->on('shipping_guides')
                ->onDelete('cascade');

            $table->index(['shipping_guide_id']);
            $table->index(['is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_guide_vehicles');
    }
}
