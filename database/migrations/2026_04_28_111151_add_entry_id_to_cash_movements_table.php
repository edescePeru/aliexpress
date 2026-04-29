<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntryIdToCashMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('entry_id')->nullable()->after('sale_id');
            $table->index('entry_id');

            $table->foreign('entry_id')
                ->references('id')
                ->on('entries')
                ->onDelete('set null');
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
            $table->dropForeign(['entry_id']);
            $table->dropIndex(['entry_id']);
            $table->dropColumn('entry_id');
        });
    }
}
