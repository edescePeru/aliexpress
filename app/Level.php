<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
        'shelf_id',
    ];

    public function shelf()
    {
        return $this->belongsTo(
            Shelf::class,
            'shelf_id'
        );
    }

    public function containers()
    {
        return $this->hasMany(
            Container::class,
            'level_id'
        );
    }

    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'level_id'
        );
    }
}