<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Exampler extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
        'brand_id',
    ];

    public function materials()
    {
        return $this->hasMany(
            'App\Material'
        );
    }

    public function brand()
    {
        return $this->belongsTo(
            'App\Brand'
        );
    }
}