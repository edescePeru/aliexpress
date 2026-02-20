<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeightUnit extends Model
{
    protected $table = 'weight_units';

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];
}
