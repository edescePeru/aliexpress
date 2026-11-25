<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStructureLimitsToPlans extends Migration
{
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            // NULL means not configured; never unlimited or an invented entitlement.
            $table->unsignedInteger('max_active_companies')->nullable();
            $table->unsignedInteger('max_active_branches')->nullable();
        });
    }

    public function down()
    {
        throw new \RuntimeException('Los cupos deben conservarse. Revertir código y esquema requiere una revisión explícita del plan contratado.');
    }
}
