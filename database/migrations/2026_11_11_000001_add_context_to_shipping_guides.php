<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextToShippingGuides extends Migration
{
    public function up()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->index(['tenant_id', 'company_id', 'branch_id', 'id'], 'gre_local_lookup');
            $table->unique(['tenant_id', 'company_id', 'tipo_de_comprobante', 'serie', 'numero'], 'gre_company_document_unique');
        });
    }

    public function down()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->dropUnique('gre_company_document_unique');
            $table->dropIndex('gre_local_lookup');
            $table->dropColumn(['tenant_id', 'company_id', 'branch_id']);
        });
    }
}
