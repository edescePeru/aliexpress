<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'detail_entry_id',
        'stock_item_id',
        'stock_lot_id',
        'material_id',
        'code',
        'length',
        'width',
        'weight',
        'price',
        'unit_cost',
        'percentage',
        'typescrap_id',
        'warehouse_id',
        'location_id',
        'state',
        'state_item',
        'type',
        'usage'
    ];


    public function detailEntry()
    {
        return $this->belongsTo('App\DetailEntry');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function typescrap()
    {
        return $this->belongsTo('App\Typescrap', 'typescrap_id');
    }

    public function location()
    {
        return $this->belongsTo('App\Location');
    }

    public function outputDetail()
    {
        return $this->hasMany('App\OutputDetail', 'item_id','id');
    }

    /**
     * Moneda base de inventario (usd/pen) desde DataGeneral.
     */
    protected function getBaseCurrency(): string
    {
        $record = DataGeneral::where('name', 'type_current')->first();
        // Normalizamos a mayúsculas para comparar fácil
        return strtoupper($record->valueText ?? 'PEN'); // PEN por defecto
    }

    /**
     * Costo del ítem en la moneda base de inventario.
     *
     * - Usa el precio original del ítem ($this->price).
     * - Toma la moneda de la Entry (currency_invoice).
     * - Convierte usando currency_compra / currency_venta según la base.
     */
    public function getUnitCostBaseAttribute()
    {
        $detailEntry = $this->detailEntry;

        if (!$detailEntry || !$detailEntry->entry) {
            return null;
        }

        $entry = $detailEntry->entry;
        $price = (float) $this->price;

        // Moneda base desde DataGeneral (USD o PEN)
        $baseCurrency = $this->getBaseCurrency(); // 'USD' o 'PEN'

        // Moneda de la factura de compra
        $invoiceCurrency = strtoupper($entry->currency_invoice ?? $baseCurrency);

        // Caso 1: Ya está en la moneda base → no convertimos
        if ($invoiceCurrency === $baseCurrency) {
            return $price;
        }

        // Tomamos tipos de cambio (ajusta según tu semántica real)
        $tcCompra = (float) $entry->currency_compra; // S/ por 1 USD
        $tcVenta  = (float) $entry->currency_venta;  // S/ por 1 USD

        // ⚠️ IMPORTANTE:
        // Aquí asumo:
        // - currency_compra: para convertir de USD → PEN (te "compras" los dólares)
        // - currency_venta:  para convertir de PEN → USD (te "venden" dólares)
        // Ajusta si en tu sistema los usas al revés.

        // ╔══════════════════════════════════╗
        // ║   BASE = USD, FACTURA = PEN      ║
        // ╚══════════════════════════════════╝
        if ($baseCurrency === 'USD' && $invoiceCurrency === 'PEN') {
            // price está en soles, queremos USD → dividimos entre TC
            $tc = $tcVenta > 0 ? $tcVenta : $tcCompra; // fallback
            if ($tc > 0) {
                return $price / $tc;
            }
            return $price; // fallback sin TC
        }

        // ╔══════════════════════════════════╗
        // ║   BASE = PEN, FACTURA = USD      ║
        // ╚══════════════════════════════════╝
        if ($baseCurrency === 'PEN' && $invoiceCurrency === 'USD') {
            // price está en dólares, queremos soles → multiplicamos por TC
            $tc = $tcCompra > 0 ? $tcCompra : $tcVenta; // fallback
            if ($tc > 0) {
                return $price * $tc;
            }
            return $price; // fallback sin TC
        }

        // Si en el futuro soportas más monedas o códigos raros
        return $price;
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class, 'stock_lot_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

}
