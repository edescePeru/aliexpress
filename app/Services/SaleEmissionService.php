<?php

namespace App\Services;

use App\Sale;
use App\SaleEmissionAttempt;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaleEmissionService
{
    /** El builder solo construye datos: no HTTP ni escrituras externas. */
    public function emit(Sale $original, callable $builder): array
    {
        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();
        if (DB::transactionLevel() !== 0) {
            throw new \RuntimeException('Emitir solamente después del commit de la venta.');
        }

        $connection = null;
        $claim = DB::transaction(function () use ($original, $builder, &$connection, $tenantId, $companyId) {
            $sale = $this->saleQuery($original->id, $tenantId, $companyId)->lockForUpdate()->firstOrFail();
            if ((int) $sale->state_annulled === 1 || !in_array($sale->type_document, ['01', '03'], true)) {
                throw new \RuntimeException('La venta no admite emisión electrónica.');
            }
            if (!$sale->branch_id || !DB::table('branches')->where('id', $sale->branch_id)
                ->where('company_id', $companyId)->exists()) {
                throw new \RuntimeException('La sucursal de la venta no corresponde a su empresa.');
            }

            $status = (string) $sale->emission_status;
            if (!in_array($status, ['not_requested', 'failed'], true)) {
                return ['blocked' => true, 'status' => $status];
            }
            // Un documento identificado nunca se reenvía por este método.
            if (!empty($sale->serie_sunat) || !empty($sale->numero)) {
                return ['blocked' => true, 'status' => $status];
            }
            if ($this->attemptQuery($sale->id, $tenantId, $companyId)
                ->whereIn('status', ['processing', 'unknown', 'persistence_pending', 'confirmed'])->exists()) {
                return ['blocked' => true, 'status' => $status];
            }

            if (!$sale->emission_key) {
                $sale->emission_key = bin2hex(random_bytes(10));
            }

            // No cambiar automáticamente una clave existente.
            if (strlen((string) $sale->emission_key) > 20) {
                return [
                    'blocked' => true,
                    'status' => $status,
                    'warning' => 'La clave de emisión existente supera 20 caracteres. '
                        . 'Revise sus intentos antes de cambiarla. No se envió el comprobante.',
                ];
            }
            $sale->emission_status = 'processing';
            $sale->save();

            $attempt = SaleEmissionAttempt::create([
                'tenant_id' => $tenantId, 'company_id' => $companyId,
                'branch_id' => $sale->branch_id, 'sale_id' => $sale->id,
                'user_id' => Auth::id(), 'attempt_key' => (string) Str::uuid(),
                'emission_key' => $sale->emission_key, 'status' => 'processing',
                'document_type' => $sale->type_document, 'started_at' => now(),
            ]);

            try {
                $connection = app(NubefactCompanyConfigService::class)->forSale($sale);
                $payload = $builder($sale, $connection);
                if (!is_array($payload) || empty($payload['serie']) ||
                    ($payload['operacion'] ?? '') !== 'generar_comprobante') {
                    throw new \RuntimeException('El payload de emisión no es válido.');
                }
                // Una clave de operación persistida, distinta de la clave del intento.
                $payload['codigo_unico'] = $sale->emission_key;
                $attempt->request_payload = $payload;
                $attempt->document_series = (string) $payload['serie'];
                $attempt->save();
            } catch (\Throwable $e) {
                // Todavía no hubo llamada al proveedor: fallo local confirmado.
                $attempt->update([
                    'status' => 'failed', 'failure_stage' => 'validation',
                    'error_message' => 'Falló la preparación local del comprobante.',
                    'finished_at' => now(),
                ]);
                $sale->update(['emission_status' => 'failed']);
                return ['blocked' => true, 'status' => 'failed'];
            }

            return ['blocked' => false, 'attempt_id' => $attempt->id,
                'payload' => $payload, 'sale_id' => $sale->id];
        });

        if ($claim['blocked']) {
            return $this->outcome($claim['status'], null,
                $claim['warning'] ?? 'La venta está registrada, pero no se envió el comprobante. Revise su estado de emisión.');
        }

        $url = $connection->url();
        $token = $connection->token();
        $attemptId = $claim['attempt_id'];
        $saleId = $claim['sale_id'];
        try {
            // Sin transacción ni retry automático durante la llamada HTTP.
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $token,
                'Accept' => 'application/json', 'Content-Type' => 'application/json',
            ])->timeout(60)->post($url, $claim['payload']);
            $result = $response->json();
        } catch (\Throwable $e) {
            $this->recordUnknown($saleId, $attemptId, $tenantId, $companyId, 'transport', null, null);
            return $this->outcome('unknown', null,
                'La venta está registrada. No se pudo confirmar la emisión; concilie el resultado antes de reenviar.');
        }

        // Hasta verificar el contrato del proveedor, ningún error remoto se
        // interpreta automáticamente como prueba de que el documento no existe.
        $number = is_array($result) ? (string) ($result['numero'] ?? '') : '';
        $valid = $response->successful() && is_array($result) && empty($result['errors'])
            && (string) ($result['serie'] ?? '') === (string) $claim['payload']['serie']
            && preg_match('/^[1-9][0-9]*$/', $number) === 1;

        if (!$valid) {
            $this->recordUnknown($saleId, $attemptId, $tenantId, $companyId, 'provider',
                $response->status(), is_array($result) ? $result : null);
            return $this->outcome('unknown', null,
                'La venta está registrada. La respuesta de emisión requiere revisión antes de otro envío.');
        }

        // Primero conservar evidencia en el intento. Si falla Sale después,
        // el intento permite recuperar identidad y respuesta sin nueva emisión.
        try {
            $this->attemptQuery($saleId, $tenantId, $companyId)->where('id', $attemptId)
                ->firstOrFail()->update([
                    'status' => 'persistence_pending', 'failure_stage' => 'persistence',
                    'http_status' => $response->status(), 'response_payload' => $result,
                    'document_series' => (string) $result['serie'],
                    'document_number' => $number,
                ]);
        } catch (\Throwable $e) {
            $this->recordUnknown($saleId, $attemptId, $tenantId, $companyId, 'persistence',
                $response->status(), $result);
            return $this->outcome('unknown', null,
                'Se recibió una respuesta con identidad de comprobante, pero no se pudo guardar la evidencia. No reemita.');
        }

        try {
            $attributes = DB::transaction(function () use ($saleId, $attemptId, $tenantId, $companyId, $result, $number) {
                $sale = $this->saleQuery($saleId, $tenantId, $companyId)->lockForUpdate()->firstOrFail();
                $attempt = $this->attemptQuery($saleId, $tenantId, $companyId)->where('id', $attemptId)
                    ->lockForUpdate()->firstOrFail();
                if ($attempt->status !== 'persistence_pending' ||
                    $sale->emission_key !== $attempt->emission_key ||
                    !in_array($sale->emission_status, ['processing', 'unknown'], true) ||
                    (int) $sale->state_annulled === 1) {
                    throw new \RuntimeException('La operación cambió durante la emisión.');
                }
                $sale->update([
                    'emission_status' => 'confirmed',
                    'serie_sunat' => (string) $result['serie'], 'numero' => $number,
                    'sunat_ticket' => $result['sunat_ticket'] ?? null,
                    'sunat_status' => $result['sunat_description'] ?? 'Enviado',
                    'sunat_message' => $result['sunat_note'] ?? '',
                    'fecha_emision' => now()->toDateString(),
                ]);
                $attempt->update(['status' => 'confirmed', 'failure_stage' => null,
                    'error_message' => null, 'finished_at' => now()]);
                return $sale->getAttributes();
            });
            $original->setRawAttributes($attributes, true);
            return $this->outcome('confirmed', $result, null);
        } catch (\Throwable $e) {
            // Mantener la evidencia persistence_pending; no marcar Error SUNAT.
            try {
                $this->saleQuery($saleId, $tenantId, $companyId)
                    ->where('emission_status', 'processing')->update(['emission_status' => 'unknown']);
            } catch (\Throwable $ignored) {
                // Si DB sigue caída, processing también bloqueará otro envío.
            }
            $this->logFailure($saleId, $attemptId, 'persistence');
            return $this->outcome('unknown', null,
                'La respuesta está guardada en el intento, pero falta actualizar la venta. Recupere la persistencia; no reemita.');
        }
    }

    private function recordUnknown($saleId, $attemptId, $tenantId, $companyId, $stage, $httpStatus, $result): void
    {
        try {
            DB::transaction(function () use ($saleId, $attemptId, $tenantId, $companyId, $stage, $httpStatus, $result) {
                $sale = $this->saleQuery($saleId, $tenantId, $companyId)->lockForUpdate()->firstOrFail();
                $attempt = $this->attemptQuery($saleId, $tenantId, $companyId)
                    ->where('id', $attemptId)->lockForUpdate()->firstOrFail();
                if ($sale->emission_key !== $attempt->emission_key || $sale->emission_status !== 'processing') {
                    return;
                }
                $attempt->update([
                    'status' => 'unknown', 'failure_stage' => $stage,
                    'http_status' => $httpStatus, 'response_payload' => $result,
                    'error_message' => 'Resultado no confirmado; requiere conciliación.',
                    'finished_at' => now(),
                ]);
                $sale->update(['emission_status' => 'unknown']);
            });
        } catch (\Throwable $ignored) {
            // La evidencia puede no persistir si DB está caída. Nunca reenviar.
        }
        $this->logFailure($saleId, $attemptId, $stage);
    }

    private function saleQuery($id, $tenantId, $companyId)
    {
        return Sale::query()->where('id', $id)->where('tenant_id', $tenantId)->where('company_id', $companyId);
    }

    private function attemptQuery($saleId, $tenantId, $companyId)
    {
        return SaleEmissionAttempt::query()->where('sale_id', $saleId)
            ->where('tenant_id', $tenantId)->where('company_id', $companyId);
    }

    private function outcome($status, $result, $warning): array
    {
        return ['emission_status' => $status, 'result' => $result,
            'warnings' => $warning === null ? [] : [$warning]];
    }

    private function logFailure($saleId, $attemptId, $stage): void
    {
        try {
            Log::warning('Emisión requiere revisión', [
                'sale_id' => $saleId, 'attempt_id' => $attemptId, 'stage' => $stage,
            ]);
        } catch (\Throwable $ignored) {
            // No transformar un fallo de log posterior al envío en otro envío.
        }
    }
}
