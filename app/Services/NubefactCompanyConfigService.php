<?php

namespace App\Services;

use App\Company;
use App\CompanyNubefactConfig;
use App\Sale;
use App\Support\TenantContext;

class NubefactCompanyConfigService
{
    public function forSale(Sale $sale): NubefactCompanyConnection
    {
        return $this->forCompany((int) $sale->tenant_id, (int) $sale->company_id);
    }

    /** Los comandos deben pasar contexto del documento; no de una sesión inventada. */
    public function forCompany(int $tenantId, int $companyId): NubefactCompanyConnection
    {
        if ($tenantId < 1 || $companyId < 1) {
            throw new \RuntimeException('El documento no tiene contexto empresarial válido.');
        }
        $sessionTenant = TenantContext::tenantIdOrNull();
        $sessionCompany = TenantContext::companyIdOrNull();
        if (($sessionTenant !== null && (int) $sessionTenant !== $tenantId) ||
            ($sessionCompany !== null && (int) $sessionCompany !== $companyId)) {
            throw new \RuntimeException('El documento no pertenece al contexto empresarial actual.');
        }

        $company = Company::query()->where('id', $companyId)
            ->where('tenant_id', $tenantId)->where('is_active', true)->first();
        if (!$company) {
            throw new \RuntimeException('La empresa emisora no está disponible.');
        }
        $config = CompanyNubefactConfig::query()->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)->where('is_enabled', true)->first();
        if (!$config) {
            throw new \RuntimeException('La empresa no tiene una conexión Nubefact habilitada.');
        }
        if (!preg_match('/^[0-9]{11}$/', (string) $company->ruc) ||
            (string) $config->ruc !== (string) $company->ruc) {
            throw new \RuntimeException('El RUC de la conexión no coincide con el de la empresa.');
        }

        $url = trim((string) $config->api_url);
        $parts = parse_url($url);
        $hosts = config('nubefact.allowed_hosts', ['api.nubefact.com']);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts) ||
            strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) ||
            !in_array(strtolower($parts['host'] ?? ''), $hosts, true) ||
            (isset($parts['port']) && (int) $parts['port'] !== 443) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
            !preg_match('~^/api/v1/[A-Za-z0-9-]+/?$~', $parts['path'] ?? '')) {
            throw new \RuntimeException('La ruta de Nubefact debe ser una URL HTTPS válida sin credenciales incrustadas.');
        }
        $series = [
            'factura' => trim((string) $config->serie_factura),
            'boleta' => trim((string) $config->serie_boleta),
            'nc_factura' => trim((string) $config->serie_nc_factura),
            'nc_boleta' => trim((string) $config->serie_nc_boleta),
        ];
        foreach (['factura' => 'F', 'boleta' => 'B', 'nc_factura' => 'F', 'nc_boleta' => 'B'] as $key => $prefix) {
            if ($series[$key] === '' && strpos($key, 'nc_') === 0) {
                continue;
            }
            if (!preg_match('/^' . $prefix . '[A-Z0-9]{3}$/', $series[$key])) {
                throw new \RuntimeException('La empresa tiene una serie Nubefact inválida: ' . $key);
            }
        }
        try {
            $token = $config->api_token;
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo descifrar el token Nubefact de la empresa.');
        }
        if (trim($token) === '') {
            throw new \RuntimeException('El token Nubefact de la empresa está vacío.');
        }
        return new NubefactCompanyConnection($url, $token, (string) $company->ruc, $series);
    }
}
