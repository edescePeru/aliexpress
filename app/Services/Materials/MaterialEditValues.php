<?php
namespace App\Services\Materials;

use App\{Material, StockItem, Variant, InventoryLevel};
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

/** Resolve only omitted form values. Explicit null/false/zero remains explicit and must pass normal validation. */
class MaterialEditValues
{
    public function fill($request, array $original, ?Material $material = null, bool $lock = false): void
    {
        $id = $original['material_id'] ?? null;
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) { return; }
        if (!$material) {
            $material = app(MaterialCompanyCatalog::class)->apply(Material::query())->whereKey($id)->firstOrFail();
        }
        $hasVariants = $material->variants()->exists();
        foreach (['name' => 'full_name', 'description' => 'description', 'type_tax_id' => 'type_tax_id'] as $field => $attribute) {
            if (!array_key_exists($field, $original)) { $request->merge([$field => $material->$attribute]); }
        }
        if (!array_key_exists('tipo_variantes', $original)) { $request->merge(['tipo_variantes' => $hasVariants ? '1' : '0']); }
        if (!array_key_exists('variantes_json', $original) || !is_string($original['variantes_json'])) { return; }
        $rows = json_decode($original['variantes_json'], true);
        if (!is_array($rows)) { return; }
        foreach ($rows as $index => $row) {
            if (!is_array($row)) { continue; }
            foreach (['stock_item_id', 'variant_id'] as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== ''
                    && (!is_scalar($row[$key]) || !preg_match('/^[1-9][0-9]*$/D', (string) $row[$key]))) {
                    throw ValidationException::withMessages(['variantes_json' => 'El identificador de una fila no es válido.']);
                }
            }
            $stock = null; $variant = null;
            if ($hasVariants && !empty($row['variant_id']) && is_scalar($row['variant_id'])) {
                $q = $material->variants()->where('tenant_id', TenantContext::tenantId())->whereKey($row['variant_id']);
                if ($lock) { $q->lockForUpdate(); }
                $variant = $q->first();
                if ($variant) {
                    $q = StockItem::query()->where('tenant_id', TenantContext::tenantId())
                        ->where('material_id', $material->id)->where('variant_id', $variant->id);
                    if (array_key_exists('stock_item_id', $row)) { $q->whereKey($row['stock_item_id']); }
                    if ($lock) { $q->lockForUpdate(); }
                    $stock = $q->first();
                    if (!$stock && !empty($row['stock_item_id'])) {
                        throw ValidationException::withMessages(['variantes_json' => 'El StockItem indicado no corresponde a la variante del producto.']);
                    }
                }
            } elseif (!$hasVariants) {
                $q = StockItem::query()->where('tenant_id', TenantContext::tenantId())
                    ->where('material_id', $material->id)->whereNull('variant_id');
                if (array_key_exists('stock_item_id', $row)) {
                    if (!is_scalar($row['stock_item_id'])) { continue; }
                    $q->whereKey($row['stock_item_id']);
                }
                if ($lock) { $q->lockForUpdate(); }
                $candidates = $q->limit(2)->get();
                if ($candidates->count() > 1) {
                    throw ValidationException::withMessages(['variantes_json' => 'El producto tiene más de un registro simple. Indique el StockItem que desea editar.']);
                }
                $stock = $candidates->first();
            }
            if (!$stock) { continue; } // New rows keep creation defaults; incompatible IDs are rejected by the existing validator.
            $values = ['stock_item_id' => $stock->id, 'sku' => $stock->sku, 'codigo_barras' => $stock->barcode,
                'is_active' => $stock->is_active, 'afecto_inventario' => $stock->tracks_inventory];
            if ($variant) { $values += ['talla_id' => $variant->talla_id, 'color_id' => $variant->color_id]; }
            else { $values += ['pack' => $material->isPack, 'cantidad_pack' => $material->quantityPack]; }
            foreach ($values as $field => $value) {
                if ($value === null && in_array($field, ['is_active', 'afecto_inventario', 'pack', 'cantidad_pack'], true)) { continue; }
                if (!array_key_exists($field, $row)) { $row[$field] = $value; }
            }
            if (isset($row['inventory_levels']) && is_array($row['inventory_levels'])) {
                foreach ($row['inventory_levels'] as $i => $position) {
                    if (!is_array($position) || empty($position['id']) || !is_scalar($position['id'])) { continue; }
                    $q = app(MaterialInventoryScope::class)->levels(InventoryLevel::query())
                        ->where('stock_item_id', $stock->id)->whereKey($position['id']);
                    if ($lock) { $q->lockForUpdate(); }
                    $level = $q->first();
                    if (!$level) { continue; }
                    foreach (['warehouse_id', 'location_id', 'min_alert', 'max_alert'] as $field) {
                        if (!array_key_exists($field, $position)) { $position[$field] = $level->$field; }
                    }
                    $row['inventory_levels'][$i] = $position;
                }
            }
            $rows[$index] = $row;
        }
        $request->merge(['variantes_json' => json_encode($rows)]);
    }
}
