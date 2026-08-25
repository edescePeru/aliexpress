<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CompanyStockItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'stock_item_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}