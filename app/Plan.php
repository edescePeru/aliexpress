<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'max_active_users',
        'description',
        'is_active',
    ];

    protected $casts = [
        'max_active_users' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
