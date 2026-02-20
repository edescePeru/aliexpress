<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id',
        'material_id',
        'description',
        'material_presentation_id',
        'valor_unitario',
        'price',
        'quantity',
        'packs',
        'units_per_pack',
        'percentage_tax',
        'total',
        'discount',
        'unit_cost',
        'total_cost',
    ];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function materialPresentation()
    {
        return $this->belongsTo(MaterialPresentation::class, 'material_presentation_id');
    }
}
