<?php

namespace App\Services\Inventory;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntryDestinationValidator
{
    public function normalize($json, $formCompanyId, $formBranchId): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Valide los destinos dentro de la transacción del ingreso.');
        }
        if ($this->positiveId($formCompanyId) !== TenantContext::companyId()
            || $this->positiveId($formBranchId) !== TenantContext::branchId()) {
            $this->invalid('La empresa o el local cambió desde que abrió el formulario. Abra un nuevo ingreso en el contexto correcto.');
        }
        if (!is_string($json)) { $this->invalid('El formato de los productos no es válido.'); }
        $items = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || !$items) {
            $this->invalid('Agregue al menos un producto válido al ingreso.');
        }

        $ids = [];
        foreach ($items as $item) {
            if (!is_object($item)) { $this->invalid('El formato de uno de los productos no es válido.'); }
            $warehouseId = $this->positiveId($item->warehouse_id ?? null);
            $locationId = $this->positiveId($item->id_location ?? null);
            if (!$warehouseId || !$locationId) {
                $this->invalid('Cada producto debe tener un almacén y una ubicación válidos. Vuelva a agregar los productos desde el formulario actualizado.');
            }
            $ids[$warehouseId] = $warehouseId;
        }
        // Orden estable para ingresos que contengan varios almacenes.
        sort($ids, SORT_NUMERIC);
        $resolver = new EntryWarehouseResolver();
        $destinations = [];
        foreach ($ids as $id) { $destinations[$id] = $resolver->resolveForEntry($id, true); }
        foreach ($items as $item) {
            $destination = $destinations[(int) $item->warehouse_id];
            $locationId = (int) $destination['location']->id;
            if ((int) $item->id_location !== $locationId) {
                $this->invalid('La ubicación enviada no corresponde al almacén seleccionado. Revise el destino del producto.');
            }
            $item->warehouse_id = (int) $destination['warehouse']->id;
            $item->id_location = $locationId;
        }
        return $items;
    }

    private function positiveId($value)
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]*$/D', (string) $value)) { return null; }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private function invalid($message): void
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
