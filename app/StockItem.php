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

    public function getStockCurrentAttribute()
    {
        if (!$this->relationLoaded('inventoryLevels')) {
            return (float) $this->inventoryLevels()->sum('qty_on_hand');
        }

        return (float) $this->inventoryLevels->sum('qty_on_hand');
    }

    public function getStockReservedAttribute()
    {
        if (!$this->relationLoaded('inventoryLevels')) {
            return (float) $this->inventoryLevels()->sum('qty_reserved');
        }

        return (float) $this->inventoryLevels->sum('qty_reserved');
    }

    public function getStockAvailableAttribute()
    {
        return (float) $this->stock_current - (float) $this->stock_reserved;
    }

    public function getListPriceAttribute()
    {
        if ($this->relationLoaded('priceListItems')) {
            $priceListItem = $this->priceListItems->first(function ($item) {
                return $item->priceList && $item->priceList->is_default && $item->priceList->is_active;
            });

            return $priceListItem ? (float) $priceListItem->price : 0;
        }

        $defaultPriceList = PriceList::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$defaultPriceList) {
            return 0;
        }

        $priceListItem = $this->priceListItems()
            ->where('price_list_id', $defaultPriceList->id)
            ->first();

        return $priceListItem ? (float) $priceListItem->price : 0;
    }

    public function getUiColorAttribute()
    {
        if ($this->material && (int) $this->material->enable_status === 0) {
            return 'purple';
        }

        if ((float) $this->stock_available <= 0) {
            return 'red';
        }

        if ((float) $this->list_price > 0) {
            return 'blue';
        }

        return '';
    }

    public function getDisplayImageAttribute()
    {
        if ($this->variant && !empty($this->variant->image)) {
            return $this->variant->image;
        }

        return optional($this->material)->image;
    }

    public function getDisplayImageUrlAttribute()
    {
        // 1. Si tiene variante con imagen
        if ($this->variant && !empty($this->variant->image)) {
            return asset('images/material/variants/' . $this->variant->image);
        }

        // 2. Si no, usar imagen del material padre
        if ($this->material && !empty($this->material->image)) {
            return asset('images/material/' . $this->material->image);
        }

        // 3. Imagen por defecto
        return asset('images/material/no_image.png');
    }

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

    public function quoteStockLots()
    {
        return $this->hasMany(QuoteStockLot::class, 'stock_item_id');
    }
}
