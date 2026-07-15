<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNoteDetailItem extends Model
{
    protected $fillable = [
        'credit_note_detail_id',
        'item_id',
        'output_detail_id',
        'stock_lot_id',
        'quantity',
        'item_code',
        'item_description',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function creditNoteDetail()
    {
        return $this->belongsTo(
            CreditNoteDetail::class,
            'credit_note_detail_id'
        );
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function outputDetail()
    {
        return $this->belongsTo(
            OutputDetail::class,
            'output_detail_id'
        );
    }

    public function stockLot()
    {
        return $this->belongsTo(
            StockLot::class,
            'stock_lot_id'
        );
    }
}
