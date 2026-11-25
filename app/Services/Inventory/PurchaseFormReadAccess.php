<?php
namespace App\Services\Inventory;

use App\Entry;
use App\Services\Materials\MaterialInventoryScope;
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class PurchaseFormReadAccess
{
    public const PERMISSIONS = [
        'entry_create' => 'create_entryPurchase', 'entry_edit' => 'edit_entryPurchase',
        'purchase_create' => 'create_orderPurchaseNormal', 'purchase_edit' => 'update_orderPurchaseNormal',
    ];

    public function authorize($consumer, array $allowedConsumers = []): void
    {
        $actor = Auth::user();
        abort_unless($actor, 401);
        if (!is_string($consumer) || !isset(self::PERMISSIONS[$consumer])) {
            throw ValidationException::withMessages(['consumer' => 'Recargue el formulario para consultar sus productos.']);
        }
        abort_unless(!$allowedConsumers || in_array($consumer, $allowedConsumers, true), 403);
        // Naming a different consumer never grants its capability.
        abort_unless($actor->can(self::PERMISSIONS[$consumer]), 403);
        app(MaterialInventoryScope::class)->context();
    }

    public function entryForEdit($id): Entry
    {
        $this->authorize('entry_edit');
        $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
        $entry = Entry::query()->where('tenant_id', $tenant)->where('company_id', $company)
            ->where('finance', false)->where('entry_type', 'Por compra')->findOrFail($id);
        $badDetail = DB::table('detail_entries')->where('entry_id', $entry->id)->where(function ($q) use ($tenant, $company) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', '<>', $tenant)
                ->orWhereNull('company_id')->orWhere('company_id', '<>', $company);
        })->exists();
        $badPosition = DB::table('items as item')->join('detail_entries as detail', 'detail.id', '=', 'item.detail_entry_id')
            ->where('detail.entry_id', $entry->id)
            ->leftJoin('warehouses as wh', function ($j) use ($tenant, $company) {
                $j->on('wh.id', '=', 'item.warehouse_id')->where('wh.tenant_id', $tenant)->where('wh.company_id', $company);
            })->leftJoin('locations as loc', function ($j) use ($tenant, $company) {
                $j->on('loc.id', '=', 'item.location_id')->on('loc.warehouse_id', '=', 'item.warehouse_id')
                    ->where('loc.tenant_id', $tenant)->where('loc.company_id', $company);
            })->where(function ($q) use ($tenant, $company) {
                $q->whereNull('item.tenant_id')->orWhere('item.tenant_id', '<>', $tenant)
                    ->orWhereNull('item.company_id')->orWhere('item.company_id', '<>', $company)
                    ->orWhere(function ($q) { $q->whereNotNull('item.warehouse_id')->whereNull('wh.id'); })
                    ->orWhere(function ($q) { $q->whereNotNull('item.location_id')->whereNull('loc.id'); });
            })->exists();
        abort_if($badDetail || $badPosition, 404, 'El ingreso contiene referencias incompatibles con la empresa actual.');
        return $entry;
    }

    public function coherentVariants($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('stock_items.variant_id')->orWhereHas('variant', function ($variant) {
                $variant->where('variants.tenant_id', TenantContext::tenantId())
                    ->whereColumn('variants.material_id', 'stock_items.material_id');
            });
        });
    }
}
