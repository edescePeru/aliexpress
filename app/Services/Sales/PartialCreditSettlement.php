<?php
namespace App\Services\Sales;

use App\{Sale, CreditNote, CashMovement, CashRegister, SalePartialPayment};

/** Client credit is gross; bank commissions remain an expense of the business. */
class PartialCreditSettlement
{
    public static function cents($value): int { return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP); }

    public function pending(Sale $sale)
    {
        return $sale->creditNotes()->where(function ($q) {
            $q->where('status', 'pending')->orWhere(function ($q) {
                $q->where('settlement_version', 1)->where('status', 'accepted')->where('internal_reversal_status', '!=', 'reversed');
            });
        });
    }

    public function hasPartialReturn(Sale $sale): bool
    {
        return $sale->creditNotes()->where('credit_note_type', 'partial')->where(function ($q) {
            $q->whereIn('status', ['pending', 'accepted'])->orWhere('internal_reversal_status', 'reversed');
        })->exists();
    }

    public function snapshot(Sale $sale): array
    {
        if($sale->internal_reversal_status==='reversed'){
            $operation=\Illuminate\Support\Facades\DB::table('sale_annulment_operations')->where('sale_id',$sale->id)
                ->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)->where('branch_id',$sale->branch_id)
                ->where('reversal_state','reversed')->first();
            $evidence=$operation?json_decode($operation->financial_evidence?:'null',true):null;
            if($operation&&is_array($evidence)&&isset($evidence['collected_cents'])){
                return ['original_cents'=>self::cents($sale->importe_total),
                    'credited_cents'=>self::cents($sale->creditNotes()->where('internal_reversal_status','reversed')->sum('importe_total')),
                    'collected_cents'=>(int)$evidence['collected_cents'],'refunded_cents'=>self::cents($operation->refund_amount),
                    'net_cents'=>0,'retained_cents'=>0,'debt_cents'=>0,'reconciliation_required'=>false,
                    'operation_reversed'=>true,'debt_cancelled_cents'=>self::cents($operation->debt_cancelled),
                    'pending_cancelled_cents'=>array_sum(array_map(function($row){return !empty($row['pending'])?(int)$row['amount_cents']:0;},$evidence['evidence']??[]))];
            }
        }
        $notes = $sale->creditNotes()->where('internal_reversal_status', 'reversed')->get();
        $credited = $refunded = 0;
        $requiresReview = false;
        foreach ($notes as $note) {
            $credited += self::cents($note->importe_total);
            if (in_array((int) $note->settlement_version, [1,2], true)) {
                $refunded += self::cents($note->cash_refund_amount);
            } elseif ($note->cash_movement_id) {
                $expense = $this->movements($sale)->where('type', 'expense')->find($note->cash_movement_id);
                if (!$expense) { $requiresReview = true; }
                else { $refunded += self::cents($expense->amount); }
            } elseif ($note->cash_refund_status === 'refunded') { $requiresReview = true; }
        }
        if ($sale->pagos_parciales_venta === 's') {
            $collected = self::cents(SalePartialPayment::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
                ->where('branch_id', $sale->branch_id)->where('sale_id', $sale->id)->where('state', 1)->sum('amount'));
        } else {
            // A regularization receipt does not represent another payment by the client.
            $collected = self::cents($this->movements($sale)->where('type', 'sale')->whereNull('regularization_source_id')->sum('amount')) - self::cents($sale->vuelto);
        }
        $original = self::cents($sale->importe_total);
        if ($sale->pagos_parciales_venta !== 's' && $collected !== $original) { $requiresReview = true; }
        $net = $original - $credited;
        $retained = $collected - $refunded;
        return ['original_cents' => $original, 'credited_cents' => $credited, 'collected_cents' => $collected,
            'refunded_cents' => $refunded, 'net_cents' => $net, 'retained_cents' => $retained,
            'reconciliation_required' => $requiresReview, 'debt_cents' => max(0, $net - $retained)];
    }

    public function plan(array $snapshot, int $amount): array
    {
        if (!empty($snapshot['reconciliation_required']) || $snapshot['net_cents'] < 0 || $snapshot['retained_cents'] < 0 || $snapshot['retained_cents'] > $snapshot['net_cents']) {
            throw new \DomainException('Los cobros y devoluciones históricos necesitan conciliación antes de emitir otra nota.');
        }
        if ($amount <= 0 || $amount > $snapshot['net_cents']) {
            throw new \DomainException('El importe supera el saldo comercial de la venta.');
        }
        $reduction = min($amount, $snapshot['debt_cents']);
        return ['debt_reduction_amount' => $reduction / 100, 'cash_refund_amount' => ($amount - $reduction) / 100];
    }

    public function movements(Sale $sale)
    {
        return CashMovement::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
            ->where('branch_id', $sale->branch_id)->where('sale_id', $sale->id);
    }

    public static function netSql(): string
    {
        return "CASE WHEN ".self::terminalSql()." THEN 0 ELSE sales.importe_total - (SELECT COALESCE(SUM(cn.importe_total), 0) FROM credit_notes cn WHERE cn.sale_id = sales.id AND cn.internal_reversal_status = 'reversed') END";
    }

    public static function retainedSql(): string
    {
        return "CASE WHEN ".self::terminalSql()." THEN 0 ELSE (SELECT COALESCE(SUM(spp.amount), 0) FROM sale_partial_payments spp WHERE spp.sale_id = sales.id AND spp.state = 1 AND spp.tenant_id = sales.tenant_id AND spp.company_id = sales.company_id AND spp.branch_id = sales.branch_id)
            - (SELECT COALESCE(SUM(CASE WHEN cn.settlement_version IN (1,2) THEN cn.cash_refund_amount ELSE COALESCE(cm.amount, 0) END), 0)
            FROM credit_notes cn LEFT JOIN cash_movements cm ON cm.id = cn.cash_movement_id AND cm.sale_id = sales.id
                AND cm.tenant_id = sales.tenant_id AND cm.company_id = sales.company_id AND cm.branch_id = sales.branch_id AND cm.type = 'expense'
            WHERE cn.sale_id = sales.id AND cn.internal_reversal_status = 'reversed') END";
    }

    private static function terminalSql(): string
    {
        return "EXISTS(SELECT 1 FROM sale_annulment_operations terminal_op WHERE terminal_op.sale_id=sales.id
            AND terminal_op.tenant_id=sales.tenant_id AND terminal_op.company_id=sales.company_id
            AND terminal_op.branch_id=sales.branch_id AND terminal_op.reversal_state='reversed')";
    }
    public function channels(Sale $sale): array
    {
        $result = [];
        foreach (app(SaleListAccess::class)->boxes()->where('currency', $sale->currency)->orderBy('id')->get() as $box) {
            $sessions = $this->sessions($sale, $box->id)->get();
            if ($sessions->count() !== 1) { continue; }
            $subtypes = $box->type === 'bank' && $box->uses_subtypes
                ? app(SaleListAccess::class)->subtypes()->where(function ($q) use ($box) { $q->whereNull('cash_box_id')->orWhere('cash_box_id', $box->id); })->get(['id', 'name'])->toArray() : [];
            $result[] = ['id' => $box->id, 'name' => $box->name, 'uses_subtypes' => $box->type === 'bank' && $box->uses_subtypes,
                'subtypes' => $subtypes];
        }
        return $result;
    }

    private function sessions(Sale $sale, $boxId)
    {
        return CashRegister::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
            ->where('branch_id', $sale->branch_id)->where('cash_box_id', $boxId)->where('user_id', auth()->id())->where('status', 1);
    }

    public function destination(Sale $sale, $boxId, $subtypeId): CashRegister
    {
        $box = app(SaleListAccess::class)->boxes()->where('currency', $sale->currency)->where('id', $boxId)->lockForUpdate()->first();
        if (!$box) { throw new \DomainException('Seleccione una caja activa de esta empresa/local y de la moneda de la venta.'); }
        $sessions = $this->sessions($sale, $box->id)->lockForUpdate()->get();
        if ($sessions->count() !== 1) { throw new \DomainException('Necesita una única sesión propia abierta en la caja seleccionada y este local.'); }
        if ($box->type === 'bank' && $box->uses_subtypes) {
            $subtype = app(SaleListAccess::class)->subtypes()->where('id', $subtypeId)->where(function ($q) use ($box) {
                $q->whereNull('cash_box_id')->orWhere('cash_box_id', $box->id);
            })->lockForUpdate()->first();
            if (!$subtype) { throw new \DomainException('Seleccione un subtipo activo de la caja bancaria elegida.'); }
        } elseif ($subtypeId) { throw new \DomainException('La caja elegida no utiliza subtipos.'); }
        return $sessions->first();
    }

    public function refund(CreditNote $note): void
    {
        if ($note->cash_refund_status === 'refunded' || self::cents($note->cash_refund_amount) === 0) { return; }
        $sale = $note->sale;
        // Recalculate under the sale lock: never apply an obsolete monetary plan.
        $plan = $this->plan($this->snapshot($sale), self::cents($note->importe_total));
        if (self::cents($plan['cash_refund_amount']) !== self::cents($note->cash_refund_amount)) {
            throw new \DomainException('Cambió el saldo de la venta; la nota necesita conciliación antes de devolver dinero.');
        }
        $session = $this->destination($sale, $note->refund_cash_box_id, $note->refund_subtype_id);
        $amount = self::cents($note->cash_refund_amount) / 100;
        $movement = CashMovement::create([
            'tenant_id' => $sale->tenant_id, 'company_id' => $sale->company_id, 'branch_id' => $sale->branch_id,
            'user_id' => auth()->id(), 'cash_box_id' => $note->refund_cash_box_id, 'cash_register_id' => $session->id,
            'cash_box_subtype_id' => $note->refund_subtype_id, 'sale_id' => $sale->id, 'type' => 'expense',
            'amount' => $amount, 'regularize' => true, 'balance_effect' => true, 'arqueo' => false,
            'description' => 'Devolución por NC parcial #' . $note->id . ' de venta #' . $sale->id,
        ]);
        $session->current_balance = (self::cents($session->current_balance) - self::cents($amount)) / 100;
        $session->total_expenses = (self::cents($session->total_expenses) + self::cents($amount)) / 100;
        $session->save();
        $note->forceFill(['cash_refund_status' => 'refunded', 'cash_refund_at' => now(), 'cash_refund_by' => auth()->id(),
            'cash_movement_id' => $movement->id])->save();
    }
}
