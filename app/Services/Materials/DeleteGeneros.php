<?php

namespace App\Services\Materials;

use App\Support\TenantContext;
use App\Genero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteGeneros
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $generos = Genero::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Recheck the complete selection inside the transaction: never silently skip IDs.
            if ($generos->isEmpty() || $generos->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    $errorField => 'La selección contiene géneros inexistentes, eliminados, duplicados o ajenos al negocio actual. No se eliminó ningún género.',
                ]);
            }

            $selectedIds = $generos->pluck('id')->all();

            // Query Builder intentionally includes soft-deleted historical references.
            // Even an inconsistent reference from another tenant prevents deleting its target;
            // responses disclose no referenced records, names or foreign context.
            foreach (['materials'] as $table) {
                if (DB::table($table)->whereIn('genero_id', $selectedIds)->exists()) {
                    throw ValidationException::withMessages([
                        $errorField => 'Uno o más géneros están referenciados por materiales, incluidos registros históricos. No se eliminó ningún género.',
                    ]);
                }
            }

            foreach ($generos as $genero) {
                if ($genero->delete() === false) {
                    throw new \RuntimeException('No se pudo completar la eliminación de géneros.');
                }
            }

            return $generos->count();
        });
    }
}
