<?php

namespace App;

use App\Support\TenantContext;
use App\Traits\ScopedPurchaseCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPurchase extends Model
{
    use ScopedPurchaseCompany;
    use SoftDeletes;

    protected $appends = ['status'];

    public $fillable = [
        'code',
        'supplier_id',
        'date_arrival',
        'date_order',
        'approved_by',
        'payment_condition',
        'currency_order',
        'currency_compra',
        'currency_venta',
        'igv',
        'total',
        'observation',
        'type',
        'quote_supplier',
        'regularize',
        'payment_deadline_id',
        'status_order',
        'quote_id',
        'state'
    ];

    protected $dates = ['deleted_at', 'date_order', 'date_arrival'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if ($order->type !== 'n') { return; }
            $actor = \Illuminate\Support\Facades\Auth::id();
            if (!$actor) { throw new \RuntimeException('La orden normal requiere un usuario autenticado.'); }
            $order->created_by = $actor;
            $order->updated_by = $actor;
            $order->approved_by = null;
        });
        static::saving(function ($order) {
            if (!$order->exists || $order->type !== 'n') { return; }
            $actor = \Illuminate\Support\Facades\Auth::id();
            if (!$actor) { throw new \RuntimeException('La edición requiere un usuario autenticado.'); }
            $order->created_by = $order->getOriginal('created_by');
            $order->approved_by = $order->getOriginal('approved_by');
            $order->updated_by = $actor;
        });
    }

    public function createdUser()
    {
        return $this->belongsTo(\App\User::class, 'created_by')
            ->where('users.tenant_id', \App\Support\TenantContext::tenantId());
    }

    public function updatedUser()
    {
        return $this->belongsTo(\App\User::class, 'updated_by')
            ->where('users.tenant_id', \App\Support\TenantContext::tenantId());
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function quote()
    {
        return $this->belongsTo('App\Quote');
    }

    public function approved_user()
    {
        return $this->belongsTo('App\User', 'approved_by')->where('users.tenant_id', \App\Support\TenantContext::tenantId());
    }

    public function details()
    {
        return $this->hasMany('App\OrderPurchaseDetail');
    }

    public function deadline()
    {
        return $this->belongsTo('App\PaymentDeadline', 'payment_deadline_id');
    }

    public function entries()
    {
        return $this->hasMany(Entry::class, 'purchase_order', 'code')->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId());
    }

    public function getStatusAttribute()
    {
        $entry = Entry::where('tenant_id', $this->tenant_id)->where('company_id', $this->company_id)->where('purchase_order', $this->code)
            ->get();

        $order_purchase = OrderPurchase::where('code', $this->code)->first();

        if ( count($entry) > 0 )
        {
            $details = OrderPurchaseDetail::where('order_purchase_id', $order_purchase->id)->get();

            if (isset($details))
            {
                foreach ($details as $detail)
                {
                    $material = $detail->material_id;
                    // TODO: obtener las entradas de esa orden y material
                    $cant_material = 0;
                    foreach ( $entry as $entrada )
                    {
                        $entry_details_sum = DetailEntry::where('entry_id', $entrada->id)
                            ->when($detail->stock_item_id, function ($q) use ($detail) { $q->where('stock_item_id', $detail->stock_item_id); }, function ($q) use ($material) { $q->where('material_id', $material); })->sum('entered_quantity');
                        $cant_material += $entry_details_sum;
                    }


                    if ($cant_material < $detail->quantity)
                    {
                        // TODO: Esto significa que esta incompleta
                        return 0;
                    }
                }
                // TODO: Esto significa que esta completa
                return 1;
            }
            // TODO: Esto significa que esta por ingresar
            return 2;
        }
        // TODO: Esto significa que esta por ingresar
        return 2;
        //$number = ($this->entered_quantity * $this->unit_price)/1.18;
        //return number_format($subtotal, 2, '.', '');
    }

    public function receiptEntries()
    {
        return $this->hasMany(Entry::class, 'order_purchase_id')
            ->where('entries.tenant_id', TenantContext::tenantId())
            ->where('entries.company_id', TenantContext::companyId());
    }

}
