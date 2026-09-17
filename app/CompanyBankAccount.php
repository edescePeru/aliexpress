<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CompanyBankAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'bank_id',

        'title',
        'account_number',
        'cci',
        'currency',
        'account_holder',
        'image',

        'position',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
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