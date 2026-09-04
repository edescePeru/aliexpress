<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'business_name',
        'RUC',
        'code',
        'address',
        'phone',
        'email',
        'special'
    ];

    protected $dates = ['deleted_at'];

    // TODO: Agregar una relacion entre Supplier y SupplierAccount De uno a muchos

    public function accounts()
    {
        return $this->hasMany('App\SupplierAccount');
    }

    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }
}
