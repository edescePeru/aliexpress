<?php
namespace App\Traits;
use App\Services\Promotions\RetiredPromotionPolicy as Policy;
trait PreventsLegacyPromotionApplication
{
    public static function bootPreventsLegacyPromotionApplication()
    {
        static::creating(function ($line) { if (!Policy::neutral($line->type_promo)) { Policy::reject(); } });
        static::updating(function ($line) {
            if ($line->isDirty('type_promo') && (!Policy::neutral($line->getOriginal('type_promo')) || !Policy::neutral($line->type_promo))) { Policy::reject(); }
        });
    }
}
