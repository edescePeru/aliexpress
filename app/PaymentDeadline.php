<?php
namespace App;


use App\Services\Configuration\PaymentDeadlineReferences;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PaymentDeadline extends Model
{
    protected $fillable = ['description', 'days', 'type', 'credit', 'is_active'];
    protected $casts = ['days' => 'integer', 'credit' => 'boolean', 'is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('payment_company', function ($query) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
            if (!$tenant || !$company) { $query->whereRaw('1 = 0'); return; }
            $query->where('payment_deadlines.tenant_id', $tenant)->where('payment_deadlines.company_id', $company);
        });
        static::saving(function ($model) {
            $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
            if ($model->exists && ((int) $model->getOriginal('tenant_id') !== $tenant || (int) $model->getOriginal('company_id') !== $company)) {
                throw ValidationException::withMessages(['paymentDeadline_id' => 'El plazo no pertenece a la empresa actual.']);
            }
            if ($model->exists && ($model->isDirty('tenant_id') || $model->isDirty('company_id'))) {
                throw ValidationException::withMessages(['paymentDeadline_id' => 'No se puede trasladar un plazo a otra empresa.']);
            }
            if ($model->exists && $model->isDirty(['days', 'type', 'credit']) && PaymentDeadlineReferences::used((int) $model->id)) {
                throw ValidationException::withMessages(['days' => 'El plazo ya se utilizó. Cree otro para cambiar días, tipo o crédito. Puede editar su descripción o desactivarlo.']);
            }
            $model->tenant_id = $tenant; $model->company_id = $company;
        });
        static::deleting(function () {
            throw ValidationException::withMessages(['paymentDeadline_id' => 'Desactive el plazo; no se permite borrar su historial.']);
        });
    }

    public function scopeSelectable($query, $currentId = null)
    {
        return $query->where(function ($q) use ($currentId) {
            $q->where('is_active', true);
            if ($currentId) { $q->orWhere('id', $currentId); }
        });
    }

    public function quotes() { return $this->hasMany('App\Quote'); }
    public function orderPurchases() { return $this->hasMany('App\OrderPurchase'); }
    public function orderServices() { return $this->hasMany('App\OrderService'); }
    public function credits() { return $this->hasMany('App\SupplierCredit'); }
}
