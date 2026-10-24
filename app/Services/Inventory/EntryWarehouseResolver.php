<?php

namespace App\Services\Inventory;

use App\Location;
use App\Branch;
use App\Warehouse;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntryWarehouseResolver
{
    /** Catálogo de destino: no mueve stock ni crea infraestructura. */
    public function optionsForCurrentContext(): array
    {
        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();
        $warehouses = $this->allowedWarehouses()
            ->with(['locations' => function ($query) use ($tenantId, $companyId) {
                $query->where('tenant_id', $tenantId)->where('company_id', $companyId);
            }])->orderBy('name')->orderBy('id')->get();

        $defaults = $warehouses->filter(function ($warehouse) {
            return $warehouse->branch_id === null && (bool) $warehouse->is_default;
        });
        $errors = [];
        $defaultId = null;
        if ($defaults->count() !== 1) {
            $errors[] = 'Debe existir un único almacén general predeterminado para la empresa.';
        } elseif ($defaults->first()->locations->count() !== 1) {
            $errors[] = 'El almacén general predeterminado debe tener exactamente una ubicación operativa.';
        } else {
            $defaultId = (int) $defaults->first()->id;
        }

        if (Warehouse::query()->where('tenant_id', $tenantId)->where('company_id', $companyId)
            ->whereNotNull('branch_id')->where('is_default', true)->exists()) {
            $errors[] = 'Los almacenes locales no pueden ser predeterminados generales.';
            $defaultId = null;
        }
        $branch = Branch::query()->where('company_id', $companyId)->find(TenantContext::branchId());
        if (!$branch) {
            $errors[] = 'La sucursal del contexto no pertenece a la empresa actual.';
            $defaultId = null;
        } elseif ($branch->default_entry_warehouse_id) {
            $preferred = $warehouses->first(function ($warehouse) use ($branch) {
                return (int) $warehouse->id === (int) $branch->default_entry_warehouse_id
                    && $warehouse->branch_id !== null && (int) $warehouse->branch_id === (int) $branch->id;
            });
            if (!$preferred || $preferred->locations->count() !== 1) {
                $errors[] = 'El destino habitual del local no es válido. Revise sus almacenes desde Editar sucursal.';
                $defaultId = null;
            } elseif (!$errors) {
                $defaultId = (int) $preferred->id;
            }
        }

        $options = [];
        foreach ($warehouses as $warehouse) {
            $valid = $warehouse->locations->count() === 1;
            $location = $valid ? $warehouse->locations->first() : null;
            $options[] = [
                'id' => (int) $warehouse->id,
                'name' => (string) $warehouse->name,
                'branch_id' => $warehouse->branch_id === null ? null : (int) $warehouse->branch_id,
                'is_general' => $warehouse->branch_id === null,
                'selectable' => $valid,
                'location_id' => $location ? (int) $location->id : null,
                'configuration_error' => $valid ? null
                    : 'Este almacén debe tener exactamente una ubicación operativa.',
            ];
        }
        return ['warehouses' => $options, 'default_warehouse_id' => $defaultId,
            'configuration_errors' => $errors];
    }

    /** Al guardar, usar lock=true DENTRO de la transacción del ingreso. */
    public function resolveForEntry(int $warehouseId, bool $lock = false): array
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new \LogicException('La validación bloqueada debe ejecutarse dentro de la transacción del ingreso.');
        }
        $query = $this->allowedWarehouses()->where('id', $warehouseId);
        if ($lock) { $query->lockForUpdate(); }
        $warehouse = $query->first();
        if (!$warehouse) {
            $this->invalid('El almacén no está permitido para la empresa y sucursal actuales.');
        }

        $query = Location::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->where('warehouse_id', $warehouse->id)->orderBy('id')->limit(2);
        if ($lock) { $query->lockForUpdate(); }
        $locations = $query->get();
        if ($locations->count() !== 1) {
            $this->invalid('El almacén seleccionado debe tener exactamente una ubicación operativa.');
        }
        return ['warehouse' => $warehouse, 'location' => $locations->first()];
    }

    private function allowedWarehouses()
    {
        $branchId = TenantContext::branchId();
        return Warehouse::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['warehouse_id' => $message]);
    }
}
