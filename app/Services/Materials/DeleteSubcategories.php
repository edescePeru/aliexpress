<?php

namespace App\Services\Materials;

use App\MaterialType;
use App\Subcategory;
use App\Subtype;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSubcategories
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $subcategories = Subcategory::query()->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            if ($subcategories->isEmpty() || $subcategories->count() !== count($ids)) {
                $this->reject($errorField, 'La selección contiene subcategorías inexistentes, eliminadas, repetidas o ajenas al negocio actual.');
            }

            $subcategoryIds = $subcategories->pluck('id')->all();
            $typeIds = $this->descendants('material_types', 'subcategory_id', $subcategoryIds, $tenantId, $errorField);
            $subtypeIds = $this->descendants('subtypes', 'material_type_id', $typeIds, $tenantId, $errorField);

            // Include historical materials and references carried only by a deeper level.
            if (DB::table('materials')->where(function ($query) use ($subcategoryIds, $typeIds, $subtypeIds) {
                $query->whereIn('subcategory_id', $subcategoryIds)
                    ->orWhereIn('material_type_id', $typeIds)
                    ->orWhereIn('subtype_id', $subtypeIds);
            })->exists()) {
                $this->reject($errorField, 'Una subcategoría, tipo o subtipo está referenciado por materiales, incluidos registros históricos.');
            }

            // Delete active descendants bottom-up and inspect every result. Keep the
            // original deletion timestamp of descendants that were already soft-deleted.
            foreach ([Subtype::class => $subtypeIds, MaterialType::class => $typeIds] as $model => $selected) {
                $records = $model::query()->where('tenant_id', $tenantId)
                    ->whereIn('id', $selected)->orderBy('id')->lockForUpdate()->get();
                foreach ($records as $record) {
                    if ($record->delete() === false) {
                        throw new \RuntimeException('No se pudo eliminar un nivel de la subcategoría.');
                    }
                }
            }

            foreach ($subcategories as $subcategory) {
                if ($subcategory->delete() === false) {
                    throw new \RuntimeException('No se pudo eliminar una de las subcategorías.');
                }
            }

            return $subcategories->count();
        });
    }

    private function descendants(string $table, string $parentKey, array $parents, $tenantId, string $errorField): array
    {
        if (!$parents) {
            return [];
        }

        // Inspect all descendants including soft-deleted ones. Cross-tenant links cause
        // rejection, never deletion of foreign data or disclosure of its identity.
        $records = DB::table($table)->whereIn($parentKey, $parents)
            ->orderBy('id')->lockForUpdate()->get(['id', 'tenant_id']);

        foreach ($records as $record) {
            if ((string) $record->tenant_id !== (string) $tenantId) {
                $this->reject($errorField, 'La jerarquía contiene relaciones incompatibles con el negocio actual. Requiere revisión.');
            }
        }

        return $records->pluck('id')->all();
    }

    private function reject(string $field, string $message): void
    {
        throw ValidationException::withMessages([
            $field => $message.' No se eliminó ninguna subcategoría ni nivel relacionado.',
        ]);
    }
}
