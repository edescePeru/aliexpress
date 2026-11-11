<?php

namespace App\Services\Sales;

use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Sale;
use App\SalePartialPayment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeferredSaleRefund
{
    public function plan(Sale $sale): array
    {
        if ($sale->internal_reversal_status === 'reversed') {
            return [];
        }

        $payments = $this->scope(SalePartialPayment::query())->where('sale_id', $sale->id)->get();
        $active = $payments->where('state', 1)->pluck('cash_movement_id');
        $inactive = $payments->where('state', 0)->pluck('cash_movement_id');
        $sources = $this->scope(CashMovement::query())
            ->where(function ($query) use ($sale, $active) {
                $query->where('sale_id', $sale->id)->orWhereIn('id', $active);
            })
            ->whereNotIn('id', $inactive->filter())
            ->whereIn('type', ['sale', 'income'])
            ->where('regularize', true)
            ->orderBy('id')->lockForUpdate()->get();

        $refunds = [];
        foreach ($sources as $source) {
            $deferred = $source->amount_regularize !== null || $source->balance_effect === false
                || $this->scopeCompany(CashBoxSubtype::query())->whereKey($source->cash_box_subtype_id)
                    ->where('is_deferred', true)->exists();
            if (!$deferred) {
                continue;
            }
            if ($sale->credit_note_status === 'partial') {
                $this->fail('La venta tiene una nota de crédito parcial. Concilia el importe pendiente antes de devolver el cobro.');
            }
            if ($source->cash_movement_origin_id || ($source->balance_effect !== false && $source->cash_movement_regularize_id)) {
                $this->fail('El cobro tiene vínculos de regularización antiguos y requiere conciliación.');
            }
            $posting = $source;
            if ($source->balance_effect === false) {
                $posting = $this->scope(CashMovement::query())
                    ->whereKey($source->cash_movement_regularize_id)
                    ->where('regularization_source_id', $source->id)
                    ->where('cash_movement_origin_id', $source->id)
                    ->where('cash_box_id', $source->cash_box_id)
                    ->where('regularize', true)->where('balance_effect', true)
                    ->where('type', $source->type)->lockForUpdate()->first();
                if (!$posting) {
                    $this->fail('No se encontró el abono vinculado al cobro.');
                }
            }
            if ($this->scope(CashMovement::query())->where('cash_movement_origin_id', $posting->id)
                ->where('type', 'expense')->exists()) {
                $refunds[$source->id] = null;
                continue;
            }

            $register = $this->scope(CashRegister::query())->whereKey($posting->cash_register_id)
                ->where('cash_box_id', $posting->cash_box_id)->lockForUpdate()->first();
            if (!$register) {
                $this->fail('No se encontró la sesión del abono en la caja y local de la venta.');
            }
            if (!$register->status || $posting->cash_settlement_id) {
                $open = $this->scope(CashRegister::query())
                    ->where('cash_box_id', $posting->cash_box_id)
                    ->where('user_id', Auth::id())->where('status', true)
                    ->orderBy('id')->limit(2)->lockForUpdate()->get();
                if ($open->count() !== 1) {
                    $this->fail('Abre una única sesión propia en la caja y local del abono para registrar la devolución.');
                }
                $register = $open->first();
            }
            if (bccomp((string) $source->amount, '0', 2) <= 0) {
                $this->fail('El cobro no tiene un importe bruto válido.');
            }
            $refunds[$source->id] = [$source, $posting, $register];
        }
        return $refunds;
    }

    public function post(Sale $sale, array $refunds): void
    {
        foreach (array_filter($refunds) as [$source, $posting, $register]) {
            // Refresh because several payments may debit the same session.
            $register->refresh();
            $gross = (string) $source->amount;
            CashMovement::create([
                'tenant_id' => $sale->tenant_id,
                'company_id' => $sale->company_id,
                'branch_id' => $sale->branch_id,
                'user_id' => Auth::id(),
                'cash_box_id' => $register->cash_box_id,
                'cash_register_id' => $register->id,
                'cash_box_subtype_id' => $source->cash_box_subtype_id,
                'sale_id' => $sale->id,
                'cash_movement_origin_id' => $posting->id,
                'type' => 'expense',
                'amount' => $gross,
                'regularize' => true,
                'balance_effect' => true,
                'arqueo' => false,
                'description' => 'Devolución de cobro diferido de venta #' . $sale->id,
            ]);
            $register->current_balance = bcsub((string) ($register->current_balance ?? 0), $gross, 2);
            $register->total_expenses = bcadd((string) ($register->total_expenses ?? 0), $gross, 2);
            $register->save();
        }
    }

    private function scope($query)
    {
        return $this->scopeCompany($query)->where('branch_id', TenantContext::branchId());
    }

    private function scopeCompany($query)
    {
        return $query->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId());
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['refund' => $message]);
    }
}
