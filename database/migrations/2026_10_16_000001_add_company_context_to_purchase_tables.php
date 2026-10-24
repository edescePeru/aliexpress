<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyContextToPurchaseTables extends Migration
{
    public function up()
    {
        foreach (['order_purchases', 'order_purchase_details', 'material_orders'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->index(['tenant_id', 'company_id']);
            });
        }
    }
    public function down()
    {
        foreach (['material_orders', 'order_purchase_details', 'order_purchases'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']); $table->dropForeign(['company_id']);
                $table->dropIndex(['tenant_id', 'company_id']);
                $table->dropColumn(['tenant_id', 'company_id']);
            });
        }
    }
}
