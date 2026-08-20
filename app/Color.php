<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'short_name',
    ];

    public function variants()
    {
        return $this->hasMany(
            Variant::class,
            'color_id'
        );
    }
}