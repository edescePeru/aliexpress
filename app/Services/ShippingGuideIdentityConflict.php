<?php
namespace App\Services;

use App\ShippingGuide;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ShippingGuideIdentityConflict
{
    public static function blocked($guide): bool
    {
        return !empty($guide->identity_conflict_at);
    }

    public static function assertUsable($guide): void
    {
        abort_if(self::blocked($guide), 409,
            'Conflicto de numeración: la respuesta y los documentos remotos corresponden a otra operación. Esta guía está bloqueada.');
    }

    public static function inspect(array $identity): ShippingGuide
    {
        foreach (['guide_id', 'tenant_id', 'company_id', 'branch_id'] as $key) {
            if (!isset($identity[$key]) || !ctype_digit((string) $identity[$key]) || (int) $identity[$key] < 1) {
                throw new \InvalidArgumentException('Identificadores de contexto inválidos.');
            }
        }
        if (!preg_match('/^T[A-Z0-9]{3}$/D', (string) ($identity['serie'] ?? ''))
            || !preg_match('/^[1-9][0-9]{0,7}$/D', (string) ($identity['numero'] ?? ''))
            || !preg_match('/^[0-9]{8,15}$/D', (string) ($identity['customer_doc_number'] ?? ''))) {
            throw new \InvalidArgumentException('La identidad esperada de la guía es inválida.');
        }
        return self::query($identity)->firstOrFail();
    }

    public static function quarantine(array $identity, string $reason): bool
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 15 || mb_strlen($reason) > 500) {
            throw new \InvalidArgumentException('Indique un motivo de 15 a 500 caracteres, sin credenciales.');
        }
        self::inspect($identity);
        return DB::transaction(function () use ($identity, $reason) {
            $guide = self::query($identity)->lockForUpdate()->firstOrFail();
            if (self::blocked($guide)) {
                abort_unless(DB::table('shipping_guide_identity_conflicts')->where('shipping_guide_id', $guide->id)->exists(), 409,
                    'La guía está marcada sin evidencia archivada. Revisar antes de continuar.');
                return false;
            }
            // Copia cifrada ANTES de retirar la atribución errónea del estado/documentos.
            // Si falla cifrado/insert/update, toda la transacción se revierte.
            $snapshot = [
                'guide' => $guide->getRawOriginal(),
                'items' => $guide->items()->get()->map(function ($row) { return $row->getRawOriginal(); })->all(),
                'vehicles' => $guide->vehicles()->get()->map(function ($row) { return $row->getRawOriginal(); })->all(),
                'drivers' => $guide->drivers()->get()->map(function ($row) { return $row->getRawOriginal(); })->all(),
            ];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            DB::table('shipping_guide_identity_conflicts')->insert([
                'shipping_guide_id' => $guide->id, 'tenant_id' => $guide->tenant_id,
                'company_id' => $guide->company_id, 'branch_id' => $guide->branch_id,
                'reason' => $reason, 'evidence_encrypted' => Crypt::encryptString($json),
                'evidence_sha256' => hash('sha256', $json), 'recorded_by' => 'console', 'created_at' => now(),
            ]);
            $guide->forceFill([
                'identity_conflict_at' => now(), 'status' => 'ERROR', 'nubefact_accepted' => false,
                'last_nubefact_response' => null, 'nubefact_enlace' => null,
                'pdf_link' => null, 'xml_link' => null, 'cdr_link' => null,
                'sunat_description' => 'CONFLICTO DE NUMERACIÓN: respuesta remota no atribuible a esta operación local.',
                'sunat_note' => null, 'sunat_responsecode' => null, 'sunat_soap_error' => null,
            ])->save();
            return true;
        });
    }

    private static function query(array $identity)
    {
        return ShippingGuide::query()->whereKey((int) $identity['guide_id'])
            ->where('tenant_id', (int) $identity['tenant_id'])->where('company_id', (int) $identity['company_id'])
            ->where('branch_id', (int) $identity['branch_id'])->where('tipo_de_comprobante', 7)
            ->where('serie', $identity['serie'])->where('numero', $identity['numero'])
            ->where('customer_doc_number', $identity['customer_doc_number']);
    }
}
