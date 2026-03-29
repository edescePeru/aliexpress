<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $fillable=[
        'material_id', // El id del material
        'quality_id',  // es el talla_id
        'color_id',    // es el color_id
        'attribute_summary', // es la union de 40 / Blanco talla(short_name) y el color(name)
        'image',   // imagen de la variante
        'is_active'  // si es activo o no la variante
    ];

    public function stockItem()
    {
        return $this->hasOne(StockItem::class, 'variant_id');
    }

    public function talla()
    {
        return $this->belongsTo(Talla::class, 'quality_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
