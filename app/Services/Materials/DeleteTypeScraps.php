<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Typescrap;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTypeScraps
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $typescraps = Typescrap::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($typescraps->isEmpty() || $typescraps->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene tipos de retacería inexistentes, eliminados, duplicados o ajenos al negocio actual. No se eliminó ningún tipo de retacería.',
                ]);
            }

            $selectedIds = $typescraps->pluck('id')->all();

            // Query Builder intentionally includes soft-deleted historical references.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials', 'items'] as $table) {
                if (DB::table($table)->whereIn('typescrap_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Uno o más tipos de retacería están referenciados por materiales o Items, incluidos registros históricos. No se eliminó ningún tipo de retacería.',
                    ]);
                }
            }

            foreach ($typescraps as $typeScrap) {
                if ($typeScrap->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de tipos de retacería.');
                }
            }

            return $typescraps->count();
        });
    }
}
