<?php

namespace App;

use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','description'];

    public static function currentContext(): array
    {
        return ['tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId()];
    }

    public function scopeForCurrentCompany($query)
    {
        return $query->where(static::currentContext());
    }

    public static function currentCategoryRule()
    {
        return Rule::exists('category_invoices', 'id')->where(function ($query) {
            $query->where(static::currentContext())->whereNull('deleted_at');
        });
    }

    public function entries()
    {
        return $this->hasMany('App\Entry');
    }

    protected $dates = ['deleted_at'];
}
