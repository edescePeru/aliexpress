<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'presentation_label_snapshot',
        'type_tax_id', 'tax_kind', 'tax_provider_code', 'tax_rate_snapshot',
        'tenant_id',
        'company_id',
        'price_list_id', 'price_list_name', 'catalog_unit_price', 'catalog_price_source',
        'sale_id',
        'material_id',
        'stock_item_id',
        'description',
        'material_presentation_id',
        'valor_unitario',
        'price',
        'quantity',
        'packs',
        'units_per_pack',
        'percentage_tax',
        'tax_amount',
        'total',
        'discount',
        'unit_cost',
        'total_cost',
        'item_snapshot',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'item_snapshot' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function materialPresentation()
    {
        return $this->belongsTo(MaterialPresentation::class, 'material_presentation_id');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function creditNoteDetails()
    {
        return $this->hasMany(CreditNoteDetail::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
