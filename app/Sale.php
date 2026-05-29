<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'date_sale',
        'serie',
        'worker_id',
        'caja',
        'currency',
        'op_exonerada',
        'op_inafecta',
        'op_gravada',
        'igv',
        'total_descuentos',
        'importe_total',
        'vuelto',
        'tipo_pago_id',
        'state_annulled',

        'quote_id',

        'numero',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'type_document',
        'pagos_parciales_venta',
        'sunat_ticket',
        'sunat_status',
        'sunat_message',
        'fecha_emision',
        'nombre_cliente',
        'tipo_documento_cliente', // '1' = DNI, '6' = RUC
        'numero_documento_cliente',
        'direccion_cliente',
        'email_cliente',
        'serie_sunat'
    ];

    protected $dates = ['date_sale'];

    public function worker()
    {
        return $this->belongsTo('App\Worker');
    }

    public function quote()
    {
        return $this->belongsTo('App\Quote');
    }

    public function tipoPago()
    {
        return $this->belongsTo('App\TipoPago');
    }

    public function details()
    {
        return $this->hasMany('App\SaleDetail');
    }

    public function getFormattedSaleDateAttribute()
    {
        return Carbon::parse($this->date_sale)->isoFormat('DD/MM/YYYY [a las] h:mm A');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class, 'sale_id');
    }

    public function partialPayments()
    {
        return $this->hasMany(SalePartialPayment::class);
    }

    public function getDataTotalsAttribute()
    {
        $sale = Sale::find($this->id);

        $items = $sale->details->map(function ($item) {
            $valor_unitario = round($item->price / 1.18, 6);
            $subtotal = $valor_unitario * $item->quantity; // 20.25
            $igv = ($item->price * $item->quantity) - $subtotal; // 23.90 - 20.25 = 3.65

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => $item->material_id,
                "descripcion" => $item->material->full_name,
                "cantidad" => $item->quantity,
                "valor_unitario" => round($valor_unitario, 6),
                "precio_unitario" => round($item->price, 6),
                "subtotal" => round($subtotal, 6), // 34.66
                "tipo_de_igv" => "1", // gravado
                "igv" => round($igv, 2), // 6.24
                "total" => round($item->price*$item->quantity, 6) // 40.90
            ];
        })->toArray();


        $total_gravada = array_sum(array_column($items, 'subtotal'));

        return [
                "total_gravada" => number_format($total_gravada - $this->total_descuentos, 2, '.', ''),
                "total_igv" => number_format(($total_gravada - $this->total_descuentos) * 0.18, 2, '.', ''),
                "total" => number_format(($total_gravada - $this->total_descuentos) * 1.18, 2, '.', ''),
                "total_a_pagar" => number_format(($total_gravada - $this->total_descuentos) * 1.18, 2, '.', ''),
            ] + ($this->total_descuentos > 0 ? [
                "descuento_global" => number_format($this->total_descuentos, 2, '.', ''),
                "total_descuento" => number_format($this->total_descuentos, 2, '.', '')
            ] : []) + [
                "items" => $items,
            ];

    }
}
