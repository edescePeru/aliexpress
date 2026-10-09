<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CashSettlement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',

        'cash_box_id',
        'cash_register_id',
        'user_id',

        'expected_amount',
        'confirmed_amount',
        'deferred_amount',

        'counted_amount',
        'difference_amount',

        'observation',

        'settled_at',
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

        'cash_register_id' =>
            'integer',

        'user_id' =>
            'integer',

        'expected_amount' =>
            'decimal:2',

        'confirmed_amount' =>
            'decimal:2',

        'deferred_amount' =>
            'decimal:2',

        'counted_amount' =>
            'decimal:2',

        'difference_amount' =>
            'decimal:2',

        'settled_at' =>
            'datetime',
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


    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }


    /*
     * ============================================================
     * CAJA
     * ============================================================
     */

    public function cashBox()
    {
        return $this->belongsTo(
            CashBox::class
        );
    }


    public function cashRegister()
    {
        return $this->belongsTo(
            CashRegister::class
        );
    }


    /*
     * ============================================================
     * MOVIMIENTOS RENDIDOS EN ESTE ARQUEO
     * ============================================================
     */

    public function movements()
    {
        return $this->hasMany(
            CashMovement::class,
            'cash_settlement_id'
        );
    }
}