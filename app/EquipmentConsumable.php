<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EquipmentConsumable extends Model
{
    protected $fillable = [
        'equipment_id',
        'material_id',
        'stock_item_id',
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
        'type_tax_id',
        'tax_rate',
    ];

    protected $casts = [
        'material_presentation_id' => 'integer',
        'packs' => 'integer',
        'units_per_pack' => 'integer',
        'tax_rate' => 'decimal:4',
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

    public function stockItem()
    {
        return $this->belongsTo('App\StockItem', 'stock_item_id');
    }

    public function quoteStockLots()
    {
        return $this->hasMany(
            QuoteStockLot::class,
            'quote_detail_id',
            'id'
        );
    }

    public function typeTax()
    {
        return $this->belongsTo(
            TypeTax::class,
            'type_tax_id'
        );
    }
}
