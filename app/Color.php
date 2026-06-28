<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable=[
        'name',
        'code',
        'short_name'
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class, 'color_id');
    }
}
