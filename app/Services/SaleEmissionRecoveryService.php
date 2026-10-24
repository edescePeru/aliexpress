<?php

namespace App\Services;

use App\Sale;
use App\SaleEmissionAttempt;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleEmissionRecoveryService
{
    /** Recuperación LOCAL: no HTTP, no credenciales ni nueva emisión. */
    public function recover(int $saleId, int $attemptId): array
    {
        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();

        return DB::transaction(function () use ($saleId, $attemptId, $tenantId, $companyId) {
            // Mismo orden de bloqueo que SaleEmissionService: Sale, intento.
            $sale = Sale::query()->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)->where('id', $saleId)
                ->whereNotNull('quote_id')->lockForUpdate()->firstOrFail();

            $attempt = SaleEmissionAttempt::query()->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)->where('sale_id', $sale->id)
                ->where('id', $attemptId)->lockForUpdate()->firstOrFail();

            if ((int) $sale->state_annulled === 1 || !in_array($sale->type_document, ['01', '03'], true)) {
                $this->invalid('La venta no admite recuperación de emisión.');
            }
            if (in_array($sale->annulment_status, ['pending', 'waiting_sunat_process'], true)) {
                $this->invalid('La venta tiene una anulación pendiente. Requiere revisión.');
            }
            if (!$sale->emission_key || $sale->emission_key !== $attempt->emission_key ||
                (int) $sale->branch_id !== (int) $attempt->branch_id ||
                $sale->type_document !== $attempt->document_type) {
                $this->invalid('El intento no corresponde al contexto y operación de la venta.');
            }
            if (!DB::table('branches')->where('id', $sale->branch_id)
                ->where('company_id', $companyId)->exists()) {
                $this->invalid('La sucursal de la venta no pertenece a su empresa.');
            }
            if (!in_array($attempt->status, ['persistence_pending', 'confirmed'], true)) {
                $this->invalid('Este intento no tiene persistencia pendiente recuperable. No se reemitió.');
            }
            if (!in_array($sale->emission_status, ['processing', 'unknown', 'confirmed'], true)) {
                $this->invalid('El estado de la venta cambió; requiere revisión.');
            }
            // No elegir un intento arbitrario cuando hay evidencia competidora.
            if (SaleEmissionAttempt::query()->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)->where('sale_id', $sale->id)
                ->where('id', '<>', $attempt->id)
                ->whereIn('status', ['processing', 'unknown', 'persistence_pending', 'confirmed'])
                ->exists()) {
                $this->invalid('Existen otros intentos sin conciliar. Revise la evidencia antes de recuperar.');
            }

            $request = $attempt->request_payload;
            $response = $attempt->response_payload;
            $number = is_array($response) ? (string) ($response['numero'] ?? '') : '';
            $series = is_array($response) ? (string) ($response['serie'] ?? '') : '';
            $providerType = $sale->type_document === '01' ? '1' : '2';

            if (!is_array($request) || !is_array($response) || !empty($response['errors']) ||
                (int) $attempt->http_status < 200 || (int) $attempt->http_status >= 300 ||
                ($request['operacion'] ?? '') !== 'generar_comprobante' ||
                (string) ($request['codigo_unico'] ?? '') !== $sale->emission_key ||
                (string) ($request['tipo_de_comprobante'] ?? '') !== $providerType ||
                $series === '' || $series !== (string) ($request['serie'] ?? '') ||
                $series !== (string) $attempt->document_series ||
                preg_match('/^[1-9][0-9]*$/', $number) !== 1 ||
                $number !== (string) $attempt->document_number) {
                $this->invalid('La evidencia guardada está incompleta o no es consistente. No se modificó la venta.');
            }

            if ((!empty($sale->serie_sunat) && (string) $sale->serie_sunat !== $series) ||
                (!empty($sale->numero) && (string) $sale->numero !== $number)) {
                $this->invalid('La venta ya tiene otra identidad de comprobante. No se sobrescribió.');
            }

            $alreadyConfirmed = $sale->emission_status === 'confirmed';
            if ($alreadyConfirmed) {
                if ((string) $sale->serie_sunat !== $series || (string) $sale->numero !== $number) {
                    $this->invalid('La venta confirmada no coincide con el intento.');
                }
                // No sobrescribir estados tributarios posteriores ni fecha de emisión.
            } else {
                if ($attempt->status !== 'persistence_pending') {
                    $this->invalid('El intento está confirmado pero la venta no. Requiere revisión.');
                }
                // Recuperar la fecha ORIGINAL del payload, no la fecha de recuperación.
                $dateText = (string) ($request['fecha_de_emision'] ?? '');
                try {
                    $emissionDate = Carbon::createFromFormat('!d-m-Y', $dateText);
                    if (!$emissionDate || $emissionDate->format('d-m-Y') !== $dateText) {
                        $this->invalid('La fecha original de emisión no es válida.');
                    }
                } catch (\Throwable $e) {
                    $this->invalid('No se pudo validar la fecha original de emisión.');
                }

                $sale->update([
                    'emission_status' => 'confirmed',
                    'serie_sunat' => $series, 'numero' => $number,
                    'sunat_ticket' => $response['sunat_ticket'] ?? null,
                    'sunat_status' => $response['sunat_description'] ?? 'Enviado',
                    'sunat_message' => $response['sunat_note'] ?? '',
                    'fecha_emision' => $emissionDate->format('Y-m-d'),
                ]);
            }

            if ($attempt->status === 'persistence_pending') {
                $attempt->update([
                    'status' => 'confirmed', 'failure_stage' => null,
                    'error_message' => null, 'finished_at' => now(),
                ]);
            }

            return [
                'message' => $alreadyConfirmed
                    ? 'El comprobante ya estaba registrado; se verificó la coincidencia.'
                    : 'Se recuperaron los datos del comprobante sin volver a emitirlo.',
                'sale_id' => $sale->id, 'attempt_id' => $attempt->id,
                'emission_status' => 'confirmed', 'serie_sunat' => $sale->serie_sunat,
                'numero' => $sale->numero, 'sunat_status' => $sale->sunat_status,
                'already_confirmed' => $alreadyConfirmed,
            ];
        });
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['attempt_id' => $message]);
    }
}
