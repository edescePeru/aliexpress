<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'balance_effect', 'regularization_source_id',
        'tenant_id',
        'company_id',
        'branch_id',

        'cash_box_id',
        'cash_register_id',
        'cash_settlement_id',
        'user_id',

        'type',

        'amount',

        'description',

        'regularize',

        'amount_regularize',

        'commission',

        'sale_id',

        'entry_id',

        'cash_box_subtype_id',

        'observation',

        'cash_movement_origin_id',

        'cash_movement_regularize_id',

        'arqueo',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'balance_effect' => 'boolean', 'regularization_source_id' => 'integer',
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

        'amount' =>
            'decimal:2',

        'amount_regularize' =>
            'decimal:2',

        'commission' =>
            'decimal:2',

        'regularize' =>
            'boolean',

        'arqueo' =>
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


    public function cashBoxSubtype()
    {
        return $this->belongsTo(
            CashBoxSubtype::class
        );
    }


    /*
     * ============================================================
     * OPERACIÓN ORIGEN
     * ============================================================
     */

    public function sale()
    {
        return $this->belongsTo(
            Sale::class
        );
    }


    public function entry()
    {
        return $this->belongsTo(
            Entry::class,
            'entry_id'
        );
    }


    /*
     * ============================================================
     * SELF REFERENCES
     * ============================================================
     */

    public function origin()
    {
        return $this->belongsTo(
            self::class,
            'cash_movement_origin_id'
        );
    }


    public function regularizedBy()
    {
        return $this->belongsTo(
            self::class,
            'cash_movement_regularize_id'
        );
    }


    public function regularizedChildren()
    {
        return $this->hasMany(
            self::class,
            'cash_movement_origin_id'
        );
    }


    /*
     * ============================================================
     * HELPERS LEGACY
     * ============================================================
     */

    public function impactAmount()
    {
        if ($this->balance_effect === false || !$this->regularize) {
            return 0.0;
        }

        if (
        !is_null(
            $this->amount_regularize
        )
        ) {
            return (float)
            $this->amount_regularize;
        }

        return (float)
        $this->amount;
    }


    public function computeCommission()
    {
        if (
        is_null(
            $this->amount_regularize
        )
        ) {

            $this->commission =
                null;

            return;
        }

        $this->commission =
            (float)
            $this->amount
            -
            (float)
            $this->amount_regularize;
    }

    public function settlement()
    {
        return $this->belongsTo(
            CashSettlement::class,
            'cash_settlement_id'
        );
    }
    // NULL retains historical behavior. Informational originals never post cash a second time.
    public function scopeAffectingBalance($query)
    {
        $column=$this->qualifyColumn('balance_effect');
        return $query->where($this->qualifyColumn('regularize'),true)->where(function($q)use($column){
            $q->whereNull($column)->orWhere($column,true);
        });
    }

    public function regularizationSource()
    {
        return $this->belongsTo(self::class,'regularization_source_id');
    }

}
