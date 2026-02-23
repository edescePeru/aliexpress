<?php

namespace App\Exports;

use App\Exports\Sheets\GananciasResumenSheet;
use App\Exports\Sheets\GananciasReporteSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GananciasTrabajadorExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            new GananciasResumenSheet($this->creator, $this->startDate, $this->endDate),
            new GananciasReporteSheet($this->creator, $this->startDate, $this->endDate),
        ];
    }
}