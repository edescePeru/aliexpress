<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceReferenceToSaleDetails extends Migration
{
    public function up()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            // Historical reference intentionally has no cascading FK.
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->string('price_list_name', 100)->nullable();
            $table->decimal('catalog_unit_price', 18, 4)->nullable();
            $table->string('catalog_price_source', 20)->nullable();
        });
    }
    public function down()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['price_list_id','price_list_name','catalog_unit_price','catalog_price_source']);
        });
    }
}
