<?php

namespace App;

use App\Traits\BelongsToTenant;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, CascadeSoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'business_name',
        'RUC',
        'code',
        'address',
        'location',
        'special',
    ];

    protected $cascadeDeletes = [
        'contactNames',
    ];

    protected $dates = [
        'deleted_at',
    ];


    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }


    public function quotes()
    {
        return $this->hasMany(
            Quote::class,
            'customer_id'
        );
    }


    public function contactNames()
    {
        return $this->hasMany(
            ContactName::class,
            'customer_id'
        );
    }


    public function guides()
    {
        return $this->hasMany(
            ReferralGuide::class,
            'customer_id'
        );
    }
}