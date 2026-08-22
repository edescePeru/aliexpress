<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'material_id',

        /*
         * quality_id queda temporalmente
         * por compatibilidad con código legacy.
         * No debe utilizarse en nuevas variantes.
         */
        'quality_id',

        'talla_id',
        'color_id',
        'attribute_summary',
        'image',
        'is_active',
    ];

    public function stockItem()
    {
        return $this->hasOne(
            StockItem::class,
            'variant_id'
        );
    }

    public function talla()
    {
        return $this->belongsTo(
            Talla::class,
            'talla_id'
        );
    }

    public function color()
    {
        return $this->belongsTo(
            Color::class,
            'color_id'
        );
    }

    public function material()
    {
        return $this->belongsTo(
            Material::class,
            'material_id'
        );
    }

    public function stockItems()
    {
        return $this->hasMany(
            StockItem::class,
            'variant_id'
        );
    }
}