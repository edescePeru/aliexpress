<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternalReversalFieldsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('internal_reversal_status', [
                'none',
                'reversed'
            ])->default('none')->after('state_annulled');

            $table->timestamp('internal_reversed_at')->nullable()->after('internal_reversal_status');
            $table->unsignedBigInteger('internal_reversed_by')->nullable()->after('internal_reversed_at');

            $table->foreign('internal_reversed_by')
                ->references('id')
                ->on('users')
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
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['internal_reversed_by']);

            $table->dropColumn([
                'internal_reversal_status',
                'internal_reversed_at',
                'internal_reversed_by',
            ]);
        });
    }
}
