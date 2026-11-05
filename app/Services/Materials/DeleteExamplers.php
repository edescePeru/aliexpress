<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Exampler;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteExamplers
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $examplers = Exampler::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($examplers->isEmpty() || $examplers->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene modelos inexistentes, duplicados o ajenos al negocio actual. No se eliminó ningún modelo.',
                ]);
            }

            $selectedIds = $examplers->pluck('id')->all();

            // Include Material references even when their records are soft-deleted.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials'] as $table) {
                if (DB::table($table)->whereIn('exampler_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Uno o más modelos están referenciados por materiales, incluidos registros históricos. No se eliminó ningún modelo.',
                    ]);
                }
            }

            foreach ($examplers as $exampler) {
                if ($exampler->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de modelos.');
                }
            }

            return $examplers->count();
        });
    }
}
