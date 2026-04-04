<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// Models
use App\CashMovement;
use App\Entry;
use App\OrderService;
use App\Quote;

class CashFlowRangeExport implements WithMultipleSheets
{
    private $start;
    private $end;

    // datasets
    private $incomeMovements;
    private $expenseMovements;
    private $entriesFinanzas;
    private $entriesCompras;
    private $orderServices;
    private $quotes;

    // totals
    private $totalIncome = 0.0;
    private $totalExpenseCaja = 0.0;
    private $totalExpenseFinanzas = 0.0;
    private $totalCompras = 0.0;
    private $totalServicios = 0.0;

    private $totalServiciosAdicionalesSinFacturar = 0.0;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;

        // =========================
        // 1) INGRESOS (CashMovement)
        // - income (todos)
        // - sale solo regularize=1
        // =========================
        $this->incomeMovements = $this->queryCashMovementsBase()
            ->where('cash_movements.arqueo', false)
            ->whereDate('cash_movements.created_at', '>=', $this->start->toDateString())
            ->whereDate('cash_movements.created_at', '<=', $this->end->toDateString())
            ->where(function ($q) {
                $q->where('cash_movements.type', 'income')
                    ->orWhere(function ($sub) {
                        $sub->where('cash_movements.type', 'sale')
                            ->where('cash_movements.regularize', 1);
                    });
            })
            ->get();

        $this->totalIncome = (float) $this->incomeMovements->sum(function ($r) {
            return (float) ($r->amount ?? 0);
        });

        // =========================
        // 2) EGRESOS CAJA (CashMovement expense)
        // =========================
        $this->expenseMovements = $this->queryCashMovementsBase()
            ->where('cash_movements.arqueo', false)
            ->where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $this->start->toDateString())
            ->whereDate('cash_movements.created_at', '<=', $this->end->toDateString())
            ->get();

        $this->totalExpenseCaja = (float) $this->expenseMovements->sum(function ($r) {
            return (float) ($r->amount ?? 0);
        });

        // =========================
        // 3) EGRESOS FINANZAS (Entry finance=1)
        // filtro created_at, mostrar date_entry
        // =========================
        $this->entriesFinanzas = Entry::with(['supplier', 'category_invoice', 'details'])
            ->whereDate('created_at', '>=', $this->start->toDateString())
            ->whereDate('created_at', '<=', $this->end->toDateString())
            ->where('finance', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->totalExpenseFinanzas = (float) $this->entriesFinanzas->sum(function ($e) {
            return (float) $e->total; // accessor string num -> float
        });

        // =========================
        // 4) COMPRAS (Entry Por compra)
        // =========================
        $this->entriesCompras = Entry::with(['supplier', 'details'])
            ->where('entry_type', 'Por compra')
            ->whereDate('created_at', '>=', $this->start->toDateString())
            ->whereDate('created_at', '<=', $this->end->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        $this->totalCompras = (float) $this->entriesCompras->sum(function ($e) {
            return (float) $e->total;
        });

        // =========================
        // 5) SERVICIOS (OrderService regularize='r')
        // filtro created_at, mostrar date_order/date_delivery
        // =========================
        $this->orderServices = OrderService::with(['supplier', 'approved_user'])
            ->where('regularize', 'r')
            ->whereDate('created_at', '>=', $this->start->toDateString())
            ->whereDate('created_at', '<=', $this->end->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        $this->totalServicios = (float) $this->orderServices->sum(function ($o) {
            return (float) ($o->total ?? 0);
        });

        // =========================
        // 6) SERVICIOS ADICIONALES SIN FACTURAR (Quote -> equipments -> workforces)
        // =========================
        $this->quotes = Quote::with([
            'equipments.workforces'
        ])
            ->whereDate('date_quote', '>=', $this->start->toDateString())
            ->whereDate('date_quote', '<=', $this->end->toDateString())
            ->get();

        $this->totalServiciosAdicionalesSinFacturar = (float) $this->quotes->sum(function ($quote) {
            return $quote->equipments->sum(function ($equipment) {
                return $equipment->workforces
                    ->where('billable', false)
                    ->sum(function ($workforce) {
                        return (float) ($workforce->total ?? 0);
                    });
            });
        });
    }

    public function sheets(): array
    {
        $totalExpense = $this->totalExpenseCaja + $this->totalExpenseFinanzas + $this->totalCompras + $this->totalServicios;
        $profit = $this->totalIncome - $totalExpense;

        return [
            new Sheets\CashFlowSummarySheet(
                $this->start,
                $this->end,
                $this->totalIncome,
                $this->totalExpenseCaja,
                //$this->totalExpenseFinanzas,
                $this->totalServiciosAdicionalesSinFacturar,
                $this->totalCompras,
                $this->totalServicios,
                $totalExpense,
                $profit
            ),

            new Sheets\CashMovementsSheet(
                'INGRESOS',
                $this->incomeMovements,
                'TOTAL INGRESOS'
            ),

            new Sheets\CashMovementsSheet(
                'EGRESOS CAJA',
                $this->expenseMovements,
                'TOTAL EGRESOS CAJA'
            ),

            //new Sheets\EntriesFinanzasSheet($this->entriesFinanzas, 'TOTAL EGRESOS FINANZAS'),

            new Sheets\EntriesComprasSheet($this->entriesCompras, 'TOTAL COMPRAS'),

            new Sheets\OrderServicesSheet($this->orderServices, 'TOTAL SERVICIOS'),
        ];
    }

    /**
     * Base query para CashMovement con joins (igual a tu buildListResponse)
     */
    private function queryCashMovementsBase()
    {
        return CashMovement::query()
            ->join('cash_registers', 'cash_registers.id', '=', 'cash_movements.cash_register_id')
            ->join('cash_boxes', 'cash_boxes.id', '=', 'cash_registers.cash_box_id')
            ->join('users', 'users.id', '=', 'cash_registers.user_id')
            ->leftJoin('cash_box_subtypes', 'cash_box_subtypes.id', '=', 'cash_movements.cash_box_subtype_id')
            ->select([
                'cash_movements.id',
                'cash_movements.type',
                'cash_movements.amount',
                'cash_movements.description',
                'cash_movements.observation',
                'cash_movements.created_at',
                'cash_movements.sale_id',
                'cash_movements.amount_regularize',
                'cash_movements.commission',
                'cash_movements.regularize',
                'cash_movements.arqueo',

                'cash_boxes.name as cash_box_name',
                'users.name as user_name',

                'cash_box_subtypes.name as subtype_name',
                'cash_box_subtypes.code as subtype_code',
            ])
            ->orderBy('cash_movements.created_at', 'desc')
            ->orderBy('cash_movements.id', 'desc');
    }
}
