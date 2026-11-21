<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class HardenInventoryMovementReferences extends Migration
{
    public function up()
    {
        DB::unprepared(file_get_contents(__DIR__.'/../sql/inventory_movements_context_up.sql'));
    }

    public function down()
    {
        DB::unprepared(file_get_contents(__DIR__.'/../sql/inventory_movements_context_down.sql'));
    }
}
