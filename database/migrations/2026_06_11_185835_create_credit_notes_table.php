<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id');

            $table->string('type_document')->default('07'); // Nota de crédito
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();

            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();

            $table->decimal('op_gravada', 12, 2)->default(0);
            $table->decimal('op_exonerada', 12, 2)->default(0);
            $table->decimal('op_inafecta', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total_descuentos', 12, 2)->default(0);
            $table->decimal('importe_total', 12, 2)->default(0);

            $table->enum('credit_note_type', [
                'total',
                'partial'
            ])->default('total');

            $table->enum('status', [
                'draft',
                'pending',
                'accepted',
                'rejected',
                'error'
            ])->default('draft');

            $table->string('sunat_status')->nullable();
            $table->text('sunat_message')->nullable();
            $table->string('sunat_ticket')->nullable();

            $table->string('nubefact_key')->nullable();
            $table->longText('nubefact_response')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();

            $table->text('pdf_url')->nullable();
            $table->text('xml_url')->nullable();
            $table->text('cdr_url')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('credit_notes');
    }
}
