<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_boxes', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // "Efectivo", "Bancario BCP", "Yape", etc.
            $table->string('type'); // 'cash' | 'bank' (o lo que definas)
            $table->boolean('uses_subtypes')->default(false);

            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);

            // Datos opcionales de cuenta/banco (sin tabla aparte)
            $table->string('bank_name')->nullable();
            $table->string('account_label')->nullable();
            $table->string('account_number_mask')->nullable();
            $table->string('currency', 3)->nullable(); // 'PEN', 'USD'

            $table->timestamps();

            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_boxes');
    }
}
