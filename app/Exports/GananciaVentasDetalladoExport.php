<?php

namespace App\Exports;

use App\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GananciaVentasDetalladoExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithColumnFormatting
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
        return 'VENTAS';
    }

    private function buildBaseQuery()
    {
        $query = Sale::with([
            'worker',
            'details.material',
            'details.materialPresentation',
            'cashMovements.cashBoxSubtype',
            'cashMovements.cashRegister.cashBox',
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

    private function resolveUnitCost($detail): float
    {
        // 1) costo guardado en detalle
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }

        // 2) fallback a costo actual del material (ventas antiguas)
        return (float)(optional($detail->material)->unit_price ?? 0);
    }

    private function resolveTotalCost($detail, float $unitCost, float $qty): float
    {
        if (!is_null($detail->total_cost) && (float)$detail->total_cost > 0) {
            return (float)$detail->total_cost;
        }
        return $unitCost * $qty;
    }

    private function resolveSaleDateDMY($sale): string
    {
        if (empty($sale->fecha_emision)) {
            return '';
        }

        return Carbon::parse($sale->fecha_emision)->format('d/m/Y');
    }

    private function resolveSaleDateVentaDMY($sale): string
    {
        $date = $sale->date_sale ?: $sale->created_at;
        return $date ? Carbon::parse($date)->format('d/m/Y') : '';
    }

    private function resolveWorkerFullName($sale): string
    {
        $w = $sale->worker;
        return trim(($w->first_name ?? '') . ' ' . ($w->last_name ?? ''));
    }

    private function resolveOrigen($sale): string
    {
        return !empty($sale->quote_id) ? 'Cotización' : 'Punto de Venta';
    }

    private function resolveCliente($sale): string
    {
        // puede ser null en POS sin comprobante
        return (string)($sale->nombre_cliente ?? '');
    }

    private function resolveFormaPago($sale): string
    {
        // Seleccionar el PRIMER movimiento (por id ascendente)
        $mv = $sale->cashMovements->sortBy('created_at')->first();

        if (!$mv) return 'N/D';

        // Si tiene subtipo (yape/plin/transferencia/etc)
        if (!empty($mv->cash_box_subtype_id)) {
            return (string)(optional($mv->cashBoxSubtype)->name ?? 'N/D');
        }

        // Si no tiene subtipo => efectivo o bancario por CashBox name
        $cashBoxName = optional(optional($mv->cashRegister)->cashBox)->name;
        return $cashBoxName ?: 'Efectivo';
    }

    private function resolvePresentacion($detail): string
    {
        if (empty($detail->material_presentation_id)) {
            return 'UND';
        }

        $presentation = $detail->materialPresentation ?? null;

        if (!$presentation) {
            return 'SET';
        }

        // Si algún día usan label, lo toma automáticamente
        $label = trim($presentation->label ?? '');

        if ($label === '') {
            $label = 'SET';
        }

        $qtyUnd = (int)$presentation->quantity;

        return $label . ' (' . $qtyUnd . ' und)';
    }

    private function resolveDescripcion($detail): string
    {
        // prioridad: description guardada en detalle, si no material->full_name
        return (string)($detail->description ?: (optional($detail->material)->full_name ?? ''));
    }

    public function headings(): array
    {
        return [
            'Origen',
            'Venta ID',
            'Fecha Venta',
            'Trabajador',
            'Fecha Emisión',
            'Cliente',
            'Forma de Pago',
            'UND',
            'PRESENT.',
            'DESCRIPCIÓN',
            'P.U.V (S/)',   // Precio Unitario Venta
            'P.T.V (S/)',   // Precio Total Venta (neto)
            'PUC (S/)',     // Precio Unitario Costo
            'PUCT (S/)',    // Precio Unitario Costo Total (Total costo)
            'GANANCIA (S/)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => '#,##0.00', // PUV
            'K' => '#,##0.00', // PTV
            'L' => '#,##0.00', // PUC
            'M' => '#,##0.00', // PUCT
            'N' => '#,##0.00', // GANANCIA
        ];
    }

    public function collection(): Collection
    {
        $sales = $this->buildBaseQuery()->get();

        $rows = collect();

        foreach ($sales as $sale) {

            // filtrar detalles SOLO materiales
            $details = $sale->details->filter(function ($d) {
                return !empty($d->material_id);
            })->values();

            if ($details->count() === 0) {
                continue;
            }

            $origen     = $this->resolveOrigen($sale);
            $saleId     = $sale->id;
            $fechaVentaDMY   = $this->resolveSaleDateVentaDMY($sale);
            $workerName = $this->resolveWorkerFullName($sale);
            $fechaDMY   = $this->resolveSaleDateDMY($sale);
            $cliente    = $this->resolveCliente($sale);
            $formaPago  = $this->resolveFormaPago($sale);

            foreach ($details as $idx => $detail) {

                $qty = (float)$detail->quantity;

                $lineTotal    = (float)$detail->total;
                $lineDiscount = (float)$detail->discount;
                $netLine      = $lineTotal - $lineDiscount;

                $puv = (float)$detail->price; // precio unitario venta
                $ptv = $netLine;              // total neto de línea

                $unitCost  = $this->resolveUnitCost($detail);
                $totalCost = $this->resolveTotalCost($detail, $unitCost, $qty);

                $ganancia = $netLine - $totalCost;

                // ✅ Cabecera solo en la primera fila de cada venta
                $isFirst = ($idx === 0);

                $rows->push([
                    $isFirst ? $origen : '',
                    $isFirst ? $saleId : '',
                    $isFirst ? $fechaVentaDMY : '',
                    $isFirst ? $workerName : '',
                    $isFirst ? $fechaDMY : '',
                    $isFirst ? $cliente : '',
                    $isFirst ? $formaPago : '',

                    $qty,
                    $this->resolvePresentacion($detail),
                    $this->resolveDescripcion($detail),
                    round($puv, 2),
                    round($ptv, 2),
                    round($unitCost, 2),
                    round($totalCost, 2),
                    round($ganancia, 2),
                ]);
            }
        }

        return $rows;
    }
}
