<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SunatShippingIndicator extends Model
{
    protected $table = 'sunat_shipping_indicators';

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];
}
