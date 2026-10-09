<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',

        'cash_box_id',
        'user_id',

        'opening_balance',
        'closing_balance',
        'current_balance',

        'total_sales',
        'total_incomes',
        'total_expenses',

        'opening_time',
        'closing_time',

        /*
         * Legacy temporal.
         */
        'type',

        'status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'opening_time',
        'closing_time',
    ];

    protected $casts = [
        'tenant_id' =>
            'integer',

        'company_id' =>
            'integer',

        'branch_id' =>
            'integer',

        'cash_box_id' =>
            'integer',

        'user_id' =>
            'integer',

        'opening_balance' =>
            'decimal:2',

        'closing_balance' =>
            'decimal:2',

        'current_balance' =>
            'decimal:2',

        'total_sales' =>
            'decimal:2',

        'total_incomes' =>
            'decimal:2',

        'total_expenses' =>
            'decimal:2',

        'status' =>
            'boolean',
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
     * RELACIONES
     * ============================================================
     */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function cashBox()
    {
        return $this->belongsTo(
            CashBox::class
        );
    }


    public function movements()
    {
        return $this->hasMany(
            CashMovement::class
        );
    }

    public function settlement()
    {
        return $this->hasOne(
            CashSettlement::class
        );
    }
}