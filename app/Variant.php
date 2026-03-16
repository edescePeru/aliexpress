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
}
