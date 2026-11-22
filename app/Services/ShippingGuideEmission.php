<?php
namespace App\Services;

use App\ShippingGuide;
use App\Support\TenantContext;

class ShippingGuideEmission
{
    public static function existing($key, string $fingerprint)
    {
        $guide = ShippingGuide::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('emission_key', $key)->first();
        if ($guide) {
            abort_unless((int) $guide->branch_id === (int) TenantContext::branchId()
                && (int) $guide->tipo_de_comprobante === 7, 409, 'La operación no corresponde al local actual.');
            abort_unless(is_string($guide->emission_fingerprint) && $guide->emission_fingerprint !== '', 409,
                'La operación es anterior a la huella de contenido. Revise la guía existente; no se repetirá el envío.');
            abort_unless(hash_equals($guide->emission_fingerprint, $fingerprint), 409,
                'La misma clave de operación se usó con datos distintos. No se guardó ni envió otra guía.');
        }
        return $guide;
    }

    public static function replay(ShippingGuide $guide)
    {
        return response()->json([
            'message' => 'Esta operación ya tiene una guía guardada. Revise su estado en el listado. Los cambios posteriores del formulario no se aplicaron.',
            'persisted' => true,
            'replayed' => true,
            'status' => $guide->status,
            'emission_state' => $guide->emission_state,
            'data' => $guide,
        ]);
    }
}
