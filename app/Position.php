<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
        'container_id',
        'status',
    ];

    public function container()
    {
        return $this->belongsTo(
            Container::class,
            'container_id'
        );
    }

    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'position_id'
        );
    }
}