<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'name',
        'comment',
        'area_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];


    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }


    public function branch()
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id'
        );
    }


    public function area()
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }


    public function shelves()
    {
        return $this->hasMany(
            Shelf::class,
            'warehouse_id'
        );
    }


    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'warehouse_id'
        );
    }


    public function inventoryLevels()
    {
        return $this->hasMany(
            InventoryLevel::class,
            'warehouse_id'
        );
    }
}