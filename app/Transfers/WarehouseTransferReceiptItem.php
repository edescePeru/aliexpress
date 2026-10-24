<?php
namespace App\Transfers;

class WarehouseTransferReceiptItem extends TransferRecord
{

    protected $table = 'warehouse_transfer_receipt_items';
    protected $appendOnly = true;
    protected function validateReferences(): void
    {
        $line = $this->parentRecord(WarehouseTransferReceiptLine::class,$this->receipt_line_id);
        $item = $this->parentRecord(WarehouseTransferAllocationItem::class,$this->allocation_item_id);
        if ((int)$line->allocation_id !== (int)$item->allocation_id) { $this->invalid('El Item recibido pertenece a otra asignación.'); }
    }
    public function receiptLine() { return $this->belongsTo(WarehouseTransferReceiptLine::class,'receipt_line_id'); }
    public function allocationItem() { return $this->belongsTo(WarehouseTransferAllocationItem::class,'allocation_item_id'); }
}
