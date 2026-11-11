<?php

namespace App\Services\Sales;

use App\Sale;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class UserProfitReport
{
    public function query($creator = null, $start = null, $end = null)
    {
        $filters = Validator::make(compact('creator', 'start', 'end'), [
            'creator' => 'nullable|integer|min:1',
            'start' => 'nullable|required_with:end|date_format:d/m/Y',
            'end' => 'nullable|required_with:start|date_format:d/m/Y',
        ])->validate();

        $query = $this->salesInContext(TenantContext::tenantId(), TenantContext::companyId(), TenantContext::branchId());

        if (!empty($filters['creator'])) {
            $query->where('created_by', $filters['creator']);
        }
        if (!empty($filters['start'])) {
            $from = Carbon::createFromFormat('d/m/Y', $filters['start'])->startOfDay();
            $to = Carbon::createFromFormat('d/m/Y', $filters['end'])->startOfDay();
            if ($to->lt($from)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['endDate' => 'La fecha final debe ser igual o posterior a la inicial.']);
            }
            $query->where('date_sale', '>=', $from->toDateTimeString())
                ->where('date_sale', '<', $to->addDay()->toDateTimeString());
        }
        return $query;
    }

    public function salesInContext(int $tenantId, int $companyId, int $branchId)
    {
        return Sale::query()->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('currency', 'PEN')
            ->where('state_annulled', false)
            ->where(function ($query) {
                $query->whereNull('internal_reversal_status')->orWhere('internal_reversal_status', '!=', 'reversed');
            });
    }

    public function authors()
    {
        return $this->query()->whereNotNull('created_by')->orderByDesc('id')
            ->get(['id', 'created_by', 'created_by_name'])->unique('created_by')->values();
    }

    public function author(Sale $sale): string
    {
        return $sale->created_by_name ?: ($sale->created_by ? 'Usuario #' . $sale->created_by : 'No registrado');
    }

    public function line($detail): array
    {
        $quantity = (float) $detail->quantity;
        $sold = (float) $detail->total - (float) $detail->discount;
        $cost = $detail->total_cost !== null ? (float) $detail->total_cost : null;
        if ($cost === null && $detail->unit_cost !== null && $quantity > 0) {
            $cost = (float) $detail->unit_cost * $quantity;
        }
        return [$quantity, $sold, $cost, $cost === null ? null : $sold - $cost];
    }

    public function totals(iterable $sales): array
    {
        $quantity = $sold = $cost = $profit = 0.0;
        $missing = 0;
        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                [$units, $amount, $lineCost, $margin] = $this->line($detail);
                $quantity += $units;
                $sold += $amount;
                if ($lineCost === null) {
                    $missing++;
                    continue;
                }
                $cost += $lineCost;
                $profit += $margin;
            }
        }
        return [
            'quantity_sale_sum' => $quantity,
            'total_sale_sum' => round($sold, 2),
            'total_cost_sum' => $missing ? null : round($cost, 2),
            'total_utility_sum' => $missing ? null : round($profit, 2),
            'missing_cost_count' => $missing,
        ];
    }
}
