<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Support\TenantContext;

class ShippingGuide extends Model
{
    protected $table = 'shipping_guides';
    protected $hidden = ['emission_fingerprint', 'emission_key', 'last_nubefact_payload', 'last_nubefact_response', 'nubefact_enlace', 'pdf_link', 'xml_link', 'cdr_link', 'pdf_zip_base64', 'xml_zip_base64', 'cdr_zip_base64'];
    protected $appends = ['documents_available', 'identity_conflict', 'emission_state', 'emission_state_label'];

    public function getEmissionStateAttribute() { return \App\Services\ShippingGuideTransmission::state($this); }
    public function getEmissionStateLabelAttribute() { return \App\Services\ShippingGuideTransmission::label($this); }

    public function getIdentityConflictAttribute()
    {
        return \App\Services\ShippingGuideIdentityConflict::blocked($this);
    }

    public function getDocumentsAvailableAttribute()
    {
        $result = [];
        foreach (\App\Services\ShippingGuideDocument::TYPES as $type) {
            $result[$type] = \App\Services\ShippingGuideDocument::available($this, $type);
        }
        return $result;
    }


    protected $fillable = [
        'emission_key', 'emission_fingerprint', 'submission_state', 'submission_http_status', 'submission_provider_code', 'submission_diagnostic_reference',
        'tenant_id', 'company_id', 'branch_id',
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
    public function scopeForCurrentLocal($query)
    {
        return $query->where([
            'shipping_guides.tenant_id' => TenantContext::tenantId(),
            'shipping_guides.company_id' => TenantContext::companyId(),
            'shipping_guides.branch_id' => TenantContext::branchId(),
        ]);
    }
}
