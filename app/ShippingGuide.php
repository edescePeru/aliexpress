<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingGuide extends Model
{
    protected $table = 'shipping_guides';

    protected $fillable = [
        'guide_type',
        'tipo_de_comprobante',
        'serie',
        'numero',

        'customer_id',
        'customer_doc_type',
        'customer_doc_number',
        'customer_name',
        'customer_address',
        'customer_email',
        'customer_email_1',
        'customer_email_2',

        'fecha_emision',
        'fecha_inicio_traslado',

        'motivo_traslado_code',
        'tipo_transporte',

        'peso_bruto_total',
        'peso_bruto_um_code',
        'numero_bultos',
        'sunat_shipping_indicator_code',

        'partida_ubigeo',
        'partida_direccion',
        'partida_cod_establecimiento',

        'llegada_ubigeo',
        'llegada_direccion',
        'llegada_cod_establecimiento',

        'items_mode',
        'source_sale_id',
        'source_sale_ref',

        'observaciones',

        'status',
        'nubefact_accepted',
        'nubefact_enlace',
        'sunat_description',
        'sunat_note',
        'sunat_responsecode',
        'sunat_soap_error',
        'pdf_link',
        'xml_link',
        'cdr_link',
        'last_nubefact_payload',
        'last_nubefact_response',

        'transportista_doc_type',
        'transportista_doc_number',
        'transportista_name',
        'mtc_registration_number',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_inicio_traslado' => 'date',
        'peso_bruto_total' => 'decimal:3',
        'nubefact_accepted' => 'boolean',
        'last_nubefact_payload' => 'array',
        'last_nubefact_response' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(ShippingGuideItem::class, 'shipping_guide_id');
    }

    public function vehicles()
    {
        return $this->hasMany(ShippingGuideVehicle::class, 'shipping_guide_id');
    }

    public function drivers()
    {
        return $this->hasMany(ShippingGuideDriver::class, 'shipping_guide_id');
    }

    // Catálogos (opcional, si quieres relations por code)
    public function motivoTraslado()
    {
        return $this->belongsTo(TransferReason::class, 'motivo_traslado_code', 'code');
    }

    public function pesoUnidad()
    {
        return $this->belongsTo(WeightUnit::class, 'peso_bruto_um_code', 'code');
    }

    public function sunatIndicator()
    {
        return $this->belongsTo(SunatShippingIndicator::class, 'sunat_shipping_indicator_code', 'code');
    }
}
