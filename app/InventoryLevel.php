<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLevel extends Model
{
    protected $fillable=[
        'stock_item_id',
        'location_id',
        'warehouse_id',
        'qty_on_hand',
        'qty_reserved',
        'min_alert',
        'max_alert',
        'average_cost',
        'last_cost'
    ];

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // o si usas location_id
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
