<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNoteDetail extends Model
{
    protected $fillable = [
        'credit_note_id',
        'sale_detail_id',
        'description',
        'quantity',
        'price',
        'valor_unitario',
        'subtotal',
        'igv',
        'total',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
