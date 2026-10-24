<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseOrderAuthorship extends Migration
{
    public function up()
    {
        Schema::table('order_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
        // Sin backfill: approved_by no demuestra quién creó la orden.
    }
    public function down()
    {
        Schema::table('order_purchases', function (Blueprint $table) {
            $table->dropForeign(['created_by']); $table->dropForeign(['updated_by']);
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }
}
