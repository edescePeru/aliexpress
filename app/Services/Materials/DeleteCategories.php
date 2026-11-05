<?php

namespace App\Services\Materials;

use App\Category;
use App\MaterialType;
use App\Subcategory;
use App\Subtype;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategories
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $categories = Category::query()->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            if ($categories->isEmpty() || $categories->count() !== count($ids)) {
                $this->reject($errorField, 'La selección contiene categorías inexistentes, eliminadas, repetidas o ajenas al negocio actual.');
            }

            $categoryIds = $categories->pluck('id')->all();
            $subcategoryIds = $this->descendants('subcategories', 'category_id', $categoryIds, $tenantId, $errorField);
            $typeIds = $this->descendants('material_types', 'subcategory_id', $subcategoryIds, $tenantId, $errorField);
            $subtypeIds = $this->descendants('subtypes', 'material_type_id', $typeIds, $tenantId, $errorField);

            // Query Builder includes soft-deleted materials and references at any depth,
            // even when the material does not carry its ancestor category/subcategory IDs.
            $used = DB::table('materials')->where(function ($query) use ($categoryIds, $subcategoryIds, $typeIds, $subtypeIds) {
                $query->whereIn('category_id', $categoryIds)
                    ->orWhereIn('subcategory_id', $subcategoryIds)
                    ->orWhereIn('material_type_id', $typeIds)
                    ->orWhereIn('subtype_id', $subtypeIds);
            })->exists();

            if ($used) {
                $this->reject($errorField, 'Una categoría o alguno de sus niveles está referenciado por materiales, incluidos registros históricos.');
            }

            // An additional direct reference identified in the existing migrations.
            if (DB::table('seasonal_promotions')->whereIn('category_id', $categoryIds)->exists()) {
                $this->reject($errorField, 'Una o más categorías están referenciadas por promociones.');
            }

            // Explicit bottom-up deletes check each result; the existing cascade trait
            // alone does not propagate a false result from a child's deleting event.
            foreach ([Subtype::class => $subtypeIds, MaterialType::class => $typeIds, Subcategory::class => $subcategoryIds] as $model => $selected) {
                $records = $model::query()->where('tenant_id', $tenantId)
                    ->whereIn('id', $selected)->orderBy('id')->lockForUpdate()->get();
                foreach ($records as $record) {
                    if ($record->delete() === false) {
                        throw new \RuntimeException('No se pudo eliminar uno de los niveles de la categoría.');
                    }
                }
            }

            foreach ($categories as $category) {
                if ($category->delete() === false) {
                    throw new \RuntimeException('No se pudo eliminar una de las categorías.');
                }
            }

            return $categories->count();
        });
    }

    private function descendants(string $table, string $parentKey, array $parents, $tenantId, string $errorField): array
    {
        if (!$parents) {
            return [];
        }

        // Include historical descendants. Inspect foreign-tenant links only to reject
        // corrupt trees; never modify foreign records or disclose their identity.
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
            $field => $message.' No se eliminó ninguna categoría ni nivel relacionado.',
        ]);
    }
}
