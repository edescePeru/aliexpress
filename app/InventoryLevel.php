<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InventoryLevel extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'stock_item_id',
        'location_id',
        'warehouse_id',
        'qty_on_hand',
        'qty_reserved',
        'min_alert',
        'max_alert',
        'average_cost',
        'last_cost',
    ];

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public function stockItem()
    {
        return $this->belongsTo(
            StockItem::class,
            'stock_item_id'
        );
    }

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id'
        );
    }

    public function location()
    {
        return $this->belongsTo(
            Location::class,
            'location_id'
        );
    }
}