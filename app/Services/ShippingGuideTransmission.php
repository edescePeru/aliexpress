<?php
namespace App\Services;

use App\ShippingGuide;
use Illuminate\Support\Facades\DB;

class ShippingGuideTransmission
{
    public static function state(ShippingGuide $guide): string
    {
        if (ShippingGuideIdentityConflict::blocked($guide)) { return 'IDENTITY_CONFLICT'; }
        $known = ['REGISTERED','UNKNOWN','API_ERROR','PENDING_SUNAT','ACCEPTED','REJECTED'];
        if (in_array($guide->submission_state, $known, true)) { return $guide->submission_state; }
        if (in_array($guide->status, ['ACCEPTED','REJECTED','PENDING_SUNAT'], true)) { return $guide->status; }
        return $guide->status === 'DRAFT' ? 'REGISTERED' : 'UNKNOWN';
    }

    public static function label(ShippingGuide $guide): string
    {
        return [
            'REGISTERED'=>'REGISTRADA, ENVÍO NO CONFIRMADO', 'UNKNOWN'=>'RESULTADO EXTERNO POR CONFIRMAR',
            'API_ERROR'=>'ERROR INFORMADO POR NUBEFACT', 'PENDING_SUNAT'=>'PENDIENTE SUNAT',
            'ACCEPTED'=>'ACEPTADA', 'REJECTED'=>'RECHAZADA POR SUNAT', 'IDENTITY_CONFLICT'=>'CONFLICTO DE NUMERACIÓN',
        ][self::state($guide)];
    }

    public static function begin($id, string $snapshot): ShippingGuide
    {
        return DB::transaction(function () use ($id, $snapshot) {
            $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante',7)->lockForUpdate()->findOrFail($id);
            ShippingGuideIdentityConflict::assertUsable($guide);
            abort_unless(hash_equals(ShippingGuideConsultation::snapshot($guide),$snapshot),409,'La guía cambió antes del envío. Consulte su estado.');
            $guide->forceFill(['status'=>'SENT','submission_state'=>'UNKNOWN','submission_http_status'=>null,
                'submission_provider_code'=>null,'submission_diagnostic_reference'=>null])->save();
            return $guide->fresh();
        });
    }

    public static function failure($id, string $snapshot, \Throwable $error, $reference): bool
    {
        return DB::transaction(function () use ($id,$snapshot,$error,$reference) {
            $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante',7)->lockForUpdate()->findOrFail($id);
            // Una respuesta más nueva o un aislamiento prevalecen sobre el error tardío.
            if (ShippingGuideIdentityConflict::blocked($guide) || !hash_equals(ShippingGuideConsultation::snapshot($guide),$snapshot)) { return false; }
            $details = $error instanceof NubefactGreHttpException ? $error->diagnostic() : [];
            $http = $details['http_status'] ?? null; $code = $details['provider_code'] ?? null;
            $apiError = $http !== null && $http >= 400 && $http < 500 && $code !== '23';
            $guide->forceFill([
                'status'=>$apiError ? 'ERROR' : 'SENT', 'submission_state'=>$apiError ? 'API_ERROR' : 'UNKNOWN',
                'submission_http_status'=>$http,'submission_provider_code'=>$code,'submission_diagnostic_reference'=>$reference,
            ])->save();
            return true;
        });
    }
}
