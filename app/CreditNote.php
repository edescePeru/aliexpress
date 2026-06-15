<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'sale_id',
        'type_document',
        'serie',
        'numero',
        'reason_code',
        'reason_description',
        'op_gravada',
        'op_exonerada',
        'op_inafecta',
        'igv',
        'total_descuentos',
        'importe_total',
        'credit_note_type',
        'status',
        'sunat_status',
        'sunat_message',
        'sunat_ticket',
        'nubefact_key',
        'nubefact_response',
        'pdf_path',
        'xml_path',
        'cdr_path',
        'pdf_url',
        'xml_url',
        'cdr_url',
        'created_by',
        'accepted_at',
    ];

    protected $dates = [
        'accepted_at',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function details()
    {
        return $this->hasMany(CreditNoteDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
