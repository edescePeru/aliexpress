<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingValuesTable extends Migration
{
    public function up()
    {
        Schema::create('setting_values', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger(
                'setting_definition_id'
            );

            $table->unsignedBigInteger(
                'tenant_id'
            );

            $table->unsignedBigInteger(
                'company_id'
            )->nullable();

            $table->unsignedBigInteger(
                'branch_id'
            )->nullable();

            $table->text(
                'value_text'
            )->nullable();

            $table->decimal(
                'value_number',
                18,
                6
            )->nullable();

            $table->json(
                'value_json'
            )->nullable();

            $table->timestamps();


            $table->foreign(
                'setting_definition_id'
            )
                ->references('id')
                ->on('setting_definitions')
                ->onDelete('cascade');


            $table->foreign(
                'tenant_id'
            )
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');


            $table->foreign(
                'company_id'
            )
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');


            $table->foreign(
                'branch_id'
            )
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');


            $table->index(
                [
                    'tenant_id',
                    'company_id',
                    'branch_id',
                ],
                'setting_values_scope_idx'
            );


            $table->unique(
                [
                    'setting_definition_id',
                    'tenant_id',
                    'company_id',
                    'branch_id',
                ],
                'setting_values_scope_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists(
            'setting_values'
        );
    }
}
