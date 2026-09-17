<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveLegacyTimeBreakFromPercentageWorkers extends Migration
{
    public function up()
    {
        DB::table('percentage_workers')
            ->where('name', 'time_break')
            ->delete();
    }

    public function down()
    {
        // No recreamos el dato legacy.
        // Ahora se obtiene desde hr.break_hours.
    }
}
