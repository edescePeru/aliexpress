<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MaterialDetailSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'enabled_sections',
    ];

    protected $casts = [
        'enabled_sections' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public function scopeForCompany(
        $query,
        $companyId
    ) {
        return $query->where(
            'company_id',
            $companyId
        );
    }
}