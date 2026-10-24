<?php

namespace App;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CompanyNubefactConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'company_id', 'ruc', 'api_url', 'api_token', 'is_enabled',
        'serie_factura', 'serie_boleta', 'serie_nc_factura', 'serie_nc_boleta',
    ];

    protected $hidden = ['api_token', 'api_token_encrypted'];

    protected $casts = [
        'tenant_id' => 'integer', 'company_id' => 'integer', 'is_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($config) {
            $company = Company::query()->where('id', $config->company_id)
                ->where('tenant_id', $config->tenant_id)->first();
            if (!$company || !preg_match('/^[0-9]{11}$/', (string) $config->ruc) ||
                (string) $company->ruc !== (string) $config->ruc) {
                throw new \RuntimeException('La conexión Nubefact no corresponde al tenant, empresa y RUC indicados.');
            }
            if (empty($config->getAttributes()['api_token_encrypted'])) {
                throw new \RuntimeException('Debe registrar un token Nubefact.');
            }
        });
    }

    public function setApiTokenAttribute($value): void
    {
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException('El token Nubefact no puede estar vacío.');
        }
        $this->attributes['api_token_encrypted'] = Crypt::encryptString(trim($value));
    }

    public function getApiTokenAttribute(): string
    {
        $encrypted = $this->attributes['api_token_encrypted'] ?? '';
        if ($encrypted === '') {
            throw new \RuntimeException('La conexión Nubefact no tiene token.');
        }
        return Crypt::decryptString($encrypted);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
