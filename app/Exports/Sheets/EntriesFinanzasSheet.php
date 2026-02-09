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

class EntriesFinanzasSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle
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
        return 'EGRESOS FINANZAS';
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Fecha de Factura',
            'OC/OS',
            'Factura',
            'Tipo de Orden',
            'Proveedor',
            'Diferido',
            'Categoría',
            'Subtotal',
            'Impuestos',
            'Total',
        ];
    }

    public function map($row): array
    {
        $total = (float) $row->total;
        $this->total += $total;

        $diferido = ((string)$row->deferred_invoice === 'on') ? 'SI' : 'NO';

        return [
            $row->date_entry ? Carbon::parse($row->date_entry)->format('d/m/Y') : '-',
            $row->purchase_order ?? '-',
            $row->invoice ?? '-',
            $row->type_order ?? '-',
            optional($row->supplier)->business_name ?? '-',
            $diferido,
            optional($row->category_invoice)->name ?? '-',
            number_format((float)$row->sub_total, 2, '.', ''),
            number_format((float)$row->taxes, 2, '.', ''),
            number_format($total, 2, '.', ''),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalRow = 1 + $this->rowCount + 1;
                $event->sheet->setCellValue('I' . $totalRow, $this->totalLabel);
                $event->sheet->setCellValue('J' . $totalRow, number_format($this->total, 2, '.', ''));
                $event->sheet->getStyle('I' . $totalRow . ':J' . $totalRow)->getFont()->setBold(true);
            },
        ];
    }
}
