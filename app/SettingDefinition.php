<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SettingDefinition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'module',
        'scope',
        'value_type',
        'default_value',
        'options_json',
        'description',
        'editable_by_owner',
        'is_active',
    ];

    protected $casts = [
        'options_json' => 'array',
        'editable_by_owner' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(
            SettingValue::class,
            'setting_definition_id'
        );
    }
}