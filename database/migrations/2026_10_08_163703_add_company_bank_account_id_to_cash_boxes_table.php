<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyBankAccountIdToCashBoxesTable extends Migration
{
    public function up()
    {
        Schema::table('cash_boxes', function (Blueprint $table) {

            $table
                ->unsignedBigInteger('company_bank_account_id')
                ->nullable()
                ->after('branch_id');

            $table->index(
                'company_bank_account_id',
                'cash_boxes_company_bank_account_id_index'
            );

            $table
                ->foreign(
                    'company_bank_account_id',
                    'cash_boxes_company_bank_account_id_foreign'
                )
                ->references('id')
                ->on('company_bank_accounts')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('cash_boxes', function (Blueprint $table) {

            $table->dropForeign(
                'cash_boxes_company_bank_account_id_foreign'
            );

            $table->dropIndex(
                'cash_boxes_company_bank_account_id_index'
            );

            $table->dropColumn(
                'company_bank_account_id'
            );
        });
    }
}
