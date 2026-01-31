<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsMissingToCashMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->text('observation')->nullable()->after('description');

            $table->unsignedBigInteger('cash_movement_origin_id')->nullable()->after('cash_box_subtype_id');
            $table->unsignedBigInteger('cash_movement_regularize_id')->nullable()->after('cash_movement_origin_id');

            $table->decimal('amount_regularize', 12, 2)->nullable()->after('amount');
            $table->decimal('commission', 12, 2)->nullable()->after('amount_regularize');

            $table->index('cash_movement_origin_id');
            $table->index('cash_movement_regularize_id');

            $table->foreign('cash_movement_origin_id')->references('id')->on('cash_movements')->onDelete('set null');;
            $table->foreign('cash_movement_regularize_id')->references('id')->on('cash_movements')->onDelete('set null');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropForeign(['cash_movement_origin_id']);
            $table->dropForeign(['cash_movement_regularize_id']);

            $table->dropIndex(['cash_movement_origin_id']);
            $table->dropIndex(['cash_movement_regularize_id']);

            $table->dropColumn([
                'observation',
                'cash_movement_origin_id',
                'cash_movement_regularize_id',
                'amount_regularize',
                'commission',
            ]);
        });
    }
}
