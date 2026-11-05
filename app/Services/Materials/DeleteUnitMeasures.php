<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\UnitMeasure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUnitMeasures
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $units = UnitMeasure::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($units->isEmpty() || $units->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene unidades inexistentes, eliminadas, duplicadas o ajenas al negocio actual. No se eliminó ninguna unidad.',
                ]);
            }

            $selectedIds = $units->pluck('id')->all();

            // Query Builder intentionally includes soft-deleted historical references.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials', 'stock_items', 'workforces'] as $table) {
                if (DB::table($table)->whereIn('unit_measure_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Una o más unidades están referenciadas por materiales, productos o mano de obra, incluidos registros históricos. No se eliminó ninguna unidad.',
                    ]);
                }
            }

            foreach ($units as $unit) {
                if ($unit->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de unidades.');
                }
            }

            return $units->count();
        });
    }
}
