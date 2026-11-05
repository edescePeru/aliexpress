<?php

namespace App\Services\Materials;

use App\Brand;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteBrands
{
    public function delete(array $ids, string $errorField = 'ids'): int
    {
        $tenantId = TenantContext::tenantId();

        return DB::transaction(function () use ($ids, $tenantId, $errorField) {
            $brands = Brand::query()->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            if ($brands->isEmpty() || $brands->count() !== count($ids)) {
                $this->reject($errorField, 'La selección contiene marcas inexistentes, eliminadas, repetidas o ajenas al negocio actual.');
            }

            $brandIds = $brands->pluck('id')->all();
            // Raw queries inspect all models and reveal inconsistent tenant
            // links only to reject them, without modifying or disclosing foreign data.
            $examplers = DB::table('examplers')->whereIn('brand_id', $brandIds)
                ->orderBy('id')->lockForUpdate()->get(['id', 'tenant_id']);

            foreach ($examplers as $exampler) {
                if ((string) $exampler->tenant_id !== (string) $tenantId) {
                    $this->reject($errorField, 'Existen modelos con relaciones incompatibles con el negocio actual. Requiere revisión.');
                }
            }

            $examplerIds = $examplers->pluck('id')->all();
            if (DB::table('materials')->where(function ($query) use ($brandIds, $examplerIds) {
                $query->whereIn('brand_id', $brandIds)
                    ->orWhereIn('exampler_id', $examplerIds);
            })->exists()) {
                $this->reject($errorField, 'Una marca o alguno de sus modelos está referenciado por materiales, incluidos registros históricos.');
            }

            // Brand is soft-deleted; Exampler has no SoftDeletes. Exactly one deletion path: the Brand model
            // cascade. Do not manually delete the examplers again in this operation.
            foreach ($brands as $brand) {
                if ($brand->delete() === false) {
                    throw new \RuntimeException('No se pudo eliminar una de las marcas.');
                }
            }

            // The installed cascade trait ignores a child's false delete result.
            // Detect an incomplete cascade before commit and roll back the entire batch.
            if (DB::table('examplers')->whereIn('brand_id', $brandIds)->exists()) {
                throw new \RuntimeException('No se pudo completar la eliminación de los modelos. No se eliminó ninguna marca.');
            }

            return $brands->count();
        });
    }

    private function reject(string $field, string $message): void
    {
        throw ValidationException::withMessages([
            $field => $message.' No se eliminó ninguna marca ni modelo.',
        ]);
    }
}
