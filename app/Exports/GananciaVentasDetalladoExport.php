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

    private function buildBaseQueryO()
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

    private function buildBaseQuery()
    {
        return app(\App\Services\Sales\UserProfitReport::class)->query($this->creator, $this->startDate, $this->endDate)
            ->with(['details.material', 'details.stockItem'])
            ->orderByDesc('date_sale')->orderByDesc('id');
    }

    private function resolveUnitCostO($detail): float
    {
        // 1) costo guardado en detalle
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }

        // 2) fallback a costo actual del material (ventas antiguas)
        return (float)(optional($detail->material)->unit_price ?? 0);
    }

    private function resolveUnitCost($detail): float
    {
        // 1) costo unitario guardado directamente (lo ideal)
        if (!is_null($detail->unit_cost) && (float) $detail->unit_cost > 0) {
            return (float) $detail->unit_cost;
        }

        // 2) calcular desde total_cost si existe
        if (
            !is_null($detail->total_cost) &&
            (float) $detail->total_cost > 0 &&
            (float) $detail->quantity > 0
        ) {
            return (float) $detail->total_cost / (float) $detail->quantity;
        }

        // 3) fallback moderno: costo promedio del stock_item
        if ($detail->stockItem && $detail->stockItem->inventoryLevels) {
            $avgCost = (float) $detail->stockItem->inventoryLevels->avg('average_cost');

            if ($avgCost > 0) {
                return $avgCost;
            }
        }

        // 4) fallback legacy (ventas antiguas)
        return (float) (optional($detail->material)->unit_price ?? 0);
    }

    private function resolveTotalCostO($detail, float $unitCost, float $qty): float
    {
        if (!is_null($detail->total_cost) && (float)$detail->total_cost > 0) {
            return (float)$detail->total_cost;
        }
        return $unitCost * $qty;
    }

    private function resolveTotalCost($detail, float $unitCost, float $qty): float
    {
        // 1) ya guardado en el detalle (lo ideal)
        if (!is_null($detail->total_cost) && (float) $detail->total_cost > 0) {
            return (float) $detail->total_cost;
        }

        // 2) fallback cálculo
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
        return app(\App\Services\Sales\UserProfitReport::class)->author($sale);
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
        $labels = [];
        $active = [];
        $inactive = [];
        $movements = $sale->cashMovements->keyBy('id');
        foreach ($sale->partialPayments as $payment) {
            if ((int) $payment->sale_id !== (int) $sale->id
                || (int) $payment->tenant_id !== (int) $sale->tenant_id
                || (int) $payment->company_id !== (int) $sale->company_id
                || (int) $payment->branch_id !== (int) $sale->branch_id
                || !$payment->cash_movement_id) {
                continue;
            }
            $id = (int) $payment->cash_movement_id;
            if ((int) $payment->state !== 1) {
                $inactive[$id] = true;
                continue;
            }
            $active[$id] = true;
            $movement = $payment->cashMovement;
            if ($movement && (int) $movement->id === $id) {
                $movements->put($id, $movement);
            }
        }
        foreach ($movements->sortBy('id') as $movement) {
            $id = (int) $movement->id;
            $direct = (int) $movement->sale_id === (int) $sale->id;
            $partial = $movement->sale_id === null && isset($active[$id]);
            if ((!$direct && !$partial) || isset($inactive[$id])
                || (int) $movement->tenant_id !== (int) $sale->tenant_id
                || (int) $movement->company_id !== (int) $sale->company_id
                || (int) $movement->branch_id !== (int) $sale->branch_id
                || !in_array($movement->type, ['sale', 'income'], true)
                || $movement->cash_movement_origin_id
                || $movement->regularization_source_id
                || $movement->deferred_cancelled_at) {
                continue;
            }

            $label = 'N/D';
            $box = $movement->cashBox;
            if (!$box && $movement->cash_register_id) {
                $register = $movement->cashRegister;
                if ($register
                    && (int) $register->tenant_id === (int) $sale->tenant_id
                    && (int) $register->company_id === (int) $sale->company_id
                    && (int) $register->branch_id === (int) $sale->branch_id) {
                    $box = $register->cashBox;
                }
            }
            if ($box
                && (int) $box->tenant_id === (int) $sale->tenant_id
                && (int) $box->company_id === (int) $sale->company_id
                && ($box->branch_id === null || (int) $box->branch_id === (int) $sale->branch_id)) {
                if ($movement->cash_box_subtype_id) {
                    $subtype = $movement->cashBoxSubtype;
                    if ($subtype
                        && (int) $subtype->tenant_id === (int) $sale->tenant_id
                        && (int) $subtype->company_id === (int) $sale->company_id
                        && (int) $subtype->cash_box_id === (int) $box->id) {
                        $label = trim((string) $subtype->name) ?: 'N/D';
                    }
                } else {
                    $label = trim((string) $box->name) ?: 'N/D';
                }
            }
            $labels[$label] = $label;
        }

        if (!$labels) {
            return 'N/D';
        }
        return count($labels) > 1 ? 'Mixto: ' . implode(' / ', $labels) : reset($labels);
    }

    private function resolvePresentacion($detail): string
    {
        $snapshot = trim((string) $detail->presentation_label_snapshot);
        $units = (int) $detail->units_per_pack;

        // La presentación guardada en la venta tiene prioridad sobre el catálogo actual.
        if ($detail->packs !== null || $snapshot !== '') {
            $label = $snapshot !== '' ? $snapshot : 'Paquete';
            return $units > 0 ? $label . ' (' . $units . ' und)' : $label;
        }

        if (empty($detail->material_presentation_id)) {
            return 'UND';
        }

        // Compatibilidad con ventas antiguas sin snapshot.
        $presentation = $detail->materialPresentation;
        if (!$presentation
            || (int) $presentation->tenant_id !== (int) $detail->tenant_id
            || (int) $presentation->material_id !== (int) $detail->material_id) {
            return 'SET';
        }

        $label = trim((string) $presentation->label);
        $label = $label !== '' ? $label : 'SET';
        return $label . ' (' . (int) $presentation->quantity . ' und)';
    }

    private function resolveDescripcionO($detail): string
    {
        // prioridad: description guardada en detalle, si no material->full_name
        return (string)($detail->description ?: (optional($detail->material)->full_name ?? ''));
    }

    private function resolveDescripcion($detail): string
    {
        // 1) descripción guardada en el detalle (máxima prioridad)
        if (!empty($detail->description)) {
            return (string) $detail->description;
        }

        // 2) stock item (nuevo modelo)
        if ($detail->stockItem) {
            return (string) ($detail->stockItem->display_name ?? '');
        }

        // 3) fallback legacy
        return (string) (optional($detail->material)->full_name ?? '');
    }

    public function headings(): array
    {
        return [
            'Origen',
            'Venta ID',
            'Fecha Venta',
            'Usuario',
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
        return array_fill_keys(['K', 'L', 'M', 'N', 'O'], '#,##0.00########');
    }

    public function collection(): Collection
    {
        $sales = $this->buildBaseQuery()->get();

        $rows = collect();

        foreach ($sales as $sale) {

            $details = $sale->details->values();

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

                [$qty, $ptv, $totalCost, $ganancia] = app(\App\Services\Sales\UserProfitReport::class)->line($detail);
                $puv = (float) $detail->price;
                $unitCost = $totalCost !== null && $qty > 0 ? $totalCost / $qty : null;

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
                    $puv,
                    $ptv,
                    $unitCost === null ? 'Sin costo registrado' : $unitCost,
                    $totalCost === null ? 'Sin costo registrado' : $totalCost,
                    $ganancia === null ? 'Sin costo registrado' : $ganancia,
                ]);
            }
        }

        return $rows;
    }
}
