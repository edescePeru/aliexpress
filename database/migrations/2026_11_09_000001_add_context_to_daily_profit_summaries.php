<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextToDailyProfitSummaries extends Migration
{
    public function up()
    {
        Schema::table('ganancia_diarias', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unique(['tenant_id', 'company_id', 'branch_id', 'currency', 'date_resumen'], 'daily_profit_context_date_unique');
        });
    }

    public function down()
    {
        Schema::table('ganancia_diarias', function (Blueprint $table) {
            $table->dropUnique('daily_profit_context_date_unique');
            $table->dropColumn(['tenant_id', 'company_id', 'branch_id', 'currency']);
        });
    }
}
