<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinanceTaxSnapshotToDetailEntries extends Migration
{
    public function up()
    {
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->string('finance_tax_kind', 12)->nullable();
            $table->decimal('finance_tax_amount', 12, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('detail_entries', function (Blueprint $table) {
            $table->dropColumn(['finance_tax_kind', 'finance_tax_amount']);
        });
    }
}
