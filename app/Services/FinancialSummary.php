<?php

namespace App\Services;

use App\CashBox;
use App\CashMovement;
use App\CashRegister;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSummary
{
    public function report(?int $branchId, ?Carbon $from, ?Carbon $to): array
    {
        $boxes = $this->company(CashBox::query())->with('branch:id,name')
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where(function ($scope) use ($branchId) {
                    $scope->whereNull('branch_id')->orWhere('branch_id', $branchId);
                });
            })->orderBy('position')->orderBy('id')->get();

        $boxIds = $boxes->pluck('id');
        $sessions = $this->company(CashRegister::query())->whereIn('cash_box_id', $boxIds)
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->where('status', true)->whereNotNull('branch_id')
            ->groupBy('cash_box_id')->selectRaw('cash_box_id, SUM(current_balance) AS balance, COUNT(*) AS sessions')
            ->get()->keyBy('cash_box_id');

        $movements = $this->company(CashMovement::query())->whereIn('cash_box_id', $boxIds)
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->whereNotNull('branch_id');

        $pending = (clone $movements)->whereNull('deferred_cancelled_at')->where('regularize', false)->whereIn('type', ['sale', 'income'])
            ->whereNull('cash_movement_origin_id')->whereNull('cash_movement_regularize_id')
            ->whereNull('regularization_source_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('cash_box_subtypes as subtype')
                    ->whereColumn('subtype.id', 'cash_movements.cash_box_subtype_id')
                    ->whereColumn('subtype.tenant_id', 'cash_movements.tenant_id')
                    ->whereColumn('subtype.company_id', 'cash_movements.company_id')
                    ->where('subtype.is_deferred', true);
            })
            ->where(function ($query) {
                $query->whereNull('balance_effect')->orWhere('balance_effect', true);
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('cash_movements as child')
                    ->whereColumn('child.cash_movement_origin_id', 'cash_movements.id');
            })
            ->where(function ($query) {
                $query->whereNull('sale_id')->orWhereExists(function ($sale) {
                    $sale->selectRaw('1')->from('sales')
                        ->whereColumn('sales.id', 'cash_movements.sale_id')
                        ->whereColumn('sales.tenant_id', 'cash_movements.tenant_id')
                        ->whereColumn('sales.company_id', 'cash_movements.company_id')
                        ->where('sales.state_annulled', false)
                        ->where(function ($status) {
                            $status->whereNull('sales.internal_reversal_status')
                                ->orWhere('sales.internal_reversal_status', '<>', 'reversed');
                        });
                });
            })->groupBy('cash_box_id')->selectRaw('cash_box_id, SUM(amount) AS total')
            ->pluck('total', 'cash_box_id');

        $activity = (clone $movements)->affectingBalance()
            ->when($from !== null, function ($query) use ($from) {
                $query->where('created_at', '>=', $from);
            })->when($to !== null, function ($query) use ($to) {
                $query->where('created_at', '<', $to);
            })
            ->whereIn('type', ['sale', 'income', 'expense'])
            ->groupBy('cash_box_id')->selectRaw("
                cash_box_id,
                SUM(CASE WHEN type IN ('sale', 'income') THEN COALESCE(amount_regularize, amount) ELSE 0 END) AS receipts,
                SUM(CASE WHEN type = 'expense' THEN COALESCE(amount_regularize, amount) ELSE 0 END) AS expenses,
                SUM(CASE WHEN type IN ('sale', 'income') THEN COALESCE(commission, 0) ELSE 0 END) AS commissions
            ")->get()->keyBy('cash_box_id');

        $funds = $this->company(DB::table('cash_opening_funds'))->whereIn('cash_box_id', $boxIds)
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->when($from !== null, function ($query) use ($from) {
                $query->where('created_at', '>=', $from);
            })->when($to !== null, function ($query) use ($to) {
                $query->where('created_at', '<', $to);
            })->groupBy('cash_box_id')->selectRaw("
                cash_box_id,
                SUM(CASE WHEN origin = 'external' THEN amount ELSE 0 END) AS external_funds,
                SUM(CASE WHEN origin = 'consolidated' THEN amount ELSE 0 END) AS opening_transfers
            ")->get()->keyBy('cash_box_id');

        $groups = [];
        foreach ($boxes as $box) {
            $currency = $box->currency ?: 'Sin moneda';
            $session = $sessions->get($box->id);
            $period = $activity->get($box->id);
            $consolidated = $branchId !== null && $box->branch_id === null ? null : (string) $box->current_balance;
            $row = [
                'box' => $box,
                'consolidated' => $consolidated,
                'open' => $session->balance ?? '0',
                'sessions' => $session->sessions ?? 0,
                'pending' => $pending->get($box->id, '0'),
                'external_funds' => $funds->get($box->id)->external_funds ?? '0',
                'opening_transfers' => $funds->get($box->id)->opening_transfers ?? '0',
                'receipts' => $period->receipts ?? '0',
                'expenses' => $period->expenses ?? '0',
                'commissions' => $period->commissions ?? '0',
            ];
            $groups[$currency]['rows'][] = $row;
            foreach (['consolidated', 'open', 'pending', 'receipts', 'expenses', 'commissions', 'external_funds', 'opening_transfers'] as $field) {
                $groups[$currency]['totals'][$field] = bcadd(
                    $groups[$currency]['totals'][$field] ?? '0', (string) ($row[$field] ?? '0'), 2
                );
            }
        }

        ksort($groups);

        return $groups;
    }

    private function company($query)
    {
        return $query->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId());
    }
}


