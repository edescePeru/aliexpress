<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SaleEmissionAttempt extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'company_id', 'branch_id', 'sale_id', 'user_id',
        'attempt_key', 'emission_key', 'status', 'failure_stage',
        'error_message', 'http_status', 'document_type', 'document_series',
        'document_number', 'request_payload', 'response_payload',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'sale_id' => 'integer',
        'user_id' => 'integer',
        'http_status' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    protected $dates = ['started_at', 'finished_at'];

    protected $hidden = ['request_payload', 'response_payload', 'error_message'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
