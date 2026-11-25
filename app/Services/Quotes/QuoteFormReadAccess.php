<?php
namespace App\Services\Quotes;

use App\{Customer, ContactName};
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class QuoteFormReadAccess
{
    public function authorize(array $permissions): void
    {
        $actor = Auth::user();
        abort_unless($actor, 401);
        $allowed = false;
        foreach ($permissions as $permission) {
            if ($actor->can($permission)) { $allowed = true; break; }
        }
        abort_unless($allowed && !$actor->isPlatformAdmin() && TenantContext::tenantIdOrNull()
            && TenantContext::companyIdOrNull() && TenantContext::branchIdOrNull(), 403);
        app(QuoteBranchCatalog::class)->validateContext();
    }

    public function catalog(): void
    {
        $this->authorize(['create_quoteSale', 'edit_quoteSale']);
    }

    public function conversion(): void
    {
        // Same capability as the existing registrar comprobante screen; not list-only.
        $this->authorize(['edit_quoteSale']);
    }

    public function contacts($customerId): array
    {
        // Industrial screens are paused; this shared endpoint remains available to QuoteSale create/edit only.
        $this->authorize(['create_quoteSale', 'edit_quoteSale']);
        abort_unless(is_scalar($customerId) && preg_match('/^[1-9][0-9]*$/D', (string) $customerId), 404);
        $customer = Customer::query()->where('tenant_id', TenantContext::tenantId())->findOrFail($customerId);
        return ContactName::query()->where('tenant_id', TenantContext::tenantId())
            ->where('customer_id', $customer->id)->orderBy('id')->get(['id', 'name'])
            ->map(function ($contact) { return ['id' => $contact->id, 'contact' => $contact->name]; })->all();
    }
}
