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

    private function resolveUnitCost($detail): float
    {
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }
        return (float)(optional($detail->material)->unit_price ?? 0);
    }

    private function buildBaseQuery()
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
            'Trabajador',
            'Cantidad Vendida',
            'Total Venta',
            'Total Costo',
            'Utilidad',
        ];
    }

    public function map($sale): array
    {
        $quantity_sale = 0;
        $total_sale    = 0;
        $total_cost    = 0;
        $total_utility = 0;

        foreach ($sale->details as $detail) {

            // ✅ Solo materiales
            if (empty($detail->material_id)) {
                continue;
            }

            $qty        = (float)$detail->quantity;
            $lineTotal  = (float)$detail->total;
            $lineDisc   = (float)$detail->discount;
            $netLine    = $lineTotal - $lineDisc;

            $unitCost   = $this->resolveUnitCost($detail);
            $costLine   = $unitCost * $qty;

            $quantity_sale += $qty;
            $total_sale    += $netLine;
            $total_cost    += $costLine;
            $total_utility += ($netLine - $costLine);
        }

        return [
            $sale->id,
            optional($sale->created_at)->format('d/m/Y'),
            trim(
                (optional($sale->worker)->first_name ?? '') . ' ' .
                (optional($sale->worker)->last_name ?? '')
            ),
            $quantity_sale,
            round($total_sale, 2),
            round($total_cost, 2),
            round($total_utility, 2),
        ];
    }
}