<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_id')->nullable();

            $table->string('title', 150);
            $table->string('account_number', 100);
            $table->string('cci', 100)->nullable();

            $table->string('currency', 3)->nullable();

            $table->string('account_holder', 200)->nullable();
            $table->string('image')->nullable();

            $table->unsignedInteger('position')->default(1);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('bank_id')
                ->references('id')
                ->on('banks');

            $table->index([
                'tenant_id',
                'company_id',
                'is_active',
            ], 'company_bank_accounts_scope_index');

            $table->index([
                'company_id',
                'position',
            ], 'company_bank_accounts_position_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_bank_accounts');
    }
}
