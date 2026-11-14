<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyContextAndUsersToMetas extends Migration
{
    public function up()
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->index(['tenant_id', 'company_id', 'fecha_inicio', 'fecha_fin'], 'metas_company_period_idx');
        });

        Schema::create('meta_users', function (Blueprint $table) {
            $table->unsignedBigInteger('meta_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->primary(['meta_id', 'user_id']);
            $table->foreign('meta_id')->references('id')->on('metas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('meta_users');
        Schema::table('metas', function (Blueprint $table) {
            $table->dropIndex('metas_company_period_idx');
            $table->dropColumn('tenant_id');
            $table->dropColumn('company_id');
        });
    }
}
