<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitMeasure extends Model
{
    use SoftDeletes,
        BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    protected $dates = [
        'deleted_at',
    ];
}