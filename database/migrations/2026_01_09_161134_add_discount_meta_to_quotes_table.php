<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountMetaToQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('discount_type', 20)->nullable()->after('descuento');
            $table->string('discount_input_mode', 20)->nullable()->after('discount_type');
            $table->decimal('discount_input_value', 12, 2)->nullable()->after('discount_input_mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_input_mode', 'discount_input_value']);
        });
    }
}
