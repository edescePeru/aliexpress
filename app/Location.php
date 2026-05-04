<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $appends = ['full_location'];

    protected $fillable = [
        'area_id',
        'warehouse_id',
        'shelf_id',
        'level_id',
        'container_id',
        'position_id',
        'description',
        'default'
    ];

    public function getFullLocationAttribute()
    {
        $area = ( is_null($this->area) ) ? '': ' '.$this->area->name;
        $warehouse = ( is_null($this->warehouse) ) ? '': ' '.$this->warehouse->name;
        $shelf = ( is_null($this->shelf) ) ? '': ' '.$this->shelf->name;
        $level = ( is_null($this->level) ) ? '': ' '.$this->level->name;
        $container = ( is_null($this->container) ) ? '': ' '.$this->container->name;
        $position = ( is_null($this->position) ) ? '': ' '.$this->position->name;

        return "AR:" . $area . "|AL:" . $warehouse ."|E:". $shelf ."|N:". $level ."|C:". $container ."|P:". $position;
    }

    public function area()
    {
        return $this->belongsTo('App\Area');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse');
    }

    public function shelf()
    {
        return $this->belongsTo('App\Shelf');
    }

    public function level()
    {
        return $this->belongsTo('App\Level');
    }

    public function container()
    {
        return $this->belongsTo('App\Container');
    }

    public function position()
    {
        return $this->belongsTo('App\Position');
    }

    public function items()
    {
        return $this->hasMany('App\Item');
    }
}
