<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashBoxSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_box_subtypes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cash_box_id')->nullable();
            // nullable = subtype disponible para toda la empresa
            // si no es nullable = subtype limitado a una caja específica

            $table->string('code');  // 'yape', 'plin', 'pos', 'transfer'
            $table->string('name');  // 'Yape', 'Plin', 'POS', 'Transferencia'
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);

            $table->timestamps();

            $table->unique(['cash_box_id', 'code']);
            $table->index(['cash_box_id']);

            $table->foreign('cash_box_id')->references('id')->on('cash_boxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_box_subtypes');
    }
}
