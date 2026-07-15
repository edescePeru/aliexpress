<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNoteDetailItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_note_detail_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('credit_note_detail_id');
            $table->unsignedBigInteger('item_id');

            /*
             * OutputDetail puede eliminarse cuando se aplica la devolución,
             * por eso debe ser nullable y conservarse como referencia histórica.
             */
            $table->unsignedBigInteger('output_detail_id')->nullable();

            /*
             * Guardamos también el lote original para conocer
             * exactamente dónde debe retornar el item.
             */
            $table->unsignedBigInteger('stock_lot_id')->nullable();

            /*
             * Para itemeables normalmente será 1.
             * Se deja decimal para respetar el formato general del inventario.
             */
            $table->decimal('quantity', 20, 10)->default(1);

            /*
             * Snapshot mínimo para conservar información aunque
             * posteriormente cambie o se elimine alguna relación.
             */
            $table->string('item_code')->nullable();
            $table->string('item_description')->nullable();

            $table->timestamps();

            $table->foreign('credit_note_detail_id')
                ->references('id')
                ->on('credit_note_details')
                ->onDelete('cascade');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('restrict');

            $table->foreign('output_detail_id')
                ->references('id')
                ->on('output_details')
                ->onDelete('set null');

            $table->foreign('stock_lot_id')
                ->references('id')
                ->on('stock_lots')
                ->onDelete('set null');

            /*
             * El mismo item no puede repetirse dentro del mismo
             * detalle de Nota de Crédito.
             */
            $table->unique(
                ['credit_note_detail_id', 'item_id'],
                'cn_detail_item_unique'
            );

            $table->index('item_id');
            $table->index('output_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_note_detail_items');
    }
}
