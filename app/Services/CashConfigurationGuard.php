<?php
namespace App\Services;

use App\{CashBox, CashBoxSubtype, CashMovement, CashRegister, CompanyBankAccount};
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class CashConfigurationGuard
{
    public function lockContext(): void
    {
        $actor = Auth::user();
        $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
        abort_unless($actor && !$actor->isPlatformAdmin() && $actor->enable && $tenant && $company
            && (int) $actor->tenant_id === (int) $tenant, 403);
        abort_unless(DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)
            ->where('is_active', true)->lockForUpdate()->first()
            && DB::table('company_user')->where('company_id', $company)->where('user_id', $actor->id)
                ->where('is_active', true)->exists(), 403, 'Seleccione una empresa activa y autorizada.');
    }

    public function scope($query)
    {
        return $query->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
    }

    private function currency($value): string
    {
        // Missing historical currency stays unknown; never silently assign it PEN or USD.
        return strtoupper(trim((string) $value));
    }

    public function boxHasHistory(CashBox $box): bool
    {
        return bccomp((string) ($box->current_balance ?? '0'), '0', 2) !== 0
            || (bool) $this->scope(CashRegister::query())->where('cash_box_id', $box->id)->select('id')->lockForUpdate()->first()
            || (bool) $this->scope(CashMovement::query())->where('cash_box_id', $box->id)->select('id')->lockForUpdate()->first();
    }

    public function assertCurrency(CashBox $box, $proposed): void
    {
        $current = $box->currency;
        if ($box->type === 'bank' && $box->company_bank_account_id) {
            $account = $this->scope(CompanyBankAccount::query())->whereKey($box->company_bank_account_id)->first();
            if ($account) { $current = $account->currency; }
        }
        if ($this->currency($current) !== $this->currency($proposed) && $this->boxHasHistory($box)) {
            throw ValidationException::withMessages(['currency' => 'No puede cambiar la moneda de una caja que ya tiene sesiones, movimientos o saldo. Cree otra caja para la nueva moneda.']);
        }
    }

    public function bankAccountCurrency(CompanyBankAccount $account, $proposed)
    {
        if ($this->currency($account->currency) === $this->currency($proposed)) { return []; }
        $boxes = $this->scope(CashBox::query())->where('company_bank_account_id', $account->id)
            ->where('type', 'bank')->orderBy('id')->lockForUpdate()->get();
        foreach ($boxes as $box) { $this->assertCurrency($box, $proposed); }
        return $boxes;
    }

    public function assertSubtype(CashBoxSubtype $subtype, array $data): void
    {
        if ((bool) $subtype->is_deferred === (bool) $data['is_deferred']) { return; }
        if ($this->scope(CashMovement::query())->where('cash_box_subtype_id', $subtype->id)
            ->whereIn('type', ['sale', 'income'])->select('id')->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['is_deferred' => 'Este subtipo ya tiene cobros registrados. No puede cambiar entre inmediato y diferido; cree otro subtipo para los nuevos cobros.']);
        }
    }

    public function assertCanDeactivate(CashBoxSubtype $subtype): void
    {
        // Company-wide: a global subtype can have obligations in more than one branch and closed session.
        if ($this->scope(CashMovement::query())->where('cash_box_subtype_id', $subtype->id)
            ->whereIn('type', ['sale', 'income'])->where('amount', '>', 0)
            ->where(function ($q) { $q->where('regularize', false)->orWhereNull('regularize'); })
            ->whereNull('deferred_cancelled_at')->select('id')->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['is_active' => 'No puede desactivar este subtipo: tiene pagos pendientes de regularizar. Regularícelos o cancele el cobro mediante su flujo autorizado.']);
        }
    }
}
