<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNoteDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_note_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('sale_detail_id')->nullable();

            $table->string('description');
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('price', 12, 4)->default(0);
            $table->decimal('valor_unitario', 12, 6)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamps();

            $table->foreign('credit_note_id')
                ->references('id')
                ->on('credit_notes')
                ->cascadeOnDelete();

            $table->foreign('sale_detail_id')
                ->references('id')
                ->on('sale_details')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_note_details');
    }
}
