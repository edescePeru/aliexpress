<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'tenant_id',
        'ruc',
        'business_name',
        'trade_name',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'company_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function companyStockItems()
    {
        return $this->hasMany(
            CompanyStockItem::class,
            'company_id'
        );
    }

    public function stockItems()
    {
        return $this->belongsToMany(
            StockItem::class,
            'company_stock_items',
            'company_id',
            'stock_item_id'
        )
            ->withPivot([
                'is_active',
            ])
            ->withTimestamps();
    }

    public function areas()
    {
        return $this->hasMany(
            Area::class,
            'company_id'
        );
    }

    public function warehouses()
    {
        return $this->hasMany(
            Warehouse::class,
            'company_id'
        );
    }

    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'company_id'
        );
    }
}
