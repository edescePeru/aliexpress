<?php

namespace App\Exports;

use App\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesRangeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $start;
    protected $end;

    private $grandTotal = 0;
    private $rowCount = 0;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    public function collection()
    {
        $sales = Sale::with([
            'worker',
            'cashMovements' => function ($q) {
                $q->where('type', 'sale')
                    ->with('cashBoxSubtype')
                    ->orderBy('id', 'asc');
            }
        ])
            ->whereBetween('created_at', [$this->start, $this->end])
            ->where('state_annulled', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        // Guardamos cantidad para el TOTAL sin re-consultar
        $this->rowCount = $sales->count();

        return $sales;
    }

    public function headings(): array
    {
        return [
            'Código',
            'Fecha Venta',
            'Moneda',
            //'Total',
            'Método de Pago',
            'Regularizado',
            'Total',
        ];
    }

    // ✅ Firma compatible con WithMapping
    public function map($row): array
    {
        /** @var \App\Sale $row */
        $calc = $this->getAmountAndMethod($row);

        $this->grandTotal += (float) $calc['amount'];

        return [
            'VENTA - ' . ($row->id),
            $row->formatted_sale_date ?? Carbon::parse($row->date_sale)->isoFormat('DD/MM/YYYY [a las] h:mm A'),
            $row->currency ?? '',
            //number_format($calc['amount'], 2, '.', ''),
            $calc['method'],
            $calc['is_regularized'] ? 'SI' : 'NO',
            number_format((float)$row->importe_total, 2, '.', ''),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // headings = fila 1, data empieza en fila 2
                $totalRow = 1 + $this->rowCount + 1;

                // Columnas: A Código, B Fecha, C Moneda, D Total, ...
                $event->sheet->setCellValue('E' . $totalRow, 'TOTAL');
                $event->sheet->setCellValue('F' . $totalRow, number_format($this->grandTotal, 2, '.', ''));

                // Estilo
                $event->sheet->getStyle('E' . $totalRow . ':F' . $totalRow)->getFont()->setBold(true);
            },
        ];
    }

    /**
     * Reglas:
     * - Si existe movimiento diferido (cashBoxSubtype.is_deferred=1):
     *      - regularize=0 => excluir (amount=0)
     *      - regularize=1 => amount_regularize si >0, sino amount
     * - Si NO diferido (efectivo incluido): amount = sale->importe_total
     * - Método:
     *      - Si hay subtype => usa subtype->name (o code)
     *      - Si no hay subtype => EFECTIVO
     */
    private function getAmountAndMethod($sale): array
    {
        $movements = $sale->cashMovements;

        // Monto SIEMPRE desde la venta
        $amount = (float) $sale->importe_total;

        // Si no hay movimientos, es efectivo
        if ($movements->isEmpty()) {
            return [
                'amount' => $amount,
                'method' => 'EFECTIVO',
                'is_regularized' => true,
            ];
        }

        $getSubtypeName = function ($m) {
            $name = optional($m->cashBoxSubtype)->name;
            $code = optional($m->cashBoxSubtype)->code;
            return strtoupper($name ?: ($code ?: 'EFECTIVO'));
        };

        // Buscar si hay método DIFERIDO (solo para flags y método)
        $deferred = $movements->first(function ($m) {
            return (int) optional($m->cashBoxSubtype)->is_deferred === 1;
        });

        if ($deferred) {
            return [
                'amount' => $amount, // ✅ SIEMPRE importe_total
                'method' => $getSubtypeName($deferred) . ($deferred->regularize ? '' : ' (NO REG.)'),
                'is_regularized' => (bool) $deferred->regularize,
            ];
        }

        // No diferido: puede ser efectivo u otro método inmediato
        $firstWithSubtype = $movements->first(function ($m) {
            return !is_null($m->cash_box_subtype_id) && !is_null($m->cashBoxSubtype);
        });

        $method = $firstWithSubtype ? $getSubtypeName($firstWithSubtype) : 'EFECTIVO';

        return [
            'amount' => $amount, // ✅ SIEMPRE importe_total
            'method' => $method,
            'is_regularized' => true,
        ];
    }
}
