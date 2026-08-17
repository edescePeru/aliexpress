<?php

namespace App;

use App\Traits\BelongsToTenant;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    use SoftDeletes,
        CascadeSoftDeletes,
        BelongsToTenant;

    protected $cascadeDeletes = [
        'materialTypes',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category_id',
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

    public function category()
    {
        return $this->belongsTo(
            'App\Category'
        );
    }

    public function materialTypes()
    {
        return $this->hasMany(
            'App\MaterialType'
        );
    }
}