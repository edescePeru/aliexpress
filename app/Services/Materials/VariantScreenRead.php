<?php
namespace App\Services\Materials;

use App\Material;
use App\Variant;
use App\Support\TenantContext;

class VariantScreenRead
{
    public function material($id): Material
    {
        $access = app(StockItemScreenAccess::class);
        $access->permission('verVariantes_material');
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        $material = Material::query()->where('tenant_id', TenantContext::tenantId())->findOrFail($id, ['id','tenant_id','full_name','code']);
        abort_unless($access->stockItems()->where('stock_items.material_id', $material->id)->exists(), 404);
        return $material->setAppends([]);
    }

    public function rows($id)
    {
        $material = $this->material($id);
        $tenant = TenantContext::tenantId();
        $enabled = app(StockItemScreenAccess::class)->stockItems()->select('stock_items.variant_id')
            ->where('stock_items.material_id', $material->id)->whereNotNull('stock_items.variant_id');
        return Variant::query()->where('variants.tenant_id', $tenant)->where('variants.material_id', $material->id)
            ->whereIn('variants.id', $enabled)
            ->with([
                'talla'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','name','short_name'); },
                'color'=>function ($q) use ($tenant) { $q->where('tenant_id', $tenant)->select('id','tenant_id','name','short_name'); },
            ])
            ->select('variants.id','variants.material_id','variants.talla_id','variants.color_id','variants.attribute_summary','variants.image','variants.is_active')
            ->orderBy('variants.id')->get()->map(function ($variant) use ($material) {
                return [
                    'id'=>$variant->id, 'material_id'=>$material->id,
                    'material'=>['id'=>$material->id, 'full_name'=>$material->full_name, 'code'=>$material->code],
                    'talla'=>['id'=>optional($variant->talla)->id, 'name'=>optional($variant->talla)->name ?? '', 'short_name'=>optional($variant->talla)->short_name ?? ''],
                    'color'=>['id'=>optional($variant->color)->id, 'name'=>optional($variant->color)->name ?? '', 'short_name'=>optional($variant->color)->short_name ?? ''],
                    'attribute_summary'=>$variant->attribute_summary, 'image'=>$variant->image, 'is_active'=>$variant->is_active,
                ];
            });
    }
}
