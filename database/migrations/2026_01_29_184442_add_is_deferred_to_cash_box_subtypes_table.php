<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDeferredToCashBoxSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_box_subtypes', function (Blueprint $table) {
            $table->boolean('is_deferred')->default(false)->after('name');
            $table->boolean('requires_commission')->default(false)->after('is_deferred');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_box_subtypes', function (Blueprint $table) {
            //
        });
    }
}
