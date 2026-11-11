<?php

namespace App\Exports\Sheets;

use App\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GananciasReporteSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
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
        return 'REPORTE';
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
        return app(\App\Services\Sales\UserProfitReport::class)->query($this->creator, $this->startDate, $this->endDate)
            ->with(['details.material', 'details.stockItem'])
            ->orderByDesc('date_sale')->orderByDesc('id');
    }

    public function collection(): Collection
    {
        // 👇 Para Excel normalmente se exporta TODO sin paginar
        return $this->buildBaseQuery()->get();
    }

    public function headings(): array
    {
        return [
            'ID Venta',
            'Fecha',
            'Usuario',
            'Cantidad Vendida',
            'Total Venta',
            'Total Costo',
            'Utilidad',
        ];
    }

    public function map($sale): array
    {
        $report = app(\App\Services\Sales\UserProfitReport::class);
        $totals = $report->totals([$sale]);
        return [
            $sale->id,
            \Carbon\Carbon::parse($sale->date_sale)->format('d/m/Y'),
            $report->author($sale),
            $totals['quantity_sale_sum'],
            $totals['total_sale_sum'],
            $totals['total_cost_sum'] ?? 'Sin costo registrado',
            $totals['total_utility_sum'] ?? 'Sin costo registrado',
        ];
    }
}