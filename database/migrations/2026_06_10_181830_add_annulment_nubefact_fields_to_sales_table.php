<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnnulmentNubefactFieldsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('annulment_key')->nullable()->after('annulment_ticket');

            $table->string('annulment_sunat_status')->nullable()->after('annulment_key');
            $table->text('annulment_sunat_message')->nullable()->after('annulment_sunat_status');
            $table->string('annulment_sunat_responsecode')->nullable()->after('annulment_sunat_message');

            $table->string('annulment_pdf_path')->nullable()->after('annulment_response');
            $table->string('annulment_xml_path')->nullable()->after('annulment_pdf_path');
            $table->string('annulment_cdr_path')->nullable()->after('annulment_xml_path');

            $table->text('annulment_pdf_url')->nullable()->after('annulment_cdr_path');
            $table->text('annulment_xml_url')->nullable()->after('annulment_pdf_url');
            $table->text('annulment_cdr_url')->nullable()->after('annulment_xml_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'annulment_key',
                'annulment_sunat_status',
                'annulment_sunat_message',
                'annulment_sunat_responsecode',
                'annulment_pdf_path',
                'annulment_xml_path',
                'annulment_cdr_path',
                'annulment_pdf_url',
                'annulment_xml_url',
                'annulment_cdr_url',
            ]);
        });
    }
}
