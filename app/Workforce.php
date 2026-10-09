<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Workforce extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'description',
        'unit_measure_id',
        'unit_price',
        'billable',
    ];

    protected $casts = [
        'unit_price' =>
            'float',

        'billable' =>
            'boolean',
    ];

    public function unitMeasure()
    {
        return $this->belongsTo(
            UnitMeasure::class
        );
    }
}