<?php
namespace App\Services;

use App\ShippingGuide;
use App\Support\TenantContext;

class ShippingGuideEmission
{
    public static function existing($key)
    {
        $guide = ShippingGuide::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('emission_key', $key)->first();
        if ($guide) {
            abort_unless((int) $guide->branch_id === (int) TenantContext::branchId()
                && (int) $guide->tipo_de_comprobante === 7, 409, 'La operación no corresponde al local actual.');
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
            'data' => $guide,
        ]);
    }
}
