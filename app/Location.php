<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use BelongsToTenant;

    protected $appends = [
        'full_location',
    ];

    protected $fillable = [
        'tenant_id',
        'company_id',
        'area_id',
        'warehouse_id',
        'shelf_id',
        'level_id',
        'container_id',
        'position_id',
        'description',
        'default',
    ];

    protected $casts = [
        'default' => 'boolean',
    ];


    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }


    public function area()
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }


    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id'
        );
    }


    public function shelf()
    {
        return $this->belongsTo(
            Shelf::class,
            'shelf_id'
        );
    }


    public function level()
    {
        return $this->belongsTo(
            Level::class,
            'level_id'
        );
    }


    public function container()
    {
        return $this->belongsTo(
            Container::class,
            'container_id'
        );
    }


    public function position()
    {
        return $this->belongsTo(
            Position::class,
            'position_id'
        );
    }


    public function items()
    {
        return $this->hasMany(
            Item::class,
            'location_id'
        );
    }


    public function inventoryLevels()
    {
        return $this->hasMany(
            InventoryLevel::class,
            'location_id'
        );
    }


    public function getFullLocationAttribute()
    {
        $area =
            is_null($this->area)
                ? ''
                : ' ' . $this->area->name;

        $warehouse =
            is_null($this->warehouse)
                ? ''
                : ' ' . $this->warehouse->name;

        $shelf =
            is_null($this->shelf)
                ? ''
                : ' ' . $this->shelf->name;

        $level =
            is_null($this->level)
                ? ''
                : ' ' . $this->level->name;

        $container =
            is_null($this->container)
                ? ''
                : ' ' . $this->container->name;

        $position =
            is_null($this->position)
                ? ''
                : ' ' . $this->position->name;

        return
            'AR:' . $area .
            '|AL:' . $warehouse .
            '|E:' . $shelf .
            '|N:' . $level .
            '|C:' . $container .
            '|P:' . $position;
    }
}