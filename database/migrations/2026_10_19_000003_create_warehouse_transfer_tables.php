<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateWarehouseTransferTables extends Migration
{
    public function up()
    {
        Schema::create('warehouse_transfers', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wt');
            $this->ref($t, 'source_warehouse_id', 'warehouses', 'wt_source_wh_fk');
            $this->ref($t, 'destination_warehouse_id', 'warehouses', 'wt_dest_wh_fk');
            $this->ref($t, 'source_branch_id', 'branches', 'wt_source_branch_fk', true);
            $this->ref($t, 'destination_branch_id', 'branches', 'wt_dest_branch_fk', true);
            $this->ref($t, 'source_location_id', 'locations', 'wt_source_loc_fk');
            $this->ref($t, 'destination_location_id', 'locations', 'wt_dest_loc_fk');
            $t->enum('status', ['requested', 'prepared', 'dispatched', 'partially_received', 'received', 'cancelled'])->default('requested');
            foreach (['requested', 'prepared', 'dispatched', 'cancelled'] as $action) {
                $this->ref($t, $action.'_by', 'users', 'wt_'.$action.'_user_fk', $action !== 'requested');
                $t->dateTime($action.'_at')->nullable($action !== 'requested');
            }
            $t->dateTime('completed_at')->nullable(); $t->text('notes')->nullable(); $t->text('cancellation_reason')->nullable();
            $t->char('request_key', 36); $t->char('request_hash', 64)->nullable();
            $t->unique('request_key', 'wt_request_key_uq');
            $t->index(['tenant_id','company_id','status','id'], 'wt_company_status_idx');
            $t->timestamps();
        });
        Schema::create('warehouse_transfer_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wtl');
            $this->ref($t, 'transfer_id', 'warehouse_transfers', 'wtl_transfer_fk');
            $this->ref($t, 'stock_item_id', 'stock_items', 'wtl_stock_item_fk');
            $t->decimal('requested_quantity', 12, 2); $t->boolean('is_active')->default(true);
            $t->boolean('tracks_items')->default(false); $t->string('sku_snapshot')->nullable(); $t->text('description_snapshot')->nullable();
            $t->unique(['transfer_id','stock_item_id'], 'wtl_transfer_product_uq'); $t->timestamps();
        });
        Schema::create('warehouse_transfer_allocations', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wta');
            $this->ref($t, 'transfer_line_id', 'warehouse_transfer_lines', 'wta_line_fk');
            $this->ref($t, 'source_stock_lot_id', 'stock_lots', 'wta_source_lot_fk');
            $t->decimal('quantity', 12, 2);
            $t->decimal('source_unit_cost', 18, 6); $t->char('source_currency', 3);
            $t->decimal('base_unit_cost', 18, 6); $t->char('base_currency', 3);
            $t->decimal('exchange_rate', 18, 8)->nullable();
            $t->enum('status', ['reserved','dispatched','cancelled'])->default('reserved');
            $t->unique(['transfer_line_id','source_stock_lot_id'], 'wta_line_lot_uq'); $t->timestamps();
        });
        Schema::create('warehouse_transfer_receipts', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wtr');
            $this->ref($t, 'transfer_id', 'warehouse_transfers', 'wtr_transfer_fk');
            $this->ref($t, 'received_by', 'users', 'wtr_received_user_fk');
            $t->dateTime('received_at'); $t->text('notes')->nullable();
            $t->char('request_key', 36); $t->char('request_hash', 64)->nullable();
            $t->unique('request_key', 'wtr_request_key_uq'); $t->timestamps();
        });
        Schema::create('warehouse_transfer_receipt_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wtrl');
            $this->ref($t, 'receipt_id', 'warehouse_transfer_receipts', 'wtrl_receipt_fk');
            $this->ref($t, 'allocation_id', 'warehouse_transfer_allocations', 'wtrl_allocation_fk');
            $this->ref($t, 'destination_stock_lot_id', 'stock_lots', 'wtrl_destination_lot_fk');
            $t->decimal('quantity', 12, 2);
            $t->unique(['receipt_id','allocation_id'], 'wtrl_receipt_allocation_uq');
            $t->unique('destination_stock_lot_id', 'wtrl_destination_lot_uq'); $t->timestamps();
        });
        Schema::create('warehouse_transfer_allocation_items', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wtai');
            $this->ref($t, 'allocation_id', 'warehouse_transfer_allocations', 'wtai_allocation_fk');
            $this->ref($t, 'item_id', 'items', 'wtai_item_fk');
            $t->enum('status', ['reserved','in_transit','received','cancelled'])->default('reserved');
            $t->unique(['allocation_id','item_id'], 'wtai_allocation_item_uq'); $t->timestamps();
        });
        Schema::create('warehouse_transfer_receipt_items', function (Blueprint $t) {
            $t->bigIncrements('id'); $this->context($t, 'wtri');
            $this->ref($t, 'receipt_line_id', 'warehouse_transfer_receipt_lines', 'wtri_receipt_line_fk');
            $this->ref($t, 'allocation_item_id', 'warehouse_transfer_allocation_items', 'wtri_alloc_item_fk');
            $t->unique('allocation_item_id', 'wtri_alloc_item_once_uq'); $t->timestamps();
        });
    }

    private function context(Blueprint $t, string $prefix): void
    {
        $this->ref($t, 'tenant_id', 'tenants', $prefix.'_tenant_fk');
        $this->ref($t, 'company_id', 'companies', $prefix.'_company_fk');
        $t->index(['tenant_id','company_id'], $prefix.'_company_idx');
    }
    private function ref(Blueprint $t, string $column, string $target, string $name, bool $nullable = false): void
    {
        $t->unsignedBigInteger($column)->nullable($nullable);
        $t->foreign($column, $name)->references('id')->on($target)->onDelete('restrict');
    }
    public function down()
    {
        foreach (['warehouse_transfer_receipt_items','warehouse_transfer_allocation_items','warehouse_transfer_receipt_lines',
            'warehouse_transfer_receipts','warehouse_transfer_allocations','warehouse_transfer_lines','warehouse_transfers'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) { throw new RuntimeException('No se puede revertir esta migración con historial de traslados.'); }
        }
        Schema::dropIfExists('warehouse_transfer_receipt_items');
        Schema::dropIfExists('warehouse_transfer_allocation_items');
        Schema::dropIfExists('warehouse_transfer_receipt_lines');
        Schema::dropIfExists('warehouse_transfer_receipts');
        Schema::dropIfExists('warehouse_transfer_allocations');
        Schema::dropIfExists('warehouse_transfer_lines');
        Schema::dropIfExists('warehouse_transfers');
    }
}
