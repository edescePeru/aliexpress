<?php

namespace App\Services\Inventory;

use App\{Area, Branch, Company, Container, Level, Location, Position, Shelf, Warehouse};
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagedWarehouseService
{
    public function company($id, $lock = false)
    {
        $query = Company::query()->where('tenant_id', TenantContext::tenantId());
        if ($lock) { $query->lockForUpdate(); }
        return $query->findOrFail($id);
    }

    public function branch(Company $company, $id, $lock = false)
    {
        if (!$id) { return null; }
        $query = Branch::query()->where('company_id', $company->id);
        if ($lock) { $query->lockForUpdate(); }
        return $query->findOrFail($id);
    }

    private function warehouses(Company $company, $branch)
    {
        $query = Warehouse::query()->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id);
        return $branch ? $query->where('branch_id', $branch->id) : $query->whereNull('branch_id');
    }

    private function locations(Company $company, Warehouse $warehouse)
    {
        return Location::query()->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)->where('warehouse_id', $warehouse->id);
    }

    private function uniqueLocation(Company $company, Warehouse $warehouse)
    {
        if ($this->locations($company, $warehouse)->count() !== 1) {
            $this->invalid('El almacén debe tener exactamente una ubicación operativa.');
        }
    }

    public function state($companyId, $branchId = null): array
    {
        $company = $this->company($companyId);
        $branch = $this->branch($company, $branchId);
        $rows = $this->warehouses($company, $branch)->orderBy('name')->get()->map(function ($w) use ($company) {
            return ['id' => (int) $w->id, 'name' => $w->name, 'comment' => $w->comment,
                'is_default' => (bool) $w->is_default,
                'locations_count' => $this->locations($company, $w)->count()];
        });
        $defaults = Warehouse::query()->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)->where('is_default', true)->get();
        $validDefault = $defaults->count() === 1 && $defaults->first()->branch_id === null;
        return ['warehouses' => $rows, 'is_branch' => (bool) $branch,
            'default_entry_warehouse_id' => $branch && $branch->default_entry_warehouse_id
                ? (int) $branch->default_entry_warehouse_id : null,
            'configuration_error' => $validDefault ? null : 'Configure un único almacén general predeterminado.'];
    }

    /** Todas las escrituras del mantenedor se serializan por empresa. */
    public function save($companyId, $branchId, array $data): void
    {
        DB::transaction(function () use ($companyId, $branchId, $data) {
            $company = $this->company($companyId, true);
            $branch = $this->branch($company, $branchId, true);
            if ($data['action'] === 'preference') {
                abort_unless($branch, 404);
                $id = $data['warehouse_id'] ?? null;
                if ($id) {
                    $warehouse = $this->warehouses($company, $branch)->lockForUpdate()->findOrFail($id);
                    $this->uniqueLocation($company, $warehouse);
                }
                $branch->default_entry_warehouse_id = $id ?: null;
                $branch->save();
                return;
            }
            if ($data['action'] === 'default') {
                abort_if($branch, 404);
                $warehouse = $this->warehouses($company, null)->lockForUpdate()->findOrFail($data['warehouse_id']);
                $this->uniqueLocation($company, $warehouse);
                // Compatibilidad con DefaultInventoryLocationResolver.
                $this->locations($company, $warehouse)->update(['default' => true]);
                Warehouse::query()->where('tenant_id', $company->tenant_id)
                    ->where('company_id', $company->id)->where('is_default', true)->update(['is_default' => false]);
                $warehouse->refresh();
                $warehouse->is_default = true;
                $warehouse->save();
                return;
            }

            $name = trim($data['name']);
            if ($name === '') { $this->invalid('Ingrese el nombre del almacén.'); }
            $id = $data['warehouse_id'] ?? null;
            $duplicate = $this->warehouses($company, $branch)->where('name', $name);
            if ($id) { $duplicate->where('id', '<>', $id); }
            if ($duplicate->exists()) { $this->invalid('Ya existe un almacén con ese nombre en este ámbito.'); }
            if ($id) {
                $warehouse = $this->warehouses($company, $branch)->lockForUpdate()->findOrFail($id);
                $warehouse->name = $name;
                $warehouse->comment = $data['comment'] ?? null;
                $warehouse->save();
                return;
            }

            // La creación normal de empresas ya asegura el general predeterminado.
            // Si hay datos históricos inconsistentes, se corrigen explícitamente desde Empresa.
            $defaults = Warehouse::query()->where('tenant_id', $company->tenant_id)
                ->where('company_id', $company->id)->where('is_default', true)->get();
            if ($defaults->count() !== 1 || $defaults->first()->branch_id !== null) {
                $this->invalid('Primero configure un único almacén general predeterminado desde Editar empresa.');
            }
            $area = Area::query()->firstOrCreate(['tenant_id' => $company->tenant_id,
                'company_id' => $company->id, 'name' => 'GENERAL'], ['comment' => 'Área técnica de almacenes.']);
            $warehouse = Warehouse::create(['tenant_id' => $company->tenant_id, 'company_id' => $company->id,
                'branch_id' => $branch ? $branch->id : null, 'area_id' => $area->id,
                'name' => $name, 'comment' => $data['comment'] ?? null, 'is_default' => false]);
            $shelf = Shelf::create(['tenant_id' => $company->tenant_id, 'warehouse_id' => $warehouse->id, 'name' => 'GENERAL']);
            $level = Level::create(['tenant_id' => $company->tenant_id, 'shelf_id' => $shelf->id, 'name' => 'GENERAL']);
            $container = Container::create(['tenant_id' => $company->tenant_id, 'level_id' => $level->id, 'name' => 'GENERAL']);
            $position = Position::create(['tenant_id' => $company->tenant_id, 'container_id' => $container->id,
                'name' => 'GENERAL', 'status' => 'active']);
            Location::create(['tenant_id' => $company->tenant_id, 'company_id' => $company->id,
                'area_id' => $area->id, 'warehouse_id' => $warehouse->id, 'shelf_id' => $shelf->id,
                'level_id' => $level->id, 'container_id' => $container->id, 'position_id' => $position->id,
                'description' => 'Ubicación operativa del almacén.', 'default' => false]);
        });
    }

    private function invalid($message): void
    {
        throw ValidationException::withMessages(['warehouse_id' => $message]);
    }
}
