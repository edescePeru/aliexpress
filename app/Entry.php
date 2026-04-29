<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    use SoftDeletes;

    protected $appends = ['sub_total', 'taxes', 'total'];

    protected $fillable = [
        'referral_guide',
        'purchase_order',
        'invoice',
        'deferred_invoice',
        'supplier_id',
        'entry_type',
        'date_entry',
        'finance',
        'currency_invoice',
        'currency_compra',
        'currency_venta',
        'observation',
        'image',
        'imageOb',
        'type_order',
        'category_invoice_id',
        'state_paid',
        'state_annulled'
    ];

    public function getSubTotalAttribute()
    {
        $subtotal = 0;
        foreach ( $this->details as $detail )
        {
            if ( $detail->total_detail != null )
            {
                $subtotal += ($detail->total_detail)/1.18;
            } else {
                $subtotal += ($detail->entered_quantity * $detail->unit_price)/1.18;
            }

        }
        //$number = ($this->entered_quantity * $this->unit_price)/1.18;
        return number_format($subtotal, 2, '.', '');
    }

    public function getTaxesAttribute()
    {
        $taxes = 0;
        foreach ( $this->details as $detail )
        {
            if ( $detail->total_detail != null )
            {
                $taxes += (($detail->total_detail)/1.18)*0.18;
            } else {
                $taxes += (($detail->entered_quantity * $detail->unit_price)/1.18)*0.18;
            }

        }

        return number_format($taxes, 2,'.', '');
    }

    public function getTotalAttribute()
    {
        $total = 0;
        foreach ( $this->details as $detail )
        {
            if ( $detail->total_detail != null )
            {
                $total += $detail->total_detail;
            } else {
                $total += $detail->entered_quantity * $detail->unit_price;
            }

        }
        return number_format($total, 2, '.', '');
    }

    public function details()
    {
        return $this->hasMany('App\DetailEntry');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function category_invoice()
    {
        return $this->belongsTo('App\CategoryInvoice');
    }

    public function credit()
    {
        return $this->hasOne('App\SupplierCredit');
    }

    public function orderPurchase()
    {
        return $this->belongsTo(OrderPurchase::class, 'purchase_order', 'code');
    }

    protected $dates = ['deleted_at', 'date_entry'];
}
