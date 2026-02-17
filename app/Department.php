<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id','name'];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'department_id', 'id');
    }
}
