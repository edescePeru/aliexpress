<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GreRequestFingerprintAndSubmissionState extends Migration
{
    public function up()
    {
        Schema::table('shipping_guides', function(Blueprint $table){
            $table->char('emission_fingerprint',64)->nullable();
            $table->string('submission_state',32)->nullable();
            $table->unsignedSmallInteger('submission_http_status')->nullable();
            $table->string('submission_provider_code',8)->nullable();
            $table->char('submission_diagnostic_reference',24)->nullable();
        });
    }
    public function down()
    {
        throw new \RuntimeException('Conservar huellas y contrato de emisión; revisar antes de retirar columnas de operaciones existentes.');
    }
}
