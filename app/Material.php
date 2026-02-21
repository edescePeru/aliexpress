<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $appends = ['full_description', 'stock_store', 'price_final', 'name_unit'];

    protected $fillable = [
        'code',
        'description',
        'measure',
        'unit_measure_id',
        'stock_max',
        'stock_min',
        'stock_current',
        'stock_reserved',
        'priority',
        'unit_price',
        'image',
        'category_id',
        'subcategory_id',
        'material_type_id',
        'subtype_id',
        'brand_id',
        'exampler_id',
        'warrant_id',
        'quality_id',
        'typescrap_id',
        'enable_status',
        'full_name',
        'inventory',
        'date_update_price',
        'state_update_price',
        'rotation',
        'rotation_value',
        'genero_id',
        'talla_id',
        'tipo_venta_id',
        'perecible',
        'codigo',
        'type_tax_id',
        'list_price',
        'max_price',
        'min_price',
        'isPack',
        'quantityPack',
        'stock_unPack',
        'percentage_price'
    ];

    public function setNameProductAttribute($value)
    {
        $this->attributes['name_product'] = strtoupper($value);
    }

    public function scopeWhereConsumable($query, $column, $value)
    {
        return $query->where($column, 'like', $value.'%');
    }

    public function scopeWhereElectric($query, $column, $value)
    {
        return $query->where($column, 'like', $value.'%');
    }

    public function getQuantityItemsAttribute()
    {
        $items = Item::where('material_id', $this->id)
            ->where('usage', '<>', 'finished')
            ->get();
        $quantity = 0;
        if ( isset($items) )
        {
            $quantity = count($items);
        }
        return $quantity;
    }

    public function getNameUnitAttribute()
    {
        $unit = ($this->unit_measure_id == null) ? '':$this->unitMeasure->name;

        return $unit;
    }

    public function getPriceFinalAttribute()
    {

        if ( $this->list_price == 0 || $this->list_price == null )
        {
            return $this->unit_price;
        }

        return $this->list_price;

    }

    public function getFullDescriptionAttribute()
    {

        return $this->full_name;

    }

    public function unitMeasure()
    {
        return $this->belongsTo('App\UnitMeasure');
    }

    public function category()
    {
        return $this->belongsTo('App\Category')->withTrashed();
    }

    public function subcategory()
    {
        return $this->belongsTo('App\Subcategory');
    }

    public function materialType()
    {
        return $this->belongsTo('App\MaterialType', 'material_type_id');
    }

    public function subType()
    {
        return $this->belongsTo('App\Subtype', 'subtype_id');
    }

    public function exampler()
    {
        return $this->belongsTo('App\Exampler');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function warrant()
    {
        return $this->belongsTo('App\Warrant');
    }

    public function quality()
    {
        return $this->belongsTo('App\Quality');
    }

    public function genero()
    {
        return $this->belongsTo('App\Warrant');
    }

    public function talla()
    {
        return $this->belongsTo('App\Quality');
    }

    public function typeScrap()
    {
        return $this->belongsTo('App\Typescrap', 'typescrap_id');
    }

    public function typeTax()
    {
        return $this->belongsTo('App\TypeTax');
    }

    public function tipoVenta()
    {
        return $this->belongsTo('App\TipoVenta');
    }

    public function defaultItems()
    {
        return $this->hasMany('App\DefaultItem');
    }

    public function items()
    {
        return $this->hasMany('App\Item');
    }

    public function detailEntries()
    {
        return $this->hasMany('App\DetailEntry');
    }

    public function detailOutputs()
    {
        return $this->hasMany('App\OutputDetail');
    }

    public function unpackedChild()
    {
        return $this->hasOne(MaterialUnpack::class, 'parent_product_id');
    }

    public function unpackedParents()
    {
        return $this->hasMany(MaterialUnpack::class, 'child_product_id');
    }

    public function getStockStoreAttribute()
    {
        $storeMaterial = StoreMaterial::where('material_id', $this->id)->sum('stock_current');
        return $storeMaterial;
    }

    protected $dates = ['deleted_at'];

    public function toArray()
    {
        $array = parent::toArray();
        $array['full_description'] = $this->full_description;
        return $array;
    }

    // Scope para obtener el resumen por material
    public function scopeResumenPorMaterial($query)
    {
        return $query->selectRaw('
                id,
                full_name,
                MIN(stock_min) as stock_min,
                SUM(stock_current) as stock_total
            ')
            ->where('enable_status', 1)
            ->groupBy('id', 'full_name')
            ->orderBy('full_name');
    }
}
