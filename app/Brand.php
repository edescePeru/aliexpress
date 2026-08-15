<?php

namespace App;

use App\Traits\BelongsToTenant;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes,
        CascadeSoftDeletes,
        BelongsToTenant;

    protected $cascadeDeletes = [
        'examplers',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function materials()
    {
        return $this->hasMany(
            'App\Material'
        );
    }

    public function examplers()
    {
        return $this->hasMany(
            'App\Exampler'
        );
    }
}