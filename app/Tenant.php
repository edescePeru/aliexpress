<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'plan_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
