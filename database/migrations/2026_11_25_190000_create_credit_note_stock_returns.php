<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNoteStockReturns extends Migration
{
    public function up()
    {
        Schema::table('credit_notes', function (Blueprint $t) { $t->unsignedTinyInteger('stock_return_version')->nullable(); });
        Schema::table('output_details', function (Blueprint $t) { $t->char('cost_currency', 3)->nullable(); });
        Schema::create('credit_note_stock_returns', function (Blueprint $t) {
            $t->bigIncrements('id');
            foreach (['tenant_id', 'company_id', 'branch_id', 'sale_id', 'credit_note_id', 'credit_note_detail_id',
                'output_detail_id', 'stock_item_id', 'material_id', 'stock_lot_id', 'warehouse_id', 'location_id'] as $field) {
                $t->unsignedBigInteger($field);
            }
            $t->unsignedBigInteger('item_id')->nullable();
            $t->unsignedBigInteger('recorded_by')->nullable();
            $t->decimal('quantity', 20, 6);
            $t->decimal('unit_cost', 24, 10);
            $t->decimal('total_cost', 30, 10);
            $t->char('base_currency', 3)->nullable();
            $t->string('cost_source', 32);
            $t->string('currency_source', 32);
            $t->string('record_source', 32);
            $t->timestamp('movement_date', 6);
            $t->timestamps();
            $t->unique(['credit_note_detail_id', 'output_detail_id'], 'nc_return_detail_output_unique');
            $t->index(['tenant_id', 'company_id', 'stock_item_id', 'movement_date'], 'nc_return_stock_timeline');
            $t->index(['credit_note_id', 'tenant_id', 'company_id'], 'nc_return_note_context');
            $t->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('restrict');
            $t->foreign('credit_note_detail_id')->references('id')->on('credit_note_details')->onDelete('restrict');
            $t->foreign('output_detail_id')->references('id')->on('output_details')->onDelete('restrict');
        });
    }
    public function down()
    {
        Schema::dropIfExists('credit_note_stock_returns');
        Schema::table('output_details', function (Blueprint $t) { $t->dropColumn('cost_currency'); });
        Schema::table('credit_notes', function (Blueprint $t) { $t->dropColumn('stock_return_version'); });
    }
}
