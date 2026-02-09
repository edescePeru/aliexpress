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

class OrderServicesSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle
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
        return 'SERVICIOS';
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Código',
            'Fecha Orden',
            'Fecha Entrega',
            'Observación',
            'Proveedor',
            'Aprobado por',
            'Moneda',
            'Total',
        ];
    }

    public function map($row): array
    {
        $total = (float) ($row->total ?? 0);
        $this->total += $total;

        return [
            $row->id,
            $row->code ?? '-',
            $row->date_order ? Carbon::parse($row->date_order)->format('d/m/Y') : '-',
            $row->date_delivery ? Carbon::parse($row->date_delivery)->format('d/m/Y') : '-',
            $row->observation ?? '-',
            optional($row->supplier)->business_name ?? '-',
            optional($row->approved_user)->name ?? '-',
            $row->currency_order ?? '-',
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
