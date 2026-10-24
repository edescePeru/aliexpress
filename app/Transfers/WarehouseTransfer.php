<?php
namespace App\Transfers;

class WarehouseTransfer extends TransferRecord
{

    protected $table = 'warehouse_transfers';
    protected $fillable = ['notes'];
    protected $appends = ['code'];
    protected $attributes = ['status' => 'requested'];
    protected $immutableColumns = ['source_warehouse_id','destination_warehouse_id','source_location_id','destination_location_id','source_branch_id','destination_branch_id','requested_by','requested_at','request_key','request_hash'];
    protected $casts = ['requested_at'=>'datetime','prepared_at'=>'datetime','dispatched_at'=>'datetime','completed_at'=>'datetime','cancelled_at'=>'datetime'];
    const REQUESTED='requested'; const PREPARED='prepared'; const DISPATCHED='dispatched';
    const PARTIALLY_RECEIVED='partially_received'; const RECEIVED='received'; const CANCELLED='cancelled';

    protected function initializeNewRecord(): void
    {
        if (!\Illuminate\Support\Facades\Auth::id() || !\Illuminate\Support\Facades\DB::table('users')->where('id', \Illuminate\Support\Facades\Auth::id())->where('tenant_id', $this->tenant_id)->exists()) { $this->invalid('El traslado requiere un usuario autenticado del tenant actual.'); }
        $this->requested_by = \Illuminate\Support\Facades\Auth::id();
        $this->requested_at = now(); $this->requestIdentity();
        foreach (['source','destination'] as $side) {
            $warehouse = $this->owned('warehouses', $this->{$side.'_warehouse_id'});
            $locations = \Illuminate\Support\Facades\DB::table('locations')->where('tenant_id', $this->tenant_id)->where('company_id', $this->company_id)
                ->where('warehouse_id', $warehouse->id)->orderBy('id')->limit(2)->get();
            if ($locations->count() !== 1) { $this->invalid('Cada almacén requiere exactamente una ubicación operativa.'); }
            $this->{$side.'_branch_id'} = $warehouse->branch_id;
            $this->{$side.'_location_id'} = $locations->first()->id;
        }
    }
    protected function validateReferences(): void
    {
        if ((int)$this->source_warehouse_id === (int)$this->destination_warehouse_id) { $this->invalid('Origen y destino deben ser almacenes diferentes.'); }
        foreach (['source','destination'] as $side) {
            $warehouse = $this->owned('warehouses', $this->{$side.'_warehouse_id'});
            $location = $this->owned('locations', $this->{$side.'_location_id'});
            if ((int)$location->warehouse_id !== (int)$warehouse->id || (string)$warehouse->branch_id !== (string)$this->{$side.'_branch_id'}) {
                $this->invalid('El almacén, sucursal y ubicación no coinciden con el origen registrado.');
            }
            // Branch obtiene el tenant a través de Company; no tiene tenant_id propio.
            if ($warehouse->branch_id !== null && !\Illuminate\Support\Facades\DB::table('branches')
                ->where('id', $warehouse->branch_id)->where('company_id', $this->company_id)->exists()) {
                $this->invalid('La sucursal del almacén no pertenece a la empresa.');
            }
        }
        if (!in_array($this->status,[self::REQUESTED,self::PREPARED,self::DISPATCHED,self::PARTIALLY_RECEIVED,self::RECEIVED,self::CANCELLED],true)) { $this->invalid('Estado de traslado inválido.'); }
    }
    public function getCodeAttribute() { return $this->id ? 'TR-'.str_pad((string)$this->id,6,'0',STR_PAD_LEFT) : null; }
    public function lines() { return $this->hasMany(WarehouseTransferLine::class,'transfer_id'); }
    public function receipts() { return $this->hasMany(WarehouseTransferReceipt::class,'transfer_id'); }
    public function sourceWarehouse() { return $this->belongsTo(\App\Warehouse::class,'source_warehouse_id')->where('warehouses.company_id',\App\Support\TenantContext::companyId()); }
    public function destinationWarehouse() { return $this->belongsTo(\App\Warehouse::class,'destination_warehouse_id')->where('warehouses.company_id',\App\Support\TenantContext::companyId()); }
}
