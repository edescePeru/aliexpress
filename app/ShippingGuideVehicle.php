<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingGuideVehicle extends Model
{
    protected $table = 'shipping_guide_vehicles';

    protected $fillable = [
        'shipping_guide_id',
        'is_primary',
        'plate_number',
        'tuc',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function guide()
    {
        return $this->belongsTo(ShippingGuide::class, 'shipping_guide_id');
    }
}
