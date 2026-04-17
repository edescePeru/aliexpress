<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuoteStockLot extends Model
{
    protected $fillable = [
        'quote_id',
        'quote_detail_id',
        'stock_item_id',
        'stock_lot_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'unit_cost',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class, 'stock_lot_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
