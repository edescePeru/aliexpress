<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashBoxSubtype extends Model
{
    protected $fillable = [
        'cash_box_id',
        'code',
        'name',
        'is_active',
        'position',
        'is_deferred',
        'requires_commission'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'is_deferred' => 'boolean',
        'requires_commission' => 'boolean',
    ];

    public function cashBox()
    {
        return $this->belongsTo(CashBox::class);
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class, 'cash_box_subtype_id');
    }


}
