<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseReceiptKey extends Migration
{
    public function up()
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('purchase_receipt_key', 36)->nullable();
            $table->string('purchase_receipt_hash', 64)->nullable();
            $table->unique('purchase_receipt_key', 'entries_receipt_key_unique');
        });
    }

    public function down()
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropUnique('entries_receipt_key_unique');
            $table->dropColumn(['purchase_receipt_key', 'purchase_receipt_hash']);
        });
    }
}
