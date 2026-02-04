<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'cash_register_id',
        'type',
        'amount',
        'description',
        'regularize',
        'amount_regularize',      // monto real abonado
        'commission',             // amount - amount_regularize
        'sale_id',
        'cash_box_subtype_id',
        'observation',
        'cash_movement_origin_id',       // origen (ej: movimiento del cajero)
        'cash_movement_regularize_id',   // el movimiento central que lo vinculó/regularizó
    ];

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_regularize' => 'decimal:2',
        'commission' => 'decimal:2',
        'regularize' => 'boolean',
    ];

    public function cashRegister()
    {
        return $this->belongsTo('App\CashRegister');
    }

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function cashBoxSubtype()
    {
        return $this->belongsTo(CashBoxSubtype::class);
    }

    // Self references (auditoría)
    public function origin()
    {
        return $this->belongsTo(self::class, 'cash_movement_origin_id');
    }

    public function regularizedBy()
    {
        return $this->belongsTo(self::class, 'cash_movement_regularize_id');
    }

    public function regularizedChildren()
    {
        return $this->hasMany(self::class, 'cash_movement_origin_id');
    }

    // ======================
    // Helpers recomendados
    // ======================

    /**
     * Retorna el monto que debe impactar balance.
     * - Si regularize=1: usa amount_regularize si existe, caso contrario amount
     * - Si regularize=0: no impacta balance
     */
    public function impactAmount()
    {
        if (!$this->regularize) return 0.0;

        if (!is_null($this->amount_regularize)) return (float) $this->amount_regularize;

        return (float) $this->amount;
    }

    /**
     * Recalcula commission si hay amount_regularize.
     */
    public function computeCommission()
    {
        if (is_null($this->amount_regularize)) {
            $this->commission = null;
            return;
        }

        $this->commission = (float) $this->amount - (float) $this->amount_regularize;
    }
}
