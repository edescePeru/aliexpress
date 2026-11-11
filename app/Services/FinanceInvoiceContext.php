<?php
namespace App\Services;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashRegister;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FinanceInvoiceContext
{
    public function ids(): array
    {
        return ['tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId()];
    }

    public function boxes()
    {
        return CashBox::query()->where($this->ids())->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('branch_id')->orWhere('branch_id', TenantContext::branchId());
            });
    }

    public function subtypes()
    {
        return CashBoxSubtype::query()->where($this->ids())->where('is_active', true)
            ->where('is_deferred', false);
    }

    public function sessions()
    {
        return CashRegister::query()->where($this->ids())->where('branch_id', TenantContext::branchId())
            ->where('user_id', Auth::id())->where('status', true);
    }

    public function payment($boxId, $subtypeId, string $currency): array
    {
        $box = $this->boxes()->where('currency', $currency)->whereKey($boxId)->first();
        if (!$box) {
            $this->fail('Seleccione una caja activa del local y de la moneda de la factura.');
        }
        $subtype = null;
        if ($box->type === 'bank' && $box->uses_subtypes) {
            $subtype = $this->subtypes()->whereKey($subtypeId)->where(function ($query) use ($box) {
                $query->whereNull('cash_box_id')->orWhere('cash_box_id', $box->id);
            })->first();
            if (!$subtype) {
                $this->fail('Seleccione un subtipo de pago inmediato válido para esta caja.');
            }
        }
        $sessions = $this->sessions()->where('cash_box_id', $box->id)->orderBy('id')->limit(2)->lockForUpdate()->get();
        if ($sessions->count() !== 1) {
            $this->fail('Debe tener una única sesión abierta para la caja elegida.');
        }
        return [$box, $subtype, $sessions->first()];
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['pv_cash_box_id' => $message]);
    }
}
