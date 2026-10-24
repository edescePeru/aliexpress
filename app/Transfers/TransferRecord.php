<?php
namespace App\Transfers;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class TransferRecord extends Model
{
    protected $fillable = [];
    protected $immutableColumns = [];
    protected $appendOnly = false;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('transfer_company', function ($query) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
            if (!$tenant || !$company) { $query->whereRaw('1 = 0'); return; }
            $model = $query->getModel();
            $query->where($model->qualifyColumn('tenant_id'), $tenant)->where($model->qualifyColumn('company_id'), $company);
        });
        static::saving(function (TransferRecord $record) {
            $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
            if (!DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)->exists()) {
                $record->invalid('La empresa no corresponde al tenant actual.');
            }
            if ($record->exists) {
                if ((int) $record->getOriginal('tenant_id') !== $tenant || (int) $record->getOriginal('company_id') !== $company
                    || $record->isDirty(['tenant_id', 'company_id'])) { $record->invalid('No se puede cambiar el contexto de un registro de traslado.'); }
                if ($record->isDirty($record->immutableColumns)) { $record->invalid('No se puede cambiar la identidad ni el origen del registro.'); }
                if ($record->appendOnly && $record->isDirty()) { $record->invalid('Las recepciones confirmadas son inmutables.'); }
            }
            $record->tenant_id = $tenant; $record->company_id = $company;
            if (!$record->exists) { $record->initializeNewRecord(); }
            $record->validateReferences();
        });
        static::deleting(function (TransferRecord $record) {
            $record->invalid('Los traslados conservan su historial; utilice el flujo de cancelación o devolución.');
        });
    }

    protected function initializeNewRecord(): void {}
    abstract protected function validateReferences(): void;

    protected function parentRecord(string $class, $id)
    {
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || !($parent = $class::query()->find($id))) {
            $this->invalid('La referencia no está disponible en la empresa actual.');
        }
        return $parent;
    }

    protected function owned(string $table, $id)
    {
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || !($row = DB::table($table)->where('id', $id)
            ->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->first())) {
            $this->invalid('La referencia no pertenece a la empresa actual.');
        }
        return $row;
    }

    protected function quantity($value, bool $zero = false): void
    {
        if (!preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', (string) $value)
            || bccomp((string) $value, '0', 2) < ($zero ? 0 : 1)) {
            $this->invalid('La cantidad debe ser positiva y tener como máximo dos decimales.');
        }
    }

    protected function cost($value): void
    {
        if (!preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,6})?$/D', (string) $value)) { $this->invalid('El costo debe ser no negativo y tener hasta seis decimales.'); }
    }

    protected function requestIdentity(): void
    {
        if (!$this->request_key) { $this->request_key = (string) \Illuminate\Support\Str::uuid(); }
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $this->request_key)
            || ($this->request_hash !== null && !preg_match('/^[0-9a-f]{64}$/iD', $this->request_hash))) {
            $this->invalid('La identidad de la solicitud no es válida.');
        }
    }

    protected function invalid(string $message): void
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }
}
