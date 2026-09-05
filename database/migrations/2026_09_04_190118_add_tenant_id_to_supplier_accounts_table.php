<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantIdToSupplierAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('supplier_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->after('id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->index(
                ['tenant_id', 'supplier_id'],
                'supplier_accounts_tenant_supplier_idx'
            );
        });
    }

    public function down()
    {
        Schema::table('supplier_accounts', function (Blueprint $table) {

            $table->dropForeign([
                'tenant_id'
            ]);

            $table->dropIndex(
                'supplier_accounts_tenant_supplier_idx'
            );

            $table->dropColumn(
                'tenant_id'
            );
        });
    }
}
