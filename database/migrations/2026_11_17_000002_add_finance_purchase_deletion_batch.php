<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFinancePurchaseDeletionBatch extends Migration
{
    public function up()
    {
        foreach (['order_purchase_finances', 'order_purchase_finance_details'] as $name) {
            if (!Schema::hasColumn($name, 'deletion_batch')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->string('deletion_batch', 36)->nullable();
                });
            }
        }
    }

    public function down()
    {
        foreach (['order_purchase_finances', 'order_purchase_finance_details'] as $name) {
            if (Schema::hasColumn($name, 'deletion_batch') && DB::table($name)->whereNotNull('deletion_batch')->exists()) {
                throw new RuntimeException('Hay eliminaciones pendientes de restaurar. No se puede quitar su trazabilidad.');
            }
        }
        foreach (['order_purchase_finance_details', 'order_purchase_finances'] as $name) {
            if (Schema::hasColumn($name, 'deletion_batch')) {
                Schema::table($name, function (Blueprint $table) { $table->dropColumn('deletion_batch'); });
            }
        }
    }
}
