<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SupplierAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'number_account',
        'currency',
        'bank_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    public function bank()
    {
        return $this->belongsTo(
            Bank::class,
            'bank_id'
        );
    }
}