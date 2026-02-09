<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashFlowSummarySheet implements FromArray, WithTitle
{
    private $start;
    private $end;

    private $totalIncome;
    private $expenseCaja;
    private $expenseFinanzas;
    private $compras;
    private $servicios;
    private $totalExpense;
    private $profit;

    public function __construct(
        Carbon $start,
        Carbon $end,
        $totalIncome,
        $expenseCaja,
        $expenseFinanzas,
        $compras,
        $servicios,
        $totalExpense,
        $profit
    ) {
        $this->start = $start;
        $this->end = $end;

        $this->totalIncome = $totalIncome;
        $this->expenseCaja = $expenseCaja;
        $this->expenseFinanzas = $expenseFinanzas;
        $this->compras = $compras;
        $this->servicios = $servicios;
        $this->totalExpense = $totalExpense;
        $this->profit = $profit;
    }

    public function title(): string
    {
        return 'RESUMEN';
    }

    public function array(): array
    {
        return [
            ['Rango', $this->start->format('d/m/Y') . ' - ' . $this->end->format('d/m/Y')],
            ['Total Ingresos', number_format($this->totalIncome, 2, '.', '')],

            ['Egresos Caja', number_format($this->expenseCaja, 2, '.', '')],
            ['Egresos Finanzas', number_format($this->expenseFinanzas, 2, '.', '')],
            ['Compras', number_format($this->compras, 2, '.', '')],
            ['Servicios', number_format($this->servicios, 2, '.', '')],

            ['Total Egresos', number_format($this->totalExpense, 2, '.', '')],
            ['Utilidad', number_format($this->profit, 2, '.', '')],
        ];
    }
}
