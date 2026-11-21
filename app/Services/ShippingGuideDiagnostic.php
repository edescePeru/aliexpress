<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class ShippingGuideDiagnostic
{
    public static function record(\Throwable $error, string $operation, $guide = null): ?string
    {
        try {
            $reference = bin2hex(random_bytes(12));
            $context = [
                'reference' => $reference,
                'operation' => in_array($operation, ['generate', 'consult'], true) ? $operation : 'unknown',
                'guide_id' => $guide ? (int) $guide->id : null,
                'tenant_id' => $guide ? (int) $guide->tenant_id : null,
                'company_id' => $guide ? (int) $guide->company_id : null,
                'branch_id' => $guide ? (int) $guide->branch_id : null,
                'category' => 'application',
            ];
            if ($error instanceof NubefactGreHttpException) {
                $context['category'] = 'provider_http';
                $context = array_merge($context, $error->diagnostic());
                $context['detail_archived'] = GreProviderFailureArchive::capture($error, $reference, $context['operation'], $guide);
            } elseif ($error instanceof \Illuminate\Http\Client\ConnectionException) {
                $context['category'] = 'connection';
            } elseif ($error instanceof \TypeError) {
                $context['category'] = 'type_error';
            }
            // No registrar $error, getMessage(), traza, request, payload ni respuesta cruda.
            Log::error('GRE integration failure', $context);
            return $reference;
        } catch (\Throwable $loggingError) {
            // Un fallo del registro no debe provocar otro envío ni ocultar la persistencia.
            return null;
        }
    }
}
