<?php

namespace App;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'tenant_id',
        'role_template_id',
        'name',
        'description',
        'guard_name',
        'is_owner_assignable',
        'is_customized',
        'is_active',
    ];

    protected $casts = [
        'is_owner_assignable' => 'boolean',
        'is_customized' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function template()
    {
        return $this->belongsTo(
            RoleTemplate::class,
            'role_template_id'
        );
    }
}