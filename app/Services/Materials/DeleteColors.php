<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteColors
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $colors = Color::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($colors->isEmpty() || $colors->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene colores inexistentes, duplicados o ajenos al negocio actual. No se eliminó ningún color.',
                ]);
            }

            $selectedIds = $colors->pluck('id')->all();

            // Check all variant references, without filtering active state or parent Material.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['variants'] as $table) {
                if (DB::table($table)->whereIn('color_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Uno o más colores están referenciados por variantes, incluidas las inactivas. No se eliminó ningún color.',
                    ]);
                }
            }

            foreach ($colors as $color) {
                if ($color->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de colores.');
                }
            }

            return $colors->count();
        });
    }
}
