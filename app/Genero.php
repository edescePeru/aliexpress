<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Genero extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'name',
        'description'
    ];

    public function materials()
    {
        return $this->hasMany('App\Material', 'genero_id');
    }

    protected $dates = ['deleted_at'];
}
