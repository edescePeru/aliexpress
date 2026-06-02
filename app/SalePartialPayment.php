<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalePartialPayment extends Model
{
    protected $fillable = [
        'sale_id',
        'cash_movement_id',
        'user_id',
        'payment_date',
        'amount',
        'state',
    ];

    protected $dates = [
        'payment_date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashMovement()
    {
        return $this->belongsTo(CashMovement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
