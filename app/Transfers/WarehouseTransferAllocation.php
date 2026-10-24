<?php
namespace App\Transfers;

class WarehouseTransferAllocation extends TransferRecord
{

    protected $table = 'warehouse_transfer_allocations';
    protected $immutableColumns = ['transfer_line_id','source_stock_lot_id','quantity','source_unit_cost','source_currency','base_unit_cost','base_currency','exchange_rate'];
    protected $attributes = ['status'=>'reserved'];
    protected $casts = ['quantity'=>'decimal:2','source_unit_cost'=>'decimal:6','base_unit_cost'=>'decimal:6','exchange_rate'=>'decimal:8'];
    protected function validateReferences(): void
    {
        $line = $this->parentRecord(WarehouseTransferLine::class,$this->transfer_line_id);
        $transfer = $this->parentRecord(WarehouseTransfer::class,$line->transfer_id);
        $lot = $this->owned('stock_lots',$this->source_stock_lot_id);
        if ((int)$lot->stock_item_id !== (int)$line->stock_item_id || (int)$lot->warehouse_id !== (int)$transfer->source_warehouse_id
            || (int)$lot->location_id !== (int)$transfer->source_location_id) { $this->invalid('El lote no corresponde al producto y origen del traslado.'); }
        $this->quantity($this->getAttributes()['quantity'] ?? null); $this->cost($this->getAttributes()['source_unit_cost'] ?? null); $this->cost($this->getAttributes()['base_unit_cost'] ?? null);
        if (bccomp($this->quantity,$line->requested_quantity,2)>0) { $this->invalid('La asignación supera la cantidad solicitada.'); }
        if ($line->tracks_items && bccomp($this->quantity,(string)(int)$this->quantity,2)!==0) { $this->invalid('La asignación de unidades debe ser entera.'); }
        foreach (['source_currency','base_currency'] as $field) { if (!preg_match('/^[A-Z]{3}$/D',(string)$this->$field)) { $this->invalid('La moneda del costo es inválida.'); } }
        if (!is_numeric($lot->unit_cost) || bccomp((string)$lot->unit_cost,'0',6)<0) { $this->invalid('El lote de origen no tiene un costo verificable.'); }
        if (!$this->exists && bccomp($this->source_unit_cost,(string)$lot->unit_cost,6)!==0) { $this->invalid('El costo de origen no coincide con el lote.'); }
        if (!in_array($this->status,['reserved','dispatched','cancelled'],true)) { $this->invalid('Estado de asignación inválido.'); }
        if ($this->exchange_rate !== null && (!is_numeric($this->exchange_rate) || bccomp($this->exchange_rate,'0',8)<=0)) { $this->invalid('El tipo de cambio debe ser positivo.'); }
    }
    public function line() { return $this->belongsTo(WarehouseTransferLine::class,'transfer_line_id'); }
    public function sourceLot() { return $this->belongsTo(\App\StockLot::class,'source_stock_lot_id')->where('stock_lots.company_id',\App\Support\TenantContext::companyId()); }
    public function receiptLines() { return $this->hasMany(WarehouseTransferReceiptLine::class,'allocation_id'); }
    public function items() { return $this->hasMany(WarehouseTransferAllocationItem::class,'allocation_id'); }
}
