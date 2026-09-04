<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockLot extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',

        'stock_item_id',
        'location_id',
        'warehouse_id',
        'detail_entry_id',

        'lot_code',
        'expiration_date',

        'qty_on_hand',
        'qty_reserved',

        'unit_cost',
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


    public function detailEntry()
    {
        return $this->belongsTo(
            DetailEntry::class,
            'detail_entry_id'
        );
    }


    public function quoteReservations()
    {
        return $this->hasMany(
            QuoteStockLot::class,
            'stock_lot_id'
        );
    }
}