<?php
namespace App\Transfers;

class WarehouseTransferReceipt extends TransferRecord
{

    protected $table = 'warehouse_transfer_receipts';
    protected $fillable = ['notes'];
    protected $appendOnly = true;
    protected $casts = ['received_at'=>'datetime'];
    protected function initializeNewRecord(): void
    {
        if (!\Illuminate\Support\Facades\Auth::id() || !\Illuminate\Support\Facades\DB::table('users')->where('id', \Illuminate\Support\Facades\Auth::id())->where('tenant_id', $this->tenant_id)->exists()) { $this->invalid('La recepción requiere un usuario autenticado del tenant actual.'); }
        $this->received_by = \Illuminate\Support\Facades\Auth::id(); $this->received_at = now(); $this->requestIdentity();
    }
    protected function validateReferences(): void { $this->parentRecord(WarehouseTransfer::class,$this->transfer_id); }
    public function transfer() { return $this->belongsTo(WarehouseTransfer::class,'transfer_id'); }
    public function lines() { return $this->hasMany(WarehouseTransferReceiptLine::class,'receipt_id'); }
}
