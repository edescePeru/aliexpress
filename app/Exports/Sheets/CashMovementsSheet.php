<?php

namespace App\Exports\Sheets;

use App\CashBoxSubtype;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class CashMovementsSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithTitle
{
    private $title;
    private $rows;
    private $totalLabel;

    private $rowCount = 0;
    private $totalAmount = 0.0;

    public function __construct(string $title, Collection $rows, string $totalLabel)
    {
        $this->title = $title;
        $this->rows = $rows;
        $this->totalLabel = $totalLabel;
        $this->rowCount = $rows->count();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'N°',
            'Fecha',
            'Usuario',
            'Caja',
            'Tipo',
            'Subtipo',
            'Estado',
            'Monto',
            'Abonado',
            'Comisión',
            'Descripción',
        ];
    }

    public function map($row): array
    {
        $regularize = (int) ($row->regularize ?? 0);
        $estado = $regularize === 1 ? 'Confirmado' : 'Pendiente';

        $amount = (float) ($row->amount ?? 0);
        $this->totalAmount += $amount;

        $desc = (string) ($row->description ?? '-');
        if (!empty($row->observation)) {
            $desc .= ' | ' . $row->observation;
        }

        $cashBoxSubTypeId = $row->cash_box_subtype_id;

        $cashBoxSubType = CashBoxSubtype::find($cashBoxSubTypeId);

        return [
            $row->id,
            Carbon::parse($row->created_at)->format('d/m/Y h:i A'),
            $row->user_name ?? '-',
            $row->cash_box_name ?? '-',
            $this->formatTypeLabel($row->type, $row->regularize),
            $cashBoxSubType->name ?? '-',
            $estado,
            number_format($amount, 2, '.', ''),
            $row->amount_regularize !== null ? number_format((float)$row->amount_regularize, 2, '.', '') : '-',
            $row->commission !== null ? number_format((float)$row->commission, 2, '.', '') : '-',
            $desc,
        ];
    }

    public function formatTypeLabel($typeRaw, $regularize) {
        if (($regularize === 0 || $regularize === '0') && ($typeRaw === 'sale' || $typeRaw === 'income')) return 'Regularizar';
        if ($typeRaw === 'sale') return 'Venta';
        if ($typeRaw === 'income') return 'Ingreso';
        if ($typeRaw === 'expense') return 'Egreso';
        return $typeRaw || '-';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalRow = 1 + $this->rowCount + 1; // headings(1) + data + 1

                // TOTAL en columnas G/H? Nosotros ponemos etiqueta en G y monto en H (Estado=G, Monto=H)
                $event->sheet->setCellValue('G' . $totalRow, $this->totalLabel);
                $event->sheet->setCellValue('H' . $totalRow, number_format($this->totalAmount, 2, '.', ''));

                $event->sheet->getStyle('G' . $totalRow . ':H' . $totalRow)->getFont()->setBold(true);
            },
        ];
    }
}
