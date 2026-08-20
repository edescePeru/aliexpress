<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subtype extends Model
{
    use SoftDeletes,
        BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'material_type_id',
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

    public function materialType()
    {
        return $this->belongsTo(
            'App\MaterialType'
        );
    }
}