<?php
namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Http\Request;

class ShippingGuideRequestFingerprint
{
    public static function make(Request $request): string
    {
        $fields = [
            'serie','fecha_emision','fecha_inicio_traslado','motivo_traslado_code','tipo_transporte',
            'peso_bruto_total','peso_bruto_um_code','numero_bultos','sunat_shipping_indicator_code',
            'partida_ubigeo','partida_direccion','partida_cod_establecimiento',
            'llegada_ubigeo','llegada_direccion','llegada_cod_establecimiento',
            'customer_id','customer_doc_type','customer_doc_number','customer_address',
            'customer_email','customer_email_1','customer_email_2','observaciones',
            'items_mode','sale_id','items','vehicle_primary_plate','vehicles_secondary',
            'driver_primary','drivers_secondary','transportista_doc_type','transportista_doc_number',
            'transportista_name','mtc_registration_number',
        ];
        $input = [];
        foreach ($fields as $field) { $input[$field] = self::canonical($request->input($field)); }
        return hash('sha256', json_encode([
            'version' => 1, 'context' => [TenantContext::tenantId(), TenantContext::companyId(), TenantContext::branchId()],
            'input' => $input,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private static function canonical($value)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) { $result[$key] = self::canonical($item); }
            // El orden de filas de items/vehículos es significativo; el de claves de objetos no.
            if (array_keys($result) !== range(0, count($result) - 1)) { ksort($result, SORT_STRING); }
            return $result;
        }
        if ($value === null) { return null; }
        abort_unless(is_scalar($value), 422, 'Formato de solicitud inválido.');
        if (is_bool($value)) { return $value ? '1' : '0'; }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
