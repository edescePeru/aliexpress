<?php

namespace App\Services\Sales;

use App\GananciaDiaria;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyProfitReport
{
    public function summaries()
    {
        return GananciaDiaria::query()->where([
            'tenant_id' => TenantContext::tenantId(),
            'company_id' => TenantContext::companyId(),
            'branch_id' => TenantContext::branchId(),
            'currency' => 'PEN',
        ]);
    }

    public function contexts(string $date)
    {
        $next = Carbon::parse($date)->addDay()->toDateString();
        $columns = ['tenant_id', 'company_id', 'branch_id'];
        $sales = DB::table('sales')->where('currency', 'PEN')
            ->where('date_sale', '>=', $date)->where('date_sale', '<', $next)
            ->select($columns);
        $summaries = DB::table('ganancia_diarias')->where('currency', 'PEN')
            ->whereDate('date_resumen', $date)->select($columns);

        return DB::query()->fromSub($sales->union($summaries), 'contexts')
            ->join('companies', function ($join) {
                $join->on('companies.id', '=', 'contexts.company_id')
                    ->on('companies.tenant_id', '=', 'contexts.tenant_id');
            })
            ->join('branches', function ($join) {
                $join->on('branches.id', '=', 'contexts.branch_id')
                    ->on('branches.company_id', '=', 'contexts.company_id');
            })
            ->join('tenants', 'tenants.id', '=', 'contexts.tenant_id')
            ->orderBy('contexts.tenant_id')->orderBy('contexts.company_id')->orderBy('contexts.branch_id')
            ->get(['contexts.tenant_id', 'contexts.company_id', 'contexts.branch_id']);
    }

    public function recalculate(object $context, string $date): void
    {
        DB::transaction(function () use ($context, $date) {
            // Serialize recalculations for this company, including the first insert.
            $company = DB::table('companies')->where('id', $context->company_id)
                ->where('tenant_id', $context->tenant_id)->lockForUpdate()->first();
            if (!$company || !DB::table('branches')->where('id', $context->branch_id)
                ->where('company_id', $context->company_id)->exists()) {
                throw new \RuntimeException('El contexto del resumen dejó de existir.');
            }

            $report = app(UserProfitReport::class);
            $groups = [];
            $quantity = $sold = $profit = 0.0;
            $missing = false;
            $next = Carbon::parse($date)->addDay()->toDateString();
            $sales = $report->salesInContext($context->tenant_id, $context->company_id, $context->branch_id)
                ->where('date_sale', '>=', $date)->where('date_sale', '<', $next)
                ->with(['details' => function ($query) use ($context) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class)
                        ->where('tenant_id', $context->tenant_id);
                }]);
            $sales->chunkById(200, function ($sales) use ($report, &$groups, &$quantity, &$sold, &$profit, &$missing) {
                foreach ($sales as $sale) {
                    foreach ($sale->details as $detail) {
                        [$units, $amount, $cost, $margin] = $report->line($detail);
                        $key = $detail->stock_item_id ? 'stock:' . $detail->stock_item_id : 'material:' . $detail->material_id;
                        if (!isset($groups[$key])) {
                            $groups[$key] = [
                                'material_id' => $detail->material_id,
                                'stock_item_id' => $detail->stock_item_id,
                                'quantity' => 0,
                                'price_sale' => 0,
                                'utility' => 0,
                            ];
                        }
                        $group = &$groups[$key];
                        $group['quantity'] += $units;
                        $group['price_sale'] += $amount;
                        $group['utility'] = $margin === null || $group['utility'] === null ? null : $group['utility'] + $margin;
                        unset($group);

                        $quantity += $units;
                        $sold += $amount;
                        $profit += $margin ?? 0;
                        $missing = $missing || $cost === null;
                    }
                }
            });

            $key = [
                'tenant_id' => $context->tenant_id,
                'company_id' => $context->company_id,
                'branch_id' => $context->branch_id,
                'currency' => 'PEN',
                'date_resumen' => $date,
            ];
            $contextKey = $key;
            unset($contextKey['date_resumen']);
            $summary = GananciaDiaria::query()->where($contextKey)->whereDate('date_resumen', $date)
                ->first() ?? new GananciaDiaria($key);
            $summary->fill([
                'quantity_sale' => round($quantity, 2),
                'total_sale' => round($sold, 2),
                'total_utility' => $missing ? null : round($profit, 2),
            ]);

            $summary->save();
            $summary->details()->delete();
            foreach ($groups as $group) {
                $group['price_sale'] = round($group['price_sale'], 2);
                $group['quantity'] = round($group['quantity'], 2);
                $group['utility'] = $group['utility'] === null ? null : round($group['utility'], 2);
                $summary->details()->create(['date_detail' => $date] + $group);
            }
        }, 3);
    }
}
