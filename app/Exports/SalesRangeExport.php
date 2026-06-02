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
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesRangeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle
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

    public function title(): string
    {
        return 'VENTAS';
    }

    public function collection()
    {
        $sales = Sale::with([
            'worker',
            'quote.customer',
            'quote.equipments.workforces',
            'partialPayments' => function ($q) {
                $q->where('state', 1);
            },
            'cashMovements' => function ($q) {
                $q->where('type', 'sale')
                    ->with(['cashRegister.cashBox', 'cashBoxSubtype'])
                    ->orderBy('id', 'desc');
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
            'Cliente',
            'Moneda',
            'Abonado',
            'Método de Pago',
            'Estado Despacho',
            'Comprobante',
            'Serv. Adic. No Facturados',
            'Total',
        ];
    }

    // ✅ Firma compatible con WithMapping
    public function map($row): array
    {
        /** @var \App\Sale $row */
        $cliente = $this->getNombreCliente($row);
        $metodoPago = $this->getMetodoPago($row);
        $totalTexto = $this->getTotalTexto($row);
        $estadoDespacho = strtoupper($row->dispatch_status ?? 'despachado');
        $estadoComprobante = $this->tieneComprobanteValido($row)
            ? 'CON COMPROBANTE'
            : 'SIN COMPROBANTE';

        $calc = $this->getAmountAndMethod($row);

        $this->grandTotal += (float) $row->importe_total;

        $totalNoBillable = 0;

        if ($row->quote) {
            $totalNoBillable = (float) $row->quote->equipments->sum(function ($equipment) {
                return $equipment->workforces
                    ->where('billable', false)
                    ->sum(function ($workforce) {
                        return (float) ($workforce->total ?? 0);
                    });
            });
        }

        return [
            'VENTA - ' . ($row->id),
            $row->formatted_sale_date ?? Carbon::parse($row->date_sale)->isoFormat('DD/MM/YYYY [a las] h:mm A'),
            $cliente,
            ($row->currency == 'PEN') ? 'Soles' : 'Dólares',
            $totalTexto,
            $metodoPago,
            $estadoDespacho,
            $estadoComprobante,
            number_format($totalNoBillable, 2, '.', ''),
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
                $event->sheet->setCellValue('I' . $totalRow, 'TOTAL');
                $event->sheet->setCellValue('J' . $totalRow, number_format($this->grandTotal, 2, '.', ''));

                // Estilo
                $event->sheet->getStyle('J' . $totalRow . ':J' . $totalRow)->getFont()->setBold(true);
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

    private function getNombreCliente($sale)
    {
        if ($sale->quote) {
            if ($sale->quote->customer) {
                return $sale->quote->customer->business_name;
            }

            return 'COTIZACION SIN CLIENTE';
        }

        return 'VENTA DIRECTA';
    }

    private function getMetodoPago($sale)
    {
        if ($sale->pagos_parciales_venta === 's') {
            return 'PAGO PARCIAL';
        }

        $movement = $sale->cashMovements->first();

        if (!$movement || !$movement->cashRegister || !$movement->cashRegister->cashBox) {
            return 'SIN MOVIMIENTO';
        }

        $cashBox = $movement->cashRegister->cashBox;

        if ($cashBox->type === 'cash') {
            return strtoupper($cashBox->name);
        }

        if ($movement->cashBoxSubtype) {
            return strtoupper($cashBox->name . ' - ' . $movement->cashBoxSubtype->name);
        }

        return strtoupper($cashBox->name);
    }

    private function getTotalTexto($sale)
    {
        $totalVenta = (float) $sale->importe_total;

        if ($sale->pagos_parciales_venta === 's') {
            $totalAbonado = (float) $sale->partialPayments->sum('amount');

            return number_format($totalAbonado, 2, '.', '') . ' / ' . number_format($totalVenta, 2, '.', '');
        }

        return number_format($totalVenta, 2, '.', '');
    }

    private function tieneComprobanteValido($sale)
    {
        return in_array($sale->type_document, ['01', '03'])
            && $sale->sunat_status !== 'Error';
    }
}
