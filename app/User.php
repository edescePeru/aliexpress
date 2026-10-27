<?php

namespace App;

use App\Support\TenantContext;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    //use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'enable',
        'owner',
        'tenant_id',
        'is_platform_admin',
        'is_tenant_owner',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_platform_admin' => 'boolean',
        'is_tenant_owner' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public function workers()
    {
        return $this->hasMany(Worker::class, 'user_id');
    }

    // Compatibility relation: only a worker in the active tenant/company.
    public function worker()
    {
        $relation = $this->hasOne(Worker::class, 'user_id');
        $tenant = TenantContext::tenantIdOrNull();
        $company = TenantContext::companyIdOrNull();
        return $tenant && $company
            ? $relation->where('workers.tenant_id', $tenant)->where('workers.company_id', $company)
            : $relation->whereRaw('1 = 0');
    }


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function isPlatformAdmin()
    {
        return (bool) $this->is_platform_admin;
    }

    public function isTenantOwner()
    {
        return (bool) $this->is_tenant_owner;
    }
}
