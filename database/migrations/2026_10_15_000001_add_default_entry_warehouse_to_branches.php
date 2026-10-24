<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultEntryWarehouseToBranches extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('default_entry_warehouse_id')->nullable();
            $table->foreign('default_entry_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['default_entry_warehouse_id']);
            $table->dropColumn('default_entry_warehouse_id');
        });
    }
}
