<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'max_active_users',
        'max_active_companies',
        'max_active_branches',
        'description',
        'is_active',
    ];

    protected $casts = [
        'max_active_users' => 'integer',
        'max_active_companies' => 'integer',
        'max_active_branches' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
