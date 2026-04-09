<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected $fillable=[
        'name',
        'currency',
        'is_default',
        'is_active'
    ];

}
