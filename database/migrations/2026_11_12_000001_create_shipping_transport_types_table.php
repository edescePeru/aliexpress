<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingTransportTypesTable extends Migration
{
    public function up()
    {
        Schema::create('shipping_transport_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('shipping_transport_types')->insert([
            ['code' => '01', 'name' => 'Público (Agencia)', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '02', 'name' => 'Privado (Propio)', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('shipping_transport_types');
    }
}