<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SettingValue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'setting_definition_id',
        'company_id',
        'branch_id',
        'value_text',
        'value_number',
        'value_json',
    ];

    protected $casts = [
        'value_json' => 'array',
    ];

    public function definition()
    {
        return $this->belongsTo(
            SettingDefinition::class,
            'setting_definition_id'
        );
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public function branch()
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id'
        );
    }
}