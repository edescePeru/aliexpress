<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseReceiptLinks extends Migration
{
    public function up()
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->unsignedBigInteger('order_purchase_id')->nullable();
            $table->index('order_purchase_id', 'entries_receipt_order_idx');
            $table->foreign('order_purchase_id', 'entries_receipt_order_fk')
                ->references('id')->on('order_purchases')->onDelete('restrict');
        });
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('order_purchase_detail_id')->nullable();
            $table->index('order_purchase_detail_id', 'details_receipt_line_idx');
            $table->foreign('order_purchase_detail_id', 'details_receipt_line_fk')
                ->references('id')->on('order_purchase_details')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->dropForeign('details_receipt_line_fk');
            $table->dropIndex('details_receipt_line_idx');
            $table->dropColumn('order_purchase_detail_id');
        });
        Schema::table('entries', function (Blueprint $table) {
            $table->dropForeign('entries_receipt_order_fk');
            $table->dropIndex('entries_receipt_order_idx');
            $table->dropColumn('order_purchase_id');
        });
    }
}
