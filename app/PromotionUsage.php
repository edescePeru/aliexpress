<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionUsage extends Model
{
    use \App\Traits\RetiredLegacyPromotion;

    protected $fillable = [
        'promotion_limit_id',
        'user_id',
        'used_quantity',
        'equipment_id',
        'equipment_consumable_id',
        'quote_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(PromotionLimit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function equipmentConsumable()
    {
        return $this->belongsTo(EquipmentConsumable::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
