<?php
namespace App\Transfers;

class WarehouseTransferLine extends TransferRecord
{

    protected $table = 'warehouse_transfer_lines';
    protected $fillable = ['requested_quantity'];
    protected $immutableColumns = ['transfer_id','stock_item_id','tracks_items','sku_snapshot','description_snapshot'];
    protected $casts = ['requested_quantity'=>'decimal:2','is_active'=>'boolean','tracks_items'=>'boolean'];
    protected $attributes = ['is_active'=>true];
    protected function initializeNewRecord(): void
    {
        $stock = \App\StockItem::query()->where('is_active',true)->find($this->stock_item_id);
        if (!$stock || !\Illuminate\Support\Facades\DB::table('company_stock_items')->where('tenant_id',$this->tenant_id)->where('company_id',$this->company_id)
            ->where('stock_item_id',$stock->id)->where('is_active',true)->exists()) { $this->invalid('El producto no está habilitado en la empresa.'); }
        $material = \App\Material::query()->find($stock->material_id);
        if (!$material) { $this->invalid('El producto no tiene un material válido.'); }
        $this->tracks_items = (int)$material->tipo_venta_id === 3;
        $this->sku_snapshot = $stock->sku; $this->description_snapshot = $stock->display_name;
    }
    protected function validateReferences(): void
    {
        $this->parentRecord(WarehouseTransfer::class,$this->transfer_id);
        $this->quantity($this->getAttributes()['requested_quantity'] ?? null);
        if ($this->tracks_items && bccomp($this->requested_quantity,(string)(int)$this->requested_quantity,2)!==0) { $this->invalid('Los productos unitarios requieren cantidades enteras.'); }
    }
    public function transfer() { return $this->belongsTo(WarehouseTransfer::class,'transfer_id'); }
    public function stockItem() { return $this->belongsTo(\App\StockItem::class,'stock_item_id'); }
    public function allocations() { return $this->hasMany(WarehouseTransferAllocation::class,'transfer_line_id'); }
}
