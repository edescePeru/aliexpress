<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostAndSaleDetailIdToOutputDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('output_details', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_detail_id')->nullable()->after('output_id');
            $table->decimal('unit_cost', 18, 6)->nullable()->after('sale_detail_id');
            $table->decimal('total_cost', 18, 6)->nullable()->after('unit_cost');

            $table->index(['sale_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('output_details', function (Blueprint $table) {
            $table->dropIndex(['sale_detail_id']);
            $table->dropColumn(['sale_detail_id', 'unit_cost', 'total_cost']);
        });
    }
}
