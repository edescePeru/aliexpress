<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsoFieldsToDateDimensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('date_dimensions', function (Blueprint $table) {
            $table->unsignedSmallInteger('iso_year')->nullable()->index();
            $table->unsignedTinyInteger('iso_week')->nullable()->index();
            $table->unsignedTinyInteger('iso_day_of_week')->nullable()->index(); // 1..7 (lun..dom)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('date_dimensions', function (Blueprint $table) {
            $table->dropColumn(['iso_year', 'iso_week', 'iso_day_of_week']);
        });
    }
}
