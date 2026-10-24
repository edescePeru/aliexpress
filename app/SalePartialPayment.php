<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SalePartialPayment extends Model
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
        'sale_id',
        'cash_movement_id',
        'user_id',
        'payment_date',
        'amount',
        'state',
    ];

    protected $dates = [
        'payment_date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashMovement()
    {
        return $this->belongsTo(CashMovement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
