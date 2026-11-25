<?php
namespace App\Services\Materials;

use App\Material;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialRetaceriaPolicy
{
    public function assertChange(Material $material, $request, array $enabled): void
    {
        if (!in_array('typescrap', $enabled, true) || !array_key_exists('typescrap', $request->all())
            || (string) $material->typescrap_id === (string) $request->input('typescrap')) { return; }
        app(MaterialParameters::class)->context();
        abort_unless((int) $material->tenant_id === TenantContext::tenantId()
            && app(MaterialCompanyCatalog::class)->apply(Material::query())->whereKey($material->id)->exists(), 404);
        if (DB::transactionLevel() < 1) { throw new \LogicException('Cambiar retacería requiere la transacción del material.'); }
        $newId = $request->input('typescrap');
        if ($newId !== null && $newId !== '' && !DB::table('typescraps')->where('id', $newId)
            ->where('tenant_id', TenantContext::tenantId())->exists()) {
            $this->invalid('El tipo de retacería no pertenece al tenant actual.');
        }
        // MAT-01 business decision: tenant-wide master changes are allowed even when shared.
        // Tenant ownership, the authorized company catalog and existing Item snapshots remain protected.
        // No Item is changed. The caller applies only the future material type.
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['typescrap' => $message]);
    }
}
