<?php
namespace App\Services;

use App\ShippingGuide;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Recuperación explícita, una sola vez por guía. Nunca se reintenta por tiempo. */
class ShippingGuideRecovery
{
    private $provider;
    private $configs;

    public function __construct(NubefactGreService $provider, NubefactCompanyConfigService $configs)
    {
        $this->provider = $provider;
        $this->configs = $configs;
    }

    public function preview(ShippingGuide $guide): array
    {
        if (!Schema::hasTable('shipping_guide_recovery_attempts')) {
            return ['enabled' => false, 'reason' => 'La recuperación requiere instalar su migración.'];
        }
        if (!$this->eligible($guide)) {
            return ['enabled' => false, 'reason' => 'Solo se recuperan guías SENT sin respuesta ni documentos del proveedor.'];
        }
        $prior = DB::table('shipping_guide_recovery_attempts')->where('shipping_guide_id', $guide->id)->first();
        if ($prior && !($prior->state === 'missing_checked' && $prior->phase === 'consult')) {
            return ['enabled' => false, 'reason' => 'Esta guía ya tiene un intento de recuperación. Consulte su estado; requiere revisión antes de otro envío.'];
        }
        try {
            $guide->load(['items', 'vehicles', 'drivers']);
            $connection = $this->configs->forCompany((int) $guide->tenant_id, (int) $guide->company_id);
            $payload = $this->verifiedPayload($guide);
            return ['enabled' => true, 'version' => $this->previewVersion($guide, $payload, $connection), 'plates' => $guide->vehicles->map(function ($v) { return ['from' => $v->plate_number, 'to' => GreVehiclePlate::normalize($v->plate_number)]; })->all()];
        } catch (\Throwable $error) {
            return ['enabled' => false, 'reason' => 'Revise la conexión y los datos históricos de la guía antes de recuperarla.'];
        }
    }

