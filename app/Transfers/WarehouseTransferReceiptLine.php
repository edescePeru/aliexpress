<?php
namespace App\Transfers;

class WarehouseTransferReceiptLine extends TransferRecord
{

    protected $table = 'warehouse_transfer_receipt_lines';
    protected $appendOnly = true;
    protected $casts = ['quantity'=>'decimal:2'];
    protected function validateReferences(): void
    {
        $receipt = $this->parentRecord(WarehouseTransferReceipt::class,$this->receipt_id);
        $allocation = $this->parentRecord(WarehouseTransferAllocation::class,$this->allocation_id);
        $line = $this->parentRecord(WarehouseTransferLine::class,$allocation->transfer_line_id);
        if ((int)$line->transfer_id !== (int)$receipt->transfer_id) { $this->invalid('La recepción y asignación pertenecen a traslados distintos.'); }
        $transfer = $this->parentRecord(WarehouseTransfer::class,$receipt->transfer_id);
        $lot = $this->owned('stock_lots',$this->destination_stock_lot_id);
        if ((int)$lot->stock_item_id !== (int)$line->stock_item_id || (int)$lot->warehouse_id !== (int)$transfer->destination_warehouse_id
            || (int)$lot->location_id !== (int)$transfer->destination_location_id || $lot->detail_entry_id !== null) {
            $this->invalid('El lote de destino no es un lote independiente y válido para esta recepción.');
        }
        $this->quantity($this->getAttributes()['quantity'] ?? null);
        if (bccomp($this->quantity,$allocation->quantity,2)>0) { $this->invalid('La recepción supera la cantidad de esa asignación.'); }
        if ($line->tracks_items && bccomp($this->quantity,(string)(int)$this->quantity,2)!==0) { $this->invalid('La recepción de unidades debe ser entera.'); }
    }
    public function receipt() { return $this->belongsTo(WarehouseTransferReceipt::class,'receipt_id'); }
    public function allocation() { return $this->belongsTo(WarehouseTransferAllocation::class,'allocation_id'); }
    public function destinationLot() { return $this->belongsTo(\App\StockLot::class,'destination_stock_lot_id')->where('stock_lots.company_id',\App\Support\TenantContext::companyId()); }
    public function items() { return $this->hasMany(WarehouseTransferReceiptItem::class,'receipt_line_id'); }
}
