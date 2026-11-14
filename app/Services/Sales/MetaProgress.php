<?php

namespace App\Services\Sales;

use App\Meta;
use App\Sale;
use App\Support\TenantContext;
use Carbon\Carbon;

class MetaProgress
{
    public function rows(string $type, Carbon $start, Carbon $end): array
    {
        $metas = Meta::forCurrentCompany()->with('users')
            ->where('tipo', $type)
            ->where('fecha_inicio', $start->toDateString())
            ->where('fecha_fin', $end->toDateString())->get();

        if ($metas->isEmpty()) {
            return [];
        }

        $ids = $metas->flatMap(function ($meta) {
            return $meta->users->pluck('id');
        })->unique()->values();

        $totals = Sale::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->whereIn('created_by', $ids)->where('currency', 'PEN')
            ->where('date_sale', '>=', $start->copy()->startOfDay())
            ->where('date_sale', '<', $end->copy()->addDay()->startOfDay())
            ->where('state_annulled', false)
            ->where(function ($query) {
                $query->whereNull('internal_reversal_status')->orWhere('internal_reversal_status', '<>', 'reversed');
            })
            ->groupBy('created_by')->selectRaw('created_by, SUM(importe_total) AS total')
            ->pluck('total', 'created_by');

        $rows = [];
        foreach ($metas as $meta) {
            foreach ($meta->users as $user) {
                $target = (float) $meta->monto;
                $total = round((float) $totals->get($user->id, 0), 2);
                $rows[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'meta_id' => $meta->id,
                    'meta_nombre' => $meta->nombre,
                    'meta_monto' => $target,
                    'ventas_total' => $total,
                    'porcentaje' => $target > 0 ? round($total / $target * 100, 2) : 0,
                ];
            }
        }
        usort($rows, function ($a, $b) {
            return ($b['ventas_total'] <=> $a['ventas_total']) ?: ($a['user_id'] <=> $b['user_id']);
        });
        foreach ($rows as $index => &$row) {
            $row['puesto'] = $index + 1;
        }

        return $rows;
    }
}
