<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillableToEquipmentWorkforcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('equipment_workforces', function (Blueprint $table) {
            $table->boolean('billable')->default(true)->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equipment_workforces', function (Blueprint $table) {
            $table->dropColumn('billable');
        });
    }
}
