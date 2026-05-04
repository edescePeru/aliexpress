<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GananciaDiariaDetail extends Model
{
    protected $fillable = [
        'ganancia_diaria_id',
        'date_detail',
        'material_id',
        'stock_item_id',
        'quantity',
        'price_sale',
        'utility'
    ];

    public function gananciaDiaria()
    {
        return $this->belongsTo('App\GananciaDiaria');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    protected $dates = ['date_detail'];
}
