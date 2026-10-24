<?php

namespace App;

use App\Traits\ScopedPurchaseCompany;
use Illuminate\Database\Eloquent\Model;

class MaterialOrder extends Model
{
    use ScopedPurchaseCompany;
    protected $fillable = [
        'order_purchase_detail_id', 'material_id', 'quantity_request', 'quantity_entered', 'stock_item_id'
    ];

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function detail()
    {
        return $this->belongsTo('App\OrderPurchaseDetail');
    }
}
