<?php

namespace App;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $table = 'metas';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'tipo',        // semanal | quincenal | mensual
        'nombre',
        'monto',
        'fecha_inicio',
        'fecha_fin',
    ];

    public function scopeForCurrentCompany($query)
    {
        $tenantId = TenantContext::tenantIdOrNull();
        $companyId = TenantContext::companyIdOrNull();

        return $tenantId && $companyId
            ? $query->where('metas.tenant_id', $tenantId)->where('metas.company_id', $companyId)
            : $query->whereRaw('1 = 0');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'meta_users')->withTimestamps();
    }

    // Relación muchos a muchos con trabajadores
    public function workers()
    {
        return $this->belongsToMany(Worker::class, 'meta_workers')
            ->withTimestamps();
    }

    // Por si necesitas acceder a la tabla pivote directamente
    public function metaWorkers()
    {
        return $this->hasMany(MetaWorker::class);
    }
}