    public function run(int $guideId, string $expectedVersion, int $userId, bool $allowGeneration = false): array
    {
        abort_unless($userId > 0, 403);
        $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->findOrFail($guideId);
        $connection = $this->configs->forCompany((int) $guide->tenant_id, (int) $guide->company_id);
        $attemptId = bin2hex(random_bytes(16));
        // Reserva persistente antes de cualquier petición externa; el UNIQUE es segunda defensa.
        list($guide, $payload, $contentVersion) = DB::transaction(function () use ($guideId, $expectedVersion, $userId, $connection, &$attemptId, $allowGeneration) {
            $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->lockForUpdate()->findOrFail($guideId);
            abort_unless($this->eligible($guide), 409, 'La guía ya no admite recuperación. Consulte su estado.');
            $prior = DB::table('shipping_guide_recovery_attempts')->where('shipping_guide_id', $guide->id)->first();
            if ($prior) {
                abort_unless($prior->state === 'missing_checked' && $prior->phase === 'consult'
                    && (int) $prior->user_id === $userId && $allowGeneration
                    && (int) $prior->tenant_id === (int) $guide->tenant_id
                    && (int) $prior->company_id === (int) $guide->company_id
                    && (int) $prior->branch_id === (int) $guide->branch_id, 409,
                    'Solo el usuario que comprobó la ausencia puede autorizar ahora el envío, desde la pantalla actualizada. Otros intentos permanecen bloqueados.');
            }
            $guide->load(['items', 'vehicles', 'drivers']);
            $payload = $this->verifiedPayload($guide);
            abort_unless(hash_equals($this->previewVersion($guide, $payload, $connection), $expectedVersion), 409,
                'La guía o la conexión cambiaron. Recargue la pantalla y revise los datos.');
            if ($prior) {
                $attemptId = $prior->id;
                $affected = DB::table('shipping_guide_recovery_attempts')->where('id', $attemptId)
                    ->where('state', 'missing_checked')->where('phase', 'consult')
                    ->update(['state' => 'running', 'updated_at' => now()]);
                abort_unless($affected === 1, 409, 'El intento cambió; no se enviará otra solicitud.');
            } else {
            DB::table('shipping_guide_recovery_attempts')->insert([
                'id' => $attemptId, 'shipping_guide_id' => $guide->id,
                'tenant_id' => $guide->tenant_id, 'company_id' => $guide->company_id, 'branch_id' => $guide->branch_id,
                'user_id' => $userId, 'state' => 'running', 'phase' => 'consult',
                'before_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['guide' => $guide->getRawOriginal(), 'items' => $guide->items->map(function ($r) { return $r->getRawOriginal(); })->all(), 'vehicles' => $guide->vehicles->map(function ($r) { return $r->getRawOriginal(); })->all(), 'drivers' => $guide->drivers->map(function ($r) { return $r->getRawOriginal(); })->all()], JSON_THROW_ON_ERROR)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            }
            return [$guide, $payload, $this->version($guide, $payload, $connection)];
        });
        $snapshot = ShippingGuideConsultation::snapshot($guide);
        $generationStarted = false;
        try {
            $missing = false;
            try {
                $response = $this->provider->consult($guide->serie, $guide->numero, (int) $guide->tipo_de_comprobante, $connection);
            } catch (NubefactGreHttpException $error) {
                $details = $error->diagnostic();
                if ($details['http_status'] !== 400 || $details['provider_code'] !== '24') { throw $error; }
                $missing = true;
            }
            if (!$missing) {
                // Incluso una respuesta incompleta bloquea generación: ausencia no confirmada.
                // El número puede haber sido ocupado manualmente por otra operación.
                // La respuesta de consulta no acredita igualdad de destinatario/items.
                // No importar estado ni documentos solo por coincidencia de serie/número.
                $this->finish($attemptId, 'remote_found');
                return ['ok' => true, 'message' => 'Nubefact respondió para este número. No se envió ni se importaron datos. Compare la guía remota con la local antes de vincularlas.', 'status' => $guide->status];
            }
            if (!$allowGeneration) {
                $this->finish($attemptId, 'missing_checked');
                return ['ok' => true, 'message' => 'Nubefact respondió Documento no existe. No se autorizó ni se realizó ningún envío. Actualice el detalle y, si desea emitir, marque también la autorización de envío.', 'status' => $guide->status];
            }
            list($payload, $snapshot) = DB::transaction(function () use ($guideId, $contentVersion, $connection, $attemptId, $payload) {
                $fresh = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->lockForUpdate()->findOrFail($guideId);
                $fresh->load(['items', 'vehicles', 'drivers']);
                $latestConnection = $this->configs->forCompany((int) $fresh->tenant_id, (int) $fresh->company_id);
                abort_unless($this->eligible($fresh)
                    && hash_equals($this->version($fresh, $this->verifiedPayload($fresh), $latestConnection), $contentVersion), 409,
                    'La guía o la conexión cambiaron durante la consulta. No se enviará.');
                $corrected = $this->correctedPayload($payload);
                foreach ($fresh->vehicles as $vehicle) {
                    $vehicle->update(['plate_number' => GreVehiclePlate::normalize($vehicle->plate_number)]);
                }
                $fresh->update(['last_nubefact_payload' => $corrected]);
                DB::table('shipping_guide_recovery_attempts')->where('id', $attemptId)->update(['phase' => 'generate', 'updated_at' => now()]);
                return [$corrected, ShippingGuideConsultation::snapshot($fresh->fresh())];
            });
            // La marca generate ya está confirmada en BD antes de transmitir. No hay bucle/retry.
            $generationStarted = true;
            $response = $this->provider->sendStoredPayload($payload, $connection);
            $result = ShippingGuideConsultation::apply($guide->id, $snapshot, $response, true);
            $this->finish($attemptId, 'response_applied');
            return ['ok' => true, 'message' => 'Se recibió respuesta del envío de recuperación. Estado: '.$result['guide']->status.'.', 'status' => $result['guide']->status];
        } catch (\Throwable $error) {
            $reference = ShippingGuideDiagnostic::record($error, $generationStarted ? 'generate' : 'consult', $guide);
            $details = $error instanceof NubefactGreHttpException ? $error->diagnostic() : [];
            $this->finish($attemptId, $generationStarted ? 'review_after_send' : 'blocked_before_send', [
                'diagnostic_reference' => $reference,
                'http_status' => $details['http_status'] ?? null, 'provider_code' => $details['provider_code'] ?? null,
            ]);
            return ['ok' => false, 'message' => ($generationStarted
                ? 'El envío de recuperación no quedó confirmado. Conserve la guía y consulte su estado; no se repetirá automáticamente.'
                : 'La recuperación se detuvo antes de enviar. Conserve la guía para revisión.')
                . ($reference ? ' Referencia de diagnóstico: '.$reference : ''), 'diagnostic_reference' => $reference];
        }
    }

    private function eligible(ShippingGuide $guide): bool
    {
        return !ShippingGuideIdentityConflict::blocked($guide) && $guide->status === 'SENT' && !$guide->nubefact_accepted
            && empty($guide->last_nubefact_response) && !$guide->nubefact_enlace
            && !$guide->pdf_link && !$guide->xml_link && !$guide->cdr_link;
    }

    private function verifiedPayload(ShippingGuide $guide): array
    {
        $stored = $guide->last_nubefact_payload;
        $built = $this->provider->buildPayload($guide);
        abort_unless(is_array($stored) && !empty($stored) && $stored == $built, 409,
            'Los datos actuales no coinciden con el envío histórico. Se requiere revisión.');
        $this->correctedPayload($stored);
        return $stored;
    }

    private function correctedPayload(array $payload): array
    {
        $plate = GreVehiclePlate::normalize($payload['transportista_placa_numero'] ?? null);
        abort_unless(is_string($plate) && preg_match('/^[A-Z0-9]{6,8}$/D', $plate), 409, 'La placa histórica requiere revisión manual.');
        $payload['transportista_placa_numero'] = $plate;
        if (isset($payload['vehiculos_secundarios'])) {
            foreach ($payload['vehiculos_secundarios'] as &$vehicle) {
                $plate = GreVehiclePlate::normalize($vehicle['placa_numero'] ?? null);
                abort_unless(is_string($plate) && preg_match('/^[A-Z0-9]{6,8}$/D', $plate), 409, 'Una placa secundaria requiere revisión manual.');
                $vehicle['placa_numero'] = $plate;
            }
            unset($vehicle);
        }
        return $payload;
    }

    private function previewVersion(ShippingGuide $guide, array $payload, NubefactCompanyConnection $connection): string
    {
        $prior = DB::table('shipping_guide_recovery_attempts')->where('shipping_guide_id', $guide->id)->first();
        $state = $prior ? $prior->id.'|'.$prior->state.'|'.$prior->phase : 'new';
        return hash('sha256', $this->version($guide, $payload, $connection).'|'.$state);
    }

    private function version(ShippingGuide $guide, array $payload, NubefactCompanyConnection $connection): string
    {
        // Huella no reversible: no incluye token ni URL en formularios, intentos ni logs.
        $account = hash_hmac('sha256', $connection->url().'|'.$connection->ruc(), $connection->token());
        return hash('sha256', ShippingGuideConsultation::snapshot($guide).'|'.serialize($payload).'|'.$account);
    }

    private function finish(string $id, string $state, array $extra = []): void
    {
        DB::table('shipping_guide_recovery_attempts')->where('id', $id)->update(array_merge($extra, ['state' => $state, 'updated_at' => now()]));
    }
}
