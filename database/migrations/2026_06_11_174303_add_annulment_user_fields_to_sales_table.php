<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnnulmentUserFieldsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('annulment_requested_by')->nullable()->after('annulment_requested_at');
            $table->unsignedBigInteger('annulled_by')->nullable()->after('annulment_accepted_at');

            $table->foreign('annulment_requested_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('annulled_by')
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
            $table->dropForeign(['annulment_requested_by']);
            $table->dropForeign(['annulled_by']);

            $table->dropColumn([
                'annulment_requested_by',
                'annulled_by',
            ]);
        });
    }
}
