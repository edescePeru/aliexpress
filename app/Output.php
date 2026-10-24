<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Output extends Model
{
    use BelongsToTenant;

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
    ];

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'execution_order',
        'request_date',
        'requesting_user',
        'responsible_user',
        'state',
        'indicator'
    ];

    protected $dates = ['request_date'];

    public function details()
    {
        return $this->hasMany('App\OutputDetail');
    }

    public function requestingUser()
    {
        return $this->belongsTo('App\User', 'requesting_user');
    }

    public function responsibleUser()
    {
        return $this->belongsTo('App\User', 'responsible_user');
    }

    public function quote()
    {
        return $this->belongsTo('App\Quote', 'execution_order', 'order_execution');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
