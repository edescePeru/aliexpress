<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionLimit extends Model
{
    use \App\Traits\RetiredLegacyPromotion;

    protected $fillable = [
        'material_id',
        'limit_quantity',
        'applies_to',
        'price_type',
        'percentage',
        'promo_price',
        'original_price',
        'start_date',
        'end_date',
    ];

    protected $dates = ['start_date', 'end_date'];

    // Relaciones
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
