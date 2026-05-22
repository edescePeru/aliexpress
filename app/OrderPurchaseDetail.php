<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPurchaseDetail extends Model
{
    use SoftDeletes;

    public $fillable = [
        'order_purchase_id',
        'material_id',
        'stock_item_id',
        'quantity',
        'price',
        'igv',
        'total_detail'
    ];

    protected $dates = ['deleted_at'];

    public function order_purchase()
    {
        return $this->belongsTo('App\OrderPurchase');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }
}
