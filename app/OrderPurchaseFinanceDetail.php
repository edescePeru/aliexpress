<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPurchaseFinanceDetail extends Model
{
    use SoftDeletes, \App\Traits\ScopedFinancePurchaseCompany;

    public $fillable = [
        'order_purchase_finance_id',
        'material',
        'unit',
        'quantity',
        'price',
        'igv',
        'total_detail'
    ];

    protected $dates = ['deleted_at'];


    public function order_purchase()
    {
        return $this->belongsTo('App\OrderPurchaseFinance')->withTrashed();
    }
}
