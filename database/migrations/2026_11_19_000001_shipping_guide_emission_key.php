<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShippingGuideEmissionKey extends Migration
{
    public function up()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->char('emission_key', 36)->nullable();
            $table->unique(['tenant_id', 'company_id', 'emission_key'], 'shipping_guides_emission_unique');
        });
    }

    public function down()
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->dropUnique('shipping_guides_emission_unique');
            $table->dropColumn('emission_key');
        });
    }
}
