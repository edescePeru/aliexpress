<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactName extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'customer_id',
        'phone',
        'email',
        'area',
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


    public function customer()
    {
        return $this->belongsTo(
            Customer::class,
            'customer_id'
        )->withTrashed();
    }


    public function quotes()
    {
        return $this->hasMany(
            Quote::class,
            'contact_id'
        );
    }
}