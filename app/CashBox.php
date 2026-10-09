<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

// Usa aquí el namespace REAL de tu Trait.
// Ejemplo:
// use App\Traits\BelongsToTenant;

class CashBox extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',

        'name',
        'type',
        'uses_subtypes',
        'is_active',
        'position',

        'bank_name',
        'account_label',
        'account_number_mask',
        'currency',

        'current_balance',

        'company_bank_account_id',
    ];

    protected $casts = [
        'tenant_id' =>
            'integer',

        'company_id' =>
            'integer',

        'branch_id' =>
            'integer',

        'uses_subtypes' =>
            'boolean',

        'is_active' =>
            'boolean',

        'position' =>
            'integer',

        'current_balance' =>
            'decimal:2',

        'company_bank_account_id' =>
            'integer',
    ];


    /*
     * ============================================================
     * CONTEXTO
     * ============================================================
     */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }


    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }


    /*
     * ============================================================
     * CAJA
     * ============================================================
     */

    public function subtypes()
    {
        return $this->hasMany(
            CashBoxSubtype::class
        );
    }


    public function registers()
    {
        return $this->hasMany(
            CashRegister::class
        );
    }

    public function settlements()
    {
        return $this->hasMany(
            CashSettlement::class
        );
    }

    public function bankAccount()
    {
        return $this->belongsTo(
            CompanyBankAccount::class,
            'company_bank_account_id'
        );
    }
}