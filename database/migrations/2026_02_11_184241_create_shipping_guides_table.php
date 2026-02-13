<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingGuidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_guides', function (Blueprint $table) {
            $table->id();
            // Tipo guía / comprobante
            $table->enum('guide_type', ['REMITENTE', 'TRANSPORTISTA'])->default('REMITENTE');
            $table->unsignedTinyInteger('tipo_de_comprobante'); // 7 remitente, 8 transportista
            $table->string('serie', 4);
            $table->string('numero', 20)->nullable(); // editable por "por si acaso"

            // Destinatario (snapshot + opcional link a customers)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_doc_type', 2)->nullable();      // "6" RUC, "1" DNI, etc (snapshot)
            $table->string('customer_doc_number', 20)->nullable();
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_address', 255)->nullable();
            $table->string('customer_email', 120)->nullable();
            $table->string('customer_email_1', 120)->nullable();
            $table->string('customer_email_2', 120)->nullable();

            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_inicio_traslado');

            // Traslado
            $table->string('motivo_traslado_code', 2);
            $table->string('tipo_transporte', 2); // "01" público, "02" privado

            $table->decimal('peso_bruto_total', 12, 3)->default(0);
            $table->string('peso_bruto_um_code', 3)->default('KGM'); // KGM/TNE
            $table->unsignedInteger('numero_bultos')->default(1);

            $table->string('sunat_shipping_indicator_code', 60)->nullable(); // opcional

            // Partida
            $table->string('partida_ubigeo', 6);
            $table->string('partida_direccion', 255);
            $table->string('partida_cod_establecimiento', 4)->default('0000');

            // Llegada
            $table->string('llegada_ubigeo', 6);
            $table->string('llegada_direccion', 255);
            $table->string('llegada_cod_establecimiento', 4)->default('0000');

            // Items mode (SALE / MANUAL)
            $table->enum('items_mode', ['SALE', 'MANUAL'])->default('MANUAL');
            $table->unsignedBigInteger('source_sale_id')->nullable();
            $table->string('source_sale_ref', 40)->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Tracking Nubefact/SUNAT
            $table->enum('status', ['DRAFT', 'SENT', 'PENDING_SUNAT', 'ACCEPTED', 'REJECTED', 'ERROR'])
                ->default('DRAFT');

            $table->boolean('nubefact_accepted')->default(false);
            $table->string('nubefact_enlace', 255)->nullable();

            $table->string('sunat_description', 255)->nullable();
            $table->string('sunat_note', 255)->nullable();
            $table->string('sunat_responsecode', 30)->nullable();
            $table->text('sunat_soap_error')->nullable();

            $table->string('pdf_link', 255)->nullable();
            $table->string('xml_link', 255)->nullable();
            $table->string('cdr_link', 255)->nullable();

            $table->json('last_nubefact_payload')->nullable();
            $table->json('last_nubefact_response')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index(['fecha_emision']);
            $table->index(['guide_type']);
            $table->index(['serie', 'numero']);
            $table->index(['status']);

            // (Opcional) Unique por serie+numero+tipo si siempre será único:
            // $table->unique(['tipo_de_comprobante', 'serie', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_guides');
    }
}
