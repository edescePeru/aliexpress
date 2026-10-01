<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EquipmentWorkforce extends Model
{
    protected $fillable = [
        'equipment_id',
        'description',
        'price',
        'quantity',
        'total',
        'unit',
        'billable',
        'type_tax_id',
        'tax_rate',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
    ];

    public function equipment(){
        return $this->belongsTo('App\Equipment');
    }

    public function typeTax()
    {
        return $this->belongsTo(
            TypeTax::class,
            'type_tax_id'
        );
    }
}
