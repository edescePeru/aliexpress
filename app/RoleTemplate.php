<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class RoleTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_owner_assignable',
        'is_active',
    ];

    protected $casts = [
        'is_owner_assignable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_template_permission',
            'role_template_id',
            'permission_id'
        );
    }

    public function roles()
    {
        return $this->hasMany(
            Role::class,
            'role_template_id'
        );
    }
}
