<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternalReversalAndRefundFieldsToCreditNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->enum('internal_reversal_status', [
                'none',
                'reversed'
            ])->default('none')->after('status');

            $table->timestamp('internal_reversed_at')->nullable()->after('internal_reversal_status');
            $table->unsignedBigInteger('internal_reversed_by')->nullable()->after('internal_reversed_at');

            $table->enum('cash_refund_status', [
                'none',
                'refunded'
            ])->default('none')->after('internal_reversed_by');

            $table->timestamp('cash_refund_at')->nullable()->after('cash_refund_status');
            $table->unsignedBigInteger('cash_refund_by')->nullable()->after('cash_refund_at');
            $table->unsignedBigInteger('cash_movement_id')->nullable()->after('cash_refund_by');

            $table->foreign('internal_reversed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('cash_refund_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('cash_movement_id')
                ->references('id')
                ->on('cash_movements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['internal_reversed_by']);
            $table->dropForeign(['cash_refund_by']);
            $table->dropForeign(['cash_movement_id']);

            $table->dropColumn([
                'internal_reversal_status',
                'internal_reversed_at',
                'internal_reversed_by',
                'cash_refund_status',
                'cash_refund_at',
                'cash_refund_by',
                'cash_movement_id',
            ]);
        });
    }
}
