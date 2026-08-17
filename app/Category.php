<?php

namespace App;

use App\Traits\BelongsToTenant;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes,
        CascadeSoftDeletes,
        BelongsToTenant;

    protected $cascadeDeletes = [
        'subcategories',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
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

    public function subcategories()
    {
        return $this->hasMany(
            'App\Subcategory'
        );
    }
}