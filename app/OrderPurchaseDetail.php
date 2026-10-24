<?php

namespace App;

use App\Support\TenantContext;
use App\Traits\ScopedPurchaseCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPurchaseDetail extends Model
{
    use ScopedPurchaseCompany;
    use SoftDeletes;

    public $fillable = [
        'order_purchase_id',
        'material_id',
        'stock_item_id',
        'quantity',
        'price',
        'igv',
        'total_detail'
    ];

    protected $dates = ['deleted_at'];

    public function order_purchase()
    {
        return $this->belongsTo('App\OrderPurchase');
    }

    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function receiptDetails()
    {
        return $this->hasMany(DetailEntry::class, 'order_purchase_detail_id')
            ->where('detail_entries.tenant_id', TenantContext::tenantId())
            ->where('detail_entries.company_id', TenantContext::companyId())
            ->whereHas('entry', function ($query) {
                $query->where('entries.tenant_id', TenantContext::tenantId())
                    ->where('entries.company_id', TenantContext::companyId());
            });
    }

}
