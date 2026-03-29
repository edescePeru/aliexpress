<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'comment',
        'area_id',
        'is_default'
    ];

    public function area()
    {
        return $this->belongsTo('App\Area');
    }

    public function shelves()
    {
        return $this->hasMany('App\Shelf');
    }

    public function locations()
    {
        return $this->hasMany('App\Location');
    }
}
