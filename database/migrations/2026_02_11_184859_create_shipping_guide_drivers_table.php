<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingGuideDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_guide_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_guide_id');

            $table->boolean('is_primary')->default(false);

            $table->string('document_type_code', 2); // "1","4","7","A","G","B","C","D"
            $table->string('document_number', 20);

            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('license_number', 30);

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
        Schema::dropIfExists('shipping_guide_drivers');
    }
}
