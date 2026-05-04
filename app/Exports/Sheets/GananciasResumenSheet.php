<?php

namespace App\Exports\Sheets;

use App\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Worker;

class GananciasResumenSheet implements FromArray, WithTitle, WithEvents
{
    private $creator;
    private $startDate;
    private $endDate;

    public function __construct($creator, $startDate, $endDate)
    {
        $this->creator   = $creator;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function title(): string
    {
        return 'RESUMEN';
    }

    private function resolveUnitCostO($detail): float
    {
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }
        return (float)(optional($detail->material)->unit_price ?? 0);
    }

    private function resolveUnitCost($detail): float
    {
        if (!is_null($detail->unit_cost) && (float) $detail->unit_cost > 0) {
            return (float) $detail->unit_cost;
        }

        if (
            !is_null($detail->total_cost) &&
            (float) $detail->total_cost > 0 &&
            (float) $detail->quantity > 0
        ) {
            return (float) $detail->total_cost / (float) $detail->quantity;
        }

        if ($detail->stockItem && $detail->stockItem->inventoryLevels) {
            $avgCost = (float) $detail->stockItem->inventoryLevels->avg('average_cost');

            if ($avgCost > 0) {
                return $avgCost;
            }
        }

        return (float) (optional($detail->material)->unit_price ?? 0);
    }

    private function buildBaseQueryO()
    {
        $query = Sale::with(['worker', 'details.material'])
            ->where('state_annulled', false)
            ->orderBy('created_at', 'DESC');

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $this->startDate);
            $fechaFinal  = Carbon::createFromFormat('d/m/Y', $this->endDate);

            $query->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal);
        }

        if (!empty($this->creator)) {
            $query->where('worker_id', $this->creator);
        }

        return $query;
    }

    private function buildBaseQuery()
    {
        $query = Sale::with([
            'worker',
            'details.material',
            'details.stockItem.inventoryLevels',
        ])
            ->where('state_annulled', false)
            ->orderBy('created_at', 'DESC');

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $this->startDate);
            $fechaFinal  = Carbon::createFromFormat('d/m/Y', $this->endDate);

            $query->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal);
        }

        if (!empty($this->creator)) {
            $query->where('worker_id', $this->creator);
        }

        return $query;
    }

    private function calculateTotalsO(): array
    {
        $total_quantity_sale_global = 0;
        $total_total_sale_global    = 0;
        $total_total_utility_global = 0;

        $allSales = $this->buildBaseQuery()->get();

        foreach ($allSales as $sale) {
            foreach ($sale->details as $detail) {

                // ✅ solo materiales
                if (empty($detail->material_id)) {
                    continue;
                }

                $qty       = (float) $detail->quantity;
                $lineTotal = (float) $detail->total;
                $lineDisc  = (float) $detail->discount;

                $netLine = $lineTotal - $lineDisc;

                $unitCost = $this->resolveUnitCost($detail);
                $costLine = $unitCost * $qty;

                $total_quantity_sale_global += $qty;
                $total_total_sale_global    += $netLine;
                $total_total_utility_global += ($netLine - $costLine);
            }
        }

        return [
            'quantity_sale_sum' => $total_quantity_sale_global,
            'total_sale_sum'    => round($total_total_sale_global, 2),
            'total_utility_sum' => round($total_total_utility_global, 2),
        ];
    }

    private function calculateTotals(): array
    {
        $total_quantity_sale_global = 0;
        $total_total_sale_global    = 0;
        $total_total_utility_global = 0;

        $allSales = $this->buildBaseQuery()->get();

        foreach ($allSales as $sale) {
            foreach ($sale->details as $detail) {

                // Solo productos/materiales, excluye servicios/workforces
                if (empty($detail->stock_item_id) && empty($detail->material_id)) {
                    continue;
                }

                $qty       = (float) ($detail->quantity ?? 0);
                $lineTotal = (float) ($detail->total ?? 0);
                $lineDisc  = (float) ($detail->discount ?? 0);

                $netLine = $lineTotal - $lineDisc;

                $unitCost = $this->resolveUnitCost($detail);

                $costLine = !is_null($detail->total_cost) && (float) $detail->total_cost > 0
                    ? (float) $detail->total_cost
                    : $unitCost * $qty;

                $total_quantity_sale_global += $qty;
                $total_total_sale_global    += $netLine;
                $total_total_utility_global += ($netLine - $costLine);
            }
        }

        return [
            'quantity_sale_sum' => $total_quantity_sale_global,
            'total_sale_sum'    => round($total_total_sale_global, 2),
            'total_utility_sum' => round($total_total_utility_global, 2),
        ];
    }

    public function array(): array
    {
        $totals = $this->calculateTotals();

        return [
            ['REPORTE DE GANANCIAS POR TRABAJADOR'],
            [''],
            ['Filtros'],
            ['Trabajador', $this->getWorkerName()],
            ['Fecha inicio', $this->startDate ?: 'TODAS'],
            ['Fecha fin', $this->endDate ?: 'TODAS'],
            [''],
            ['Totales (solo materiales)'],
            ['Cantidad total vendida', $totals['quantity_sale_sum']],
            ['Total vendido', $totals['total_sale_sum']],
            ['Utilidad total', $totals['total_utility_sum']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Ajuste de ancho simple
                $event->sheet->getDelegate()->getColumnDimension('A')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);

                // Título en negrita
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('A3')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A8')->getFont()->setBold(true);
            }
        ];
    }

    private function getWorkerName(): string
    {
        if (empty($this->creator)) return 'TODOS';

        $worker = Worker::find($this->creator);
        return $worker ? $worker->first_name." ".$worker->last_name : ('ID ' . $this->creator);
    }
}