<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'currency',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public function materialPrices()
    {
        return $this->hasMany(
            PriceListMaterial::class,
            'price_list_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            PriceListItem::class,
            'price_list_id'
        );
    }

}
