<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashBox extends Model
{
    protected $fillable = [
        'name',
        'type',
        'uses_subtypes',
        'is_active',
        'position',
        'bank_name',
        'account_label',
        'account_number_mask',
        'currency',
    ];

    protected $casts = [
        'uses_subtypes' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function subtypes()
    {
        return $this->hasMany(CashBoxSubtype::class);
    }

    public function registers()
    {
        return $this->hasMany(CashRegister::class);
    }
}
