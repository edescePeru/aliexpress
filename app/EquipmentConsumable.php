<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EquipmentConsumable extends Model
{
    protected $fillable = [
        'equipment_id',
        'material_id',
        'quantity',
        'price',
        'total',
        'valor_unitario',
        'availability',
        'state',
        'discount',
        'type_promo',
        'material_presentation_id',
        'packs',
        'units_per_pack',
    ];

    protected $casts = [
        'material_presentation_id' => 'integer',
        'packs' => 'integer',
        'units_per_pack' => 'integer',
    ];

    public function equipment(){
        return $this->belongsTo('App\Equipment');
    }

    public function material(){
        return $this->belongsTo('App\Material');
    }

    public function presentation(){
        return $this->belongsTo(MaterialPresentation::class, 'material_presentation_id');
    }
}
