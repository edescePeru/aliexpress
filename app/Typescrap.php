<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Typescrap extends Model
{
    use SoftDeletes,
        BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'length',
        'width',
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
}