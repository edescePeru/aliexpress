<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceListMaterial extends Model
{
    protected $fillable = [
        'price_list_id',
        'material_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:4',
    ];

    public function priceList()
    {
        return $this->belongsTo(
            PriceList::class,
            'price_list_id'
        );
    }

    public function material()
    {
        return $this->belongsTo(
            Material::class,
            'material_id'
        );
    }
}
