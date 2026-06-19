<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreditNoteStatusToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('credit_note_status', [
                'none',
                'partial',
                'total'
            ])->default('none')->after('annulment_type');
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
            $table->dropColumn('credit_note_status');
        });
    }
}
