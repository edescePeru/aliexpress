<?php
namespace App\Services;

use App\ShippingGuide;
use Illuminate\Support\Facades\DB;

class ShippingGuideConsultation
{
    public static function snapshot(ShippingGuide $guide)
    {
        return hash('sha256', serialize($guide->getRawOriginal()));
    }

    public static function apply($id, $snapshot, array $response, $emission = false)
    {
        if (!array_key_exists('aceptada_por_sunat', $response) || !is_bool($response['aceptada_por_sunat'])) {
            throw new \UnexpectedValueException('Respuesta sin indicador de aceptación válido.');
        }
        $documents = ['enlace', 'enlace_del_pdf', 'enlace_del_xml', 'enlace_del_cdr', 'pdf_zip_base64', 'xml_zip_base64', 'cdr_zip_base64'];
        foreach (array_merge($documents, ['sunat_soap_error','sunat_description','sunat_note']) as $field) {
            if (isset($response[$field]) && !is_string($response[$field])) { throw new \UnexpectedValueException('Campo de respuesta inválido.'); }
        }
        return DB::transaction(function () use ($id, $snapshot, $response, $documents, $emission) {
            $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->lockForUpdate()->findOrFail($id);
            ShippingGuideIdentityConflict::assertUsable($guide);
            abort_unless(hash_equals(self::snapshot($guide), $snapshot), 409, 'La guía cambió durante la consulta. Actualiza la pantalla y vuelve a consultar.');
            $previous = is_array($guide->last_nubefact_response) ? $guide->last_nubefact_response : [];
            $merged = $response;
            foreach ($documents as $field) {
                if ((!isset($merged[$field]) || $merged[$field] === '') && !empty($previous[$field])) { $merged[$field] = $previous[$field]; }
            }
            foreach (['enlace'=>'nubefact_enlace','enlace_del_pdf'=>'pdf_link','enlace_del_xml'=>'xml_link','enlace_del_cdr'=>'cdr_link'] as $field=>$attribute) {
                if (empty($merged[$field]) && $guide->$attribute) { $merged[$field] = $guide->$attribute; }
            }
            $accepted = $response['aceptada_por_sunat'];
            $error = trim($response['sunat_soap_error'] ?? '');
            // Conserva la clasificación existente; la interpretación de errores no se redefine aquí.
            $responseCode = $response['sunat_responsecode'] ?? null;
            if ($responseCode !== null && !is_string($responseCode) && !is_int($responseCode)) {
                throw new \UnexpectedValueException('Código de respuesta inválido.');
            }
            $hasError = $error !== '' || ($responseCode !== null && trim((string) $responseCode) !== '' && trim((string) $responseCode) !== '0');
            $status = $accepted ? 'ACCEPTED' : ($hasError ? 'REJECTED' : 'PENDING_SUNAT');
            $guide->update([
                'last_nubefact_response'=>$merged, 'nubefact_enlace'=>$merged['enlace'] ?? null,
                'nubefact_accepted'=>$accepted, 'status'=>$status, 'submission_state'=>$status,
                'submission_http_status'=>null, 'submission_provider_code'=>null, 'submission_diagnostic_reference'=>null,
                'sunat_description'=>$response['sunat_description'] ?? null,
                'sunat_note'=>$response['sunat_note'] ?? null,
                'sunat_responsecode'=>$response['sunat_responsecode'] ?? null,
                'sunat_soap_error'=>$error,
                'pdf_link'=>$merged['enlace_del_pdf'] ?? null,
                'xml_link'=>$merged['enlace_del_xml'] ?? null,
                'cdr_link'=>$merged['enlace_del_cdr'] ?? null,
            ]);
            return ['guide'=>$guide->fresh(), 'response'=>$merged, 'accepted'=>$accepted];
        });
    }
}
