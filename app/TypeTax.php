<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TypeTax extends Model
{
    protected $fillable = [
        'code',
        'name',
        'tax',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'tax' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}