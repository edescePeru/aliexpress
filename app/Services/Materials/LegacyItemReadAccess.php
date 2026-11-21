<?php
namespace App\Services\Materials;

use App\Entry;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class LegacyItemReadAccess
{
    public const PERMISSIONS = [
        'getJsonItems' => ['create_entryScrap'],
        'getJsonItemsTransfer' => ['create_transfer'],
        'getJsonItemsOutput' => ['create_request', 'create_requestSimple'],
        'getJsonItemsOutputComplete' => ['create_request', 'create_requestSimple'],
        'getJsonItemsOutputScraped' => ['create_request', 'create_requestSimple'],
        'getJsonItemsEntry' => ['list_entryPurchase', 'list_entryInventory', 'list_entryScrap', 'list_orderPurchaseFinance'],
    ];

    public function authorize(string $action): void
    {
        app(MaterialInventoryScope::class)->context();
        $allowed = false;
        foreach (self::PERMISSIONS[$action] ?? [] as $permission) {
            if (Auth::user()->can($permission)) { $allowed = true; break; }
        }
        abort_unless($allowed, 403, 'No tiene permiso para consultar estos ítems.');
    }

    public function entry($id): Entry
    {
        $this->authorize('getJsonItemsEntry');
        abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404);
        $entry = Entry::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->findOrFail($id);
        $permission = $entry->finance ? 'list_orderPurchaseFinance' : ([
            'Por compra'=>'list_entryPurchase', 'Inventario'=>'list_entryInventory', 'Retacería'=>'list_entryScrap',
        ][$entry->entry_type] ?? null);
        abort_unless($permission && Auth::user()->can($permission), 403, 'No tiene permiso para consultar este tipo de entrada.');
        return $entry;
    }
}
