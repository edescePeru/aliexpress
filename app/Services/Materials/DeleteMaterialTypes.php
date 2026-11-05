<?php

namespace App\Services\Materials;

use App\MaterialType;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMaterialTypes
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $types = MaterialType::query()->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            if ($types->isEmpty() || $types->count() !== count($ids)) {
                $this->reject($errorField, 'La selección contiene tipos inexistentes, eliminados, repetidos o ajenos al negocio actual.');
            }

            $typeIds = $types->pluck('id')->all();
            // Raw queries include historical subtypes and reveal inconsistent tenant
            // links only to reject them, without modifying or disclosing foreign data.
            $subtypes = DB::table('subtypes')->whereIn('material_type_id', $typeIds)
                ->orderBy('id')->lockForUpdate()->get(['id', 'tenant_id']);

            foreach ($subtypes as $subtype) {
                if ((string) $subtype->tenant_id !== (string) $tenantId) {
                    $this->reject($errorField, 'Existen subtipos con relaciones incompatibles con el negocio actual. Requiere revisión.');
                }
            }

            $subtypeIds = $subtypes->pluck('id')->all();
            if (DB::table('materials')->where(function ($query) use ($typeIds, $subtypeIds) {
                $query->whereIn('material_type_id', $typeIds)
                    ->orWhereIn('subtype_id', $subtypeIds);
            })->exists()) {
                $this->reject($errorField, 'Un tipo o alguno de sus subtipos está referenciado por materiales, incluidos registros históricos.');
            }

            // Exactly one deletion path for children: the existing MaterialType model
            // cascade. Do not manually delete the subtypes again in this operation.
            foreach ($types as $type) {
                if ($type->delete() === false) {
                    throw new \RuntimeException('No se pudo eliminar uno de los tipos de material.');
                }
            }

            // The installed cascade trait ignores a child's false delete result.
            // Detect an incomplete cascade before commit and roll back the entire batch.
            if (DB::table('subtypes')->whereIn('material_type_id', $typeIds)->whereNull('deleted_at')->exists()) {
                throw new \RuntimeException('No se pudo completar la eliminación de los subtipos. No se eliminó ningún tipo de material.');
            }

            return $types->count();
        });
    }

    private function reject(string $field, string $message): void
    {
        throw ValidationException::withMessages([
            $field => $message.' No se eliminó ningún tipo ni subtipo.',
        ]);
    }
}
