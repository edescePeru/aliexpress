<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailEntry extends Model
{
    protected $appends = ['sub_total', 'taxes', 'total', 'unit', 'material_description', 'material_code'];

    protected $fillable = [
        'entry_id',
        'material_id',
        'stock_item_id',
        // 'location_id'
        // 'warehouse_id'
        'ordered_quantity',
        'entered_quantity',
        'isComplete',
        'unit_price',
        'material_name',
        'material_unit',
        'total_detail',
        'date_vence'
    ];

    protected $dates = ['date_vence'];

    public function getMaterialCodeAttribute()
    {
        $codeMaterial = "";

        if ( is_null($this->stock_item_id ) )
        {
            $nameMaterial = is_null($this->material) ? "N/N" : $this->material->code;
        } else {
            $stockItem = StockItem::find($this->stock_item_id);
            $nameMaterial = $stockItem->sku;
        }
        return $nameMaterial;
    }

    public function getMaterialDescriptionAttribute()
    {
        $nameMaterial = "";

        if ( is_null($this->stock_item_id ) )
        {
            $nameMaterial = is_null($this->material) ? $this->material_name : $this->material->full_name;
        } else {
            $stockItem = StockItem::find($this->stock_item_id);
            $nameMaterial = $stockItem->display_name;
        }
        return $nameMaterial;
    }


    public function getMaterialDescriptioneAttribute()
    {
        return (is_null($this->material)) ? $this->material_name : $this->material->full_description;
    }

    public function getUnitAttribute()
    {
        return (is_null($this->material)) ? $this->material_unit : $this->material->unitMeasure->name;
    }

    public function getSubTotalAttribute()
    {
        if ( $this->total_detail != null )
        {
            $number = ($this->total_detail)/1.18;
        } else {
            $number = ($this->entered_quantity * $this->unit_price)/1.18;
        }

        return number_format($number, 2);
    }

    public function getTaxesAttribute()
    {
        if ( $this->total_detail != null )
        {
            $number = (($this->total_detail)/1.18)*0.18;
        } else {
            $number = (($this->entered_quantity * $this->unit_price)/1.18)*0.18;
        }

        return number_format($number, 2);
    }

    public function getTotalAttribute()
    {
        if ( $this->total_detail != null )
        {
            $number = $this->total_detail;
        } else {
            $number = $this->entered_quantity * $this->unit_price;
        }

        return number_format($number, 2);
    }

    public function entry()
    {
        return $this->belongsTo('App\Entry');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function items()
    {
        return $this->hasMany('App\Item');
    }

    public function stockItem()
    {
        return $this->belongsTo('App\StockItem', 'stock_item_id');
    }
}
