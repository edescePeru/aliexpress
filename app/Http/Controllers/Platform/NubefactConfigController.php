<?php

namespace App\Http\Controllers\Platform;

use App\Company;
use App\CompanyNubefactConfig;
use App\Tenant;
use App\Scopes\TenantScope;
use App\Http\Controllers\Controller;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NubefactConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.admin']);
    }

    public function index(Request $request)
    {
        $tenantId = ctype_digit((string) $request->query('tenant_id', ''))
            ? (int) $request->query('tenant_id') : null;
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);
        $companies = Company::query()->with(['tenant', 'nubefactConfig' => function ($query) {
            $query->withoutGlobalScope(TenantScope::class);
        }])
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->orderBy('tenant_id')->orderBy('business_name')->paginate(20);

        return view('platform.nubefact.index', compact('companies', 'tenants', 'tenantId'));
    }

    public function edit($tenantId, $companyId)
    {
        $company = Company::query()->with('tenant')->where('tenant_id', $tenantId)
            ->where('id', $companyId)->firstOrFail();
        $config = CompanyNubefactConfig::withoutGlobalScope(TenantScope::class)->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)->first();

        return view('platform.nubefact.edit', compact('company', 'config'));
    }

    public function update(Request $request, $tenantId, $companyId, PlatformAuditService $audit)
    {
        // No aceptar tenant, Company ni RUC del formulario.
        $company = Company::query()->with('tenant')->where('tenant_id', $tenantId)
            ->where('id', $companyId)->firstOrFail();
        $validator = Validator::make($request->only([
            'api_url', 'api_token', 'is_enabled', 'serie_factura', 'serie_boleta',
            'serie_nc_factura', 'serie_nc_boleta', 'version',
        ]), [
            'api_url' => ['required', 'string', 'max:2048', 'url'],
            'api_token' => ['nullable', 'string', 'max:4096'],
            'is_enabled' => ['required', 'boolean'],
            'serie_factura' => ['required', 'regex:/^F[A-Z0-9]{3}$/'],
            'serie_boleta' => ['required', 'regex:/^B[A-Z0-9]{3}$/'],
            'serie_nc_factura' => ['nullable', 'regex:/^F[A-Z0-9]{3}$/'],
            'serie_nc_boleta' => ['nullable', 'regex:/^B[A-Z0-9]{3}$/'],
            'version' => ['nullable', 'string', 'max:40'],
        ]);
        $validator->after(function ($validator) use ($request) {
            if (!is_string($request->input('api_url'))) {
                return;
            }
            $parts = parse_url((string) $request->input('api_url', ''));
            $hosts = config('nubefact.allowed_hosts', ['api.nubefact.com']);
            if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' ||
                !in_array(strtolower($parts['host'] ?? ''), $hosts, true) ||
                (isset($parts['port']) && (int) $parts['port'] !== 443) ||
                isset($parts['user']) || isset($parts['pass']) ||
                isset($parts['query']) || isset($parts['fragment']) ||
                !preg_match('~^/api/v1/[A-Za-z0-9-]+/?$~', $parts['path'] ?? '')) {
                $validator->errors()->add('api_url', 'Use una ruta HTTPS de integración de un servidor Nubefact autorizado.');
            }
        });
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($this->safeInput($request));
        }
        $data = $validator->validated();

        try {
            DB::transaction(function () use ($company, $data, $audit) {
                // Bloquear Company también evita dos creaciones simultáneas.
                $tenant = Tenant::query()->where('id', $company->tenant_id)
                    ->lockForUpdate()->firstOrFail();
                $lockedCompany = Company::query()->where('id', $company->id)
                    ->where('tenant_id', $company->tenant_id)->lockForUpdate()->firstOrFail();
                $config = CompanyNubefactConfig::withoutGlobalScope(TenantScope::class)->where('tenant_id', $lockedCompany->tenant_id)
                    ->where('company_id', $lockedCompany->id)->lockForUpdate()->first();
                $created = $config === null;
                $actualVersion = $config ? (string) $config->updated_at : '';
                if ((string) ($data['version'] ?? '') !== $actualVersion) {
                    throw ValidationException::withMessages([
                        'version' => 'La configuración cambió desde que abrió el formulario. Recargue antes de guardar.',
                    ]);
                }
                if (!preg_match('/^[0-9]{11}$/', (string) $lockedCompany->ruc)) {
                    throw ValidationException::withMessages(['ruc' => 'La empresa debe tener un RUC de 11 dígitos.']);
                }
                if ((bool) $data['is_enabled'] && (!$lockedCompany->is_active || !$tenant->is_active)) {
                    throw ValidationException::withMessages(['is_enabled' => 'No puede habilitar la conexión de una empresa o tenant inactivo.']);
                }
                $newToken = trim((string) ($data['api_token'] ?? ''));
                if ($created && $newToken === '') {
                    throw ValidationException::withMessages(['api_token' => 'Ingrese el token para registrar la conexión.']);
                }
                if ($config && (string) $config->ruc !== (string) $lockedCompany->ruc && $newToken === '') {
                    throw ValidationException::withMessages(['api_token' => 'El RUC cambió. Registre las credenciales del nuevo emisor.']);
                }
                if ($config && $config->api_url !== trim($data['api_url']) && $newToken === '') {
                    throw ValidationException::withMessages(['api_token' => 'Al cambiar la ruta, ingrese también el token de esa conexión.']);
                }
                $before = $config ? $this->auditValues($config) : null;
                if (!$config) {
                    $config = new CompanyNubefactConfig();
                }
                $config->fill([
                    'tenant_id' => $lockedCompany->tenant_id, 'company_id' => $lockedCompany->id,
                    'ruc' => $lockedCompany->ruc, 'api_url' => trim($data['api_url']),
                    'is_enabled' => (bool) $data['is_enabled'],
                    'serie_factura' => $data['serie_factura'], 'serie_boleta' => $data['serie_boleta'],
                    'serie_nc_factura' => $data['serie_nc_factura'] ?? null,
                    'serie_nc_boleta' => $data['serie_nc_boleta'] ?? null,
                ]);
                if ($newToken !== '') {
                    $config->api_token = $newToken;
                }
                $config->save();
                // Lista explícita: no auditar tokens ni ciphertext ni request completo.
                $audit->log($created ? 'nubefact_config_created' : 'nubefact_config_updated', $config, [
                    'tenant_id' => $lockedCompany->tenant_id, 'company_id' => $lockedCompany->id,
                    'token_changed' => $newToken !== '', 'before' => $before,
                    'after' => $this->auditValues($config),
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($this->safeInput($request));
        } catch (\Throwable $e) {
            Log::error('No se pudo guardar configuración Nubefact', [
                'tenant_id' => $company->tenant_id, 'company_id' => $company->id,
                'exception_type' => get_class($e),
            ]);
            return back()->withErrors(['configuration' => 'No se pudo guardar la conexión. Revise el registro técnico.'])
                ->withInput($this->safeInput($request));
        }

        return redirect()->route('platformNubefact.edit', [$company->tenant_id, $company->id])
            ->with('success', 'La configuración fue guardada. No se emitió ningún comprobante ni se probó la conexión.');
    }

    private function safeInput(Request $request): array
    {
        return $request->only(['api_url', 'is_enabled', 'serie_factura', 'serie_boleta',
            'serie_nc_factura', 'serie_nc_boleta', 'version']);
    }

    private function auditValues(CompanyNubefactConfig $config): array
    {
        return ['ruc' => $config->ruc, 'is_enabled' => (bool) $config->is_enabled,
            'api_host' => parse_url($config->api_url, PHP_URL_HOST),
            'serie_factura' => $config->serie_factura, 'serie_boleta' => $config->serie_boleta,
            'serie_nc_factura' => $config->serie_nc_factura, 'serie_nc_boleta' => $config->serie_nc_boleta];
    }
}
