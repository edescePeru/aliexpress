<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable=[
        'material_id', // id del material
        'variant_id',  // id de la variante
        'sku',         // el sku de cada variante
        'barcode',     // el barcode de cada variante
        'display_name',  // El nombre del material (full_name) y agregamos el shortname de la talla y el name del Color separados con un guion
        'unit_measure_id', // unit_measure_id del material
        'tracks_inventory', // afecto_inventario como envia on o off el on significa true y el off false
        'is_active' // El is_active de cada variante
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function unitMeasure()
    {
        return $this->belongsTo(UnitMeasure::class, 'unit_measure_id');
    }

    public function inventoryLevels()
    {
        return $this->hasMany(InventoryLevel::class, 'stock_item_id');
    }

    public function priceListItems()
    {
        return $this->hasMany(PriceListItem::class, 'stock_item_id');
    }
}
