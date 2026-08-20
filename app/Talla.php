<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Talla extends Model
{
    use SoftDeletes,
        BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'short_name',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function variants()
    {
        return $this->hasMany(
            Variant::class,
            'talla_id'
        );
    }
}