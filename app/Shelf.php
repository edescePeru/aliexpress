<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'comment',
        'warehouse_id',
    ];

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id'
        );
    }

    public function levels()
    {
        return $this->hasMany(
            Level::class,
            'shelf_id'
        );
    }

    public function locations()
    {
        return $this->hasMany(
            Location::class,
            'shelf_id'
        );
    }
}