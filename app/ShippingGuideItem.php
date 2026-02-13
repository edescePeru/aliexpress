<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingGuideItem extends Model
{
    protected $table = 'shipping_guide_items';

    protected $fillable = [
        'shipping_guide_id',
        'line',
        'product_id',
        'codigo',
        'descripcion',
        'detalle_adicional',
        'cantidad',
        'unidad_medida',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
    ];

    public function guide()
    {
        return $this->belongsTo(ShippingGuide::class, 'shipping_guide_id');
    }
}
