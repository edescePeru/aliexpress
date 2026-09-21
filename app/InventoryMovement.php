<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements_view';
    public $timestamps = false;

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'movement_date' => 'datetime',
        'quantity' => 'float',
        'unit_cost' => 'float',
    ];
}
