<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GananciaDiaria extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'currency',
        'date_resumen',
        'quantity_sale',
        'total_sale',
        'total_utility'
    ];

    public function details()
    {
        return $this->hasMany('App\GananciaDiariaDetail');
    }

    protected $dates = ['date_resumen'];
}
