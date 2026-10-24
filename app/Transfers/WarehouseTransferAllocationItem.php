<?php
namespace App\Transfers;

class WarehouseTransferAllocationItem extends TransferRecord
{

    protected $table = 'warehouse_transfer_allocation_items';
    protected $immutableColumns = ['allocation_id','item_id'];
    protected $attributes = ['status'=>'reserved'];
    protected function validateReferences(): void
    {
        $allocation = $this->parentRecord(WarehouseTransferAllocation::class,$this->allocation_id);
        $line = $this->parentRecord(WarehouseTransferLine::class,$allocation->transfer_line_id);
        if (!$line->tracks_items) { $this->invalid('Este producto no se controla con Items unitarios.'); }
        $item = $this->owned('items',$this->item_id);
        if ((int)$item->stock_item_id !== (int)$line->stock_item_id || (!$this->exists && (int)$item->stock_lot_id !== (int)$allocation->source_stock_lot_id)) {
            $this->invalid('El Item no corresponde al lote y producto asignados.');
        }
        if (!$this->exists) {
            $lot = $this->owned('stock_lots',$allocation->source_stock_lot_id);
            if ((int)$item->warehouse_id !== (int)$lot->warehouse_id || (int)$item->location_id !== (int)$lot->location_id) {
                $this->invalid('El Item no conserva el almacén y ubicación del lote de origen.');
            }
        }
        if (!in_array($this->status,['reserved','in_transit','received','cancelled'],true)) { $this->invalid('Estado de Item en traslado inválido.'); }
    }
    public function allocation() { return $this->belongsTo(WarehouseTransferAllocation::class,'allocation_id'); }
    public function item() { return $this->belongsTo(\App\Item::class,'item_id')->where('items.company_id',\App\Support\TenantContext::companyId()); }
    public function receiptItem() { return $this->hasOne(WarehouseTransferReceiptItem::class,'allocation_item_id'); }
}
