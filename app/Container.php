<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
        'level_id',
    ];

    public function level()
    {
        return $this->belongsTo(
            Level::class,
            'level_id'
        );
    }

    public function positions()
    {
        return $this->hasMany(
            Position::class,
            'container_id'
        );
    }

    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'container_id'
        );
    }
}