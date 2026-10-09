<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CashBoxSubtype extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',

        'cash_box_id',

        'code',
        'name',

        'is_active',
        'position',

        'is_deferred',
        'requires_commission',
    ];

    protected $casts = [
        'tenant_id' =>
            'integer',

        'company_id' =>
            'integer',

        'cash_box_id' =>
            'integer',

        'is_active' =>
            'boolean',

        'position' =>
            'integer',

        'is_deferred' =>
            'boolean',

        'requires_commission' =>
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


    public function cashMovements()
    {
        return $this->hasMany(
            CashMovement::class,
            'cash_box_subtype_id'
        );
    }


    /*
     * ============================================================
     * SCOPES
     * ============================================================
     *
     * Subtipos aplicables a una CashBox:
     *
     * - globales de la Company: cash_box_id NULL
     * - específicos de esa CashBox
     */

    public function scopeAvailableForCashBox(
        $query,
        $cashBoxId
    ) {
        return $query->where(
            function ($q) use ($cashBoxId) {

                $q->whereNull(
                    'cash_box_id'
                )
                    ->orWhere(
                        'cash_box_id',
                        $cashBoxId
                    );
            }
        );
    }
}
