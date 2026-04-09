<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    protected $fillable=[
        'price_list_id',
        'stock_item_id',
        'price'
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }
}
