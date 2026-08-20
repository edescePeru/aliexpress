<?php

namespace App;

use App\Traits\BelongsToTenant;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialType extends Model
{
    use SoftDeletes,
        CascadeSoftDeletes,
        BelongsToTenant;

    protected $cascadeDeletes = [
        'subtypes',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'subcategory_id',
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

    public function subcategory()
    {
        return $this->belongsTo(
            'App\Subcategory'
        );
    }

    public function subtypes()
    {
        return $this->hasMany(
            'App\Subtype'
        );
    }
}