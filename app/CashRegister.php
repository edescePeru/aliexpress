<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $fillable = [
        'opening_balance',
        'closing_balance',
        'current_balance',
        'total_sales',
        'total_incomes',
        'total_expenses',
        'opening_time',
        'closing_time',
        'type',
        'status',
        'user_id',
        'cash_box_id'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function movements()
    {
        return $this->hasMany('App\CashMovement');
    }

    public function cashBox()
    {
        return $this->belongsTo(CashBox::class);
    }
}
