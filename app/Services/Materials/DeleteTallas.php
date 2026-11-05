<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Talla;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTallas
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $tallas = Talla::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($tallas->isEmpty() || $tallas->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene tallas inexistentes, eliminadas, duplicadas o ajenas al negocio actual. No se eliminó ninguna talla.',
                ]);
            }

            $selectedIds = $tallas->pluck('id')->all();

            // Query Builder intentionally includes soft-deleted historical references.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials', 'variants'] as $table) {
                if (DB::table($table)->whereIn('talla_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Una o más tallas están referenciadas por materiales o variantes, incluidos registros históricos. No se eliminó ninguna talla.',
                    ]);
                }
            }

            foreach ($tallas as $talla) {
                if ($talla->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de tallas.');
                }
            }

            return $tallas->count();
        });
    }
}
