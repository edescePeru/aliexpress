<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class EntriesComprasSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle
{
    private $rows;
    private $rowCount = 0;
    private $total = 0.0;
    private $totalLabel;

    public function __construct(Collection $rows, string $totalLabel)
    {
        $this->rows = $rows;
        $this->rowCount = $rows->count();
        $this->totalLabel = $totalLabel;
    }

    public function title(): string
    {
        return 'COMPRAS';
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Guía de remisión',
            'Orden de compra',
            'Factura',
            'Tipo de entrada',
            'Proveedor',
            'Fecha',
            'Diferido',
            'Moneda',
            'Total',
        ];
    }

    public function map($row): array
    {
        $total = (float) $row->total;
        $this->total += $total;

        $diferido = ((string)$row->deferred_invoice === 'on') ? 'SI' : 'NO';

        return [
            $row->referral_guide ?? '-',
            $row->purchase_order ?? '-',
            $row->invoice ?? '-',
            $row->entry_type ?? '-',
            optional($row->supplier)->business_name ?? '-',
            $row->date_entry ? Carbon::parse($row->date_entry)->format('d/m/Y') : '-',
            $diferido,
            $row->currency_invoice ?? '-',
            number_format($total, 2, '.', ''),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalRow = 1 + $this->rowCount + 1;
                $event->sheet->setCellValue('H' . $totalRow, $this->totalLabel);
                $event->sheet->setCellValue('I' . $totalRow, number_format($this->total, 2, '.', ''));
                $event->sheet->getStyle('H' . $totalRow . ':I' . $totalRow)->getFont()->setBold(true);
            },
        ];
    }
}
