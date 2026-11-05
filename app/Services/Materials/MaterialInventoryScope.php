<?php

namespace App\Services\Materials;

use App\InventoryLevel;
use App\StockItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialInventoryScope
{
    public function context(): void
    {
        app(MaterialParameters::class)->context();
        $branch = TenantContext::branchIdOrNull();
        abort_unless($branch
            && DB::table('branches')->where('id', $branch)
                ->where('company_id', TenantContext::companyId())->where('is_active', true)->exists()
            && DB::table('branch_user')->where('branch_id', $branch)
                ->where('user_id', Auth::id())->where('is_active', true)->exists(),
            403, 'No tiene un local activo autorizado para consultar o configurar inventario.');
    }

    public function snapshot($request): void
    {
        $this->context();
        foreach (['tenant' => TenantContext::tenantId(), 'company' => TenantContext::companyId(),
            'branch' => TenantContext::branchId()] as $key => $expected) {
            $value = $request->input('material_inventory_'.$key,
                $request->header('X-Material-Inventory-'.ucfirst($key)));
            if (!is_scalar($value) || (string) $value !== (string) $expected) {
                throw ValidationException::withMessages([
                    'context' => 'Cambió la empresa o el local desde que abrió la pantalla. Recargue la página.',
                ]);
            }
        }
    }

    public function warehouses($query)
    {
        $this->context();
        return $query->where('warehouses.tenant_id', TenantContext::tenantId())
            ->where('warehouses.company_id', TenantContext::companyId())
            ->where(function ($q) {
                $q->whereNull('warehouses.branch_id')->orWhere('warehouses.branch_id', TenantContext::branchId());
            });
    }

    public function levels($query)
    {
        $this->context();
        return $query->where('inventory_levels.tenant_id', TenantContext::tenantId())
            ->where('inventory_levels.company_id', TenantContext::companyId())
            ->whereIn('inventory_levels.warehouse_id', $this->warehouses(DB::table('warehouses'))->select('warehouses.id'))
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('stock_items as inventory_stock_item')
                    ->whereColumn('inventory_stock_item.id', 'inventory_levels.stock_item_id')
                    ->where('inventory_stock_item.tenant_id', TenantContext::tenantId());
            })
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('locations as inventory_location')
                    ->whereColumn('inventory_location.id', 'inventory_levels.location_id')
                    ->whereColumn('inventory_location.warehouse_id', 'inventory_levels.warehouse_id')
                    ->where('inventory_location.tenant_id', TenantContext::tenantId())
                    ->where('inventory_location.company_id', TenantContext::companyId());
            });
    }

    public function stockItem(StockItem $stockItem): void
    {
        $this->context();
        abort_unless((int) $stockItem->tenant_id === TenantContext::tenantId()
            && DB::table('company_stock_items')->where('tenant_id', TenantContext::tenantId())
                ->where('company_id', TenantContext::companyId())->where('stock_item_id', $stockItem->id)
                ->where('is_active', true)->exists(), 404, 'El producto no está habilitado para la empresa actual.');
    }

    // The caller owns the transaction. Only existing levels can be changed.
    public function sync(StockItem $stockItem, array $rows): void
    {
        $this->context();
        abort_unless((int) $stockItem->tenant_id === TenantContext::tenantId(), 404);
        if (!$rows) { return; }
        $ids = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['id']) || !is_scalar($row['id'])
                || !preg_match('/^[1-9][0-9]*$/D', (string) $row['id'])
                || in_array((string) $row['id'], $ids, true)) {
                throw ValidationException::withMessages(['inventory_levels' => 'Los niveles deben tener IDs válidos y no repetidos.']);
            }
            $ids[] = (string) $row['id'];
        }
        // Sort locks independently of payload order.
        $levels = $this->levels(InventoryLevel::query())->where('stock_item_id', $stockItem->id)
            ->whereIn('inventory_levels.id', $ids)->orderBy('inventory_levels.id')->lockForUpdate()->get()->keyBy('id');
        if ($levels->count() !== count($ids)) {
            throw ValidationException::withMessages(['inventory_levels' => 'Un nivel no pertenece al producto, empresa o local actual, o tiene una ubicación incompatible.']);
        }
        foreach ($rows as $row) {
            $level = $levels->get($row['id']);
            foreach (['warehouse_id', 'stock_item_id'] as $key) {
                if (array_key_exists($key, $row) && (!is_scalar($row[$key]) || (string) $row[$key] !== (string) $level->$key)) {
                    throw ValidationException::withMessages(['inventory_levels' => 'El almacén o producto enviado no corresponde al nivel de inventario.']);
                }
            }
            $values = [];
            foreach (['min_alert', 'max_alert'] as $key) {
                if (!array_key_exists($key, $row)) { $values[$key] = (float) $level->$key; continue; }
                $value = $row[$key];
                if ($value === null || $value === '') { $value = 0; }
                if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
                    throw ValidationException::withMessages(['inventory_levels' => 'Los mínimos y máximos deben ser números no negativos.']);
                }
                $values[$key] = (float) $value;
            }
            if ($values['min_alert'] > $values['max_alert']) {
                throw ValidationException::withMessages(['inventory_levels' => 'El stock mínimo no puede ser mayor al stock máximo.']);
            }
            $level->min_alert = $values['min_alert'];
            $level->max_alert = $values['max_alert'];
            if (!$level->save()) { throw new \RuntimeException('No se pudo guardar el nivel de inventario.'); }
        }
    }
}
