<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OutputDetail extends Model
{
    protected $fillable = [
        'output_id',
        'item_id',
        'length',
        'width',
        'price',
        'percentage',
        'material_id',
        'equipment_id',
        'quote_id',
        'custom',
        'activo',
        'sale_detail_id',
        'unit_cost',
        'total_cost',
    ];

    public function output()
    {
        return $this->belongsTo('App\Output');
    }

    public function items()
    {
        return $this->belongsTo('App\Item', 'item_id', 'id');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function equipment()
    {
        return $this->belongsTo('App\Equipment');
    }

    public function quote()
    {
        return $this->belongsTo('App\Quote');
    }

    /**
     * Cantidad equivalente para el Kardex.
     * - Material sin retazo (typescrap_id null): siempre 1 unidad por fila.
     * - Material con retazo (typescrap_id != null): usamos percentage (1, 0.5, 0.3, etc.).
     * - Material sin control de inventario: 0.
     */
    /*public function getKardexQuantityAttribute()
    {
        $material = $this->material;

        // Si no hay material o el material no se controla en inventario
        if (!$material || !$material->inventory) {
            return 0;
        }

        // MATERIAL NORMAL → 1 unidad por fila
        if (is_null($material->typescrap_id)) {
            return 1.0;
        }

        // MATERIAL CON RETAZO → usamos 'percentage' como fracción
        if (!is_null($this->percentage)) {
            // En tu sistema: 1 = unidad completa, 0.5 = medio, etc.
            return (float) $this->percentage;
        }

        // Fallback defensivo
        return 1.0;
    }*/

    public function getKardexQuantityAttribute()
    {
        return (float) ($this->percentage ?? 1);
    }
}
