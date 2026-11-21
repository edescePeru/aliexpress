<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditPay extends Model
{
    use \App\Traits\ScopedSupplierCreditPayment;

    public $fillable = [
        'supplier_credit_id',
        'amount',
        'date_pay',
        'image',
    ];

    protected $dates = ['date_pay'];
    protected $casts = ['amount' => 'decimal:2'];

    public function supplier_credit()
    {
        return $this->belongsTo('App\SupplierCredit');
    }
}
