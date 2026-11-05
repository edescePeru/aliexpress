<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Subtype;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSubtypes
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $subtypes = Subtype::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($subtypes->isEmpty() || $subtypes->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene subtipos inexistentes, eliminados, duplicados o ajenos al negocio actual. No se eliminó ningún subtipo.',
                ]);
            }

            $selectedIds = $subtypes->pluck('id')->all();

            // Query Builder intentionally includes soft-deleted historical references.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials'] as $table) {
                if (DB::table($table)->whereIn('subtype_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Uno o más subtipos están referenciados por materiales, incluidos registros históricos. No se eliminó ningún subtipo.',
                    ]);
                }
            }

            foreach ($subtypes as $subtype) {
                if ($subtype->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de subtipos.');
                }
            }

            return $subtypes->count();
        });
    }
}
