<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MaterialPresentation extends Model
{
    protected $fillable = [
        'label',
        'material_id',
        'quantity',
        'price',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'material_presentation_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function consumibles()
    {
        return $this->hasMany(EquipmentConsumable::class);
    }
}
