<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashMovement;
use App\CashRegister;
use App\Entry;
use App\OrderService;
use App\Sale;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GraphsController extends Controller
{
    public function getChartDataSale(Request $request)
    {
        $filter = $request->input('filter', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Obtener IDs de administradores (WhatsApp)
        //$adminIds = User::where('is_admin', 1)->pluck('id');

        $data = [
            'labels' => [],
            'sales' => [] // Array de ventas por fecha
        ];

        // Variables para el total de ventas generales
        $salesTotal = 0;

        if ($filter === 'daily') {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $salesData = $this->getSalesData($startDate, $endDate);

            $data['labels'][] = $startDate->format('d-m-Y');
            $data['sales'][] = $salesData['sales_total'];

            $salesTotal += $salesData['sales_total'];

        } elseif ($filter === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $data['labels'][] = $date->format('d-m-Y');

                $salesData = $this->getSalesData($date, $date);
                $data['sales'][] = $salesData['sales_total'];

                $salesTotal += $salesData['sales_total'];
            }

        } elseif ($filter === 'monthly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subMonths($i)->startOfMonth();
                $endMonth = $date->copy()->endOfMonth();

                $data['labels'][] = $date->format('m-Y');

                $salesData = $this->getSalesData($date, $endMonth);
                $data['sales'][] = $salesData['sales_total'];

                $salesTotal += $salesData['sales_total'];
            }

        } elseif ($filter === 'date_range' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);

            while ($startDate <= $endDate) {
                $data['labels'][] = $startDate->format('d-m-Y');

                $salesData = $this->getSalesData($startDate, $startDate);
                $data['sales'][] = $salesData['sales_total'];

                $salesTotal += $salesData['sales_total'];

                $startDate->addDay();
            }
        } else {
            return response()->json(['error' => 'Invalid filter'], 400);
        }

        // Calcular totales y porcentajes
        $totalSales = $salesTotal;

        // Agregar datos de totales SIN afectar el formato del gráfico
        $data['total'] = number_format($totalSales, 2, '.', '');
        $data['total_percentage'] = 100;

        return response()->json($data);
    }

    private function getSalesData($startDate, $endDate)
    {
        /*// Obtener todas las órdenes del rango de fechas
        $whatsappOrders = Order::whereIn('user_id', $adminIds)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('state_annulled', 0)
            ->get(); // Obtener los modelos para acceder al accesor

        $webOrders = Order::whereNotIn('user_id', $adminIds)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('state_annulled', 0)
            ->get();

        // Usar collect()->sum() con función anónima tradicional
        $whatsappSales = $whatsappOrders->sum(function ($order) {
            return $order->amount_pay;
        });

        $webSales = $webOrders->sum(function ($order) {
            return $order->amount_pay;
        });

        return [
            'sales_total' => round($whatsappSales + $webSales, 2), // Total para el gráfico por fecha
            'whatsapp_sales' => $whatsappSales,
            'web_sales' => $webSales
        ];*/
        $sales = Sale::with(['cashMovements' => function ($query) {
            $query->where('type', 'sale');
        }])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('state_annulled', 0)
            ->get();

        $totalSales = 0;

        foreach ($sales as $sale) {
            $movements = $sale->cashMovements;

            // Buscar si tiene movimiento POS
            $posMovement = $movements->first(function ($m) {
                return $m->subtype === 'pos';
            });

            if ($posMovement) {
                if ($posMovement->regularize) {
                    $amount = $posMovement->amount;
                } else {
                    continue; // No sumar esta orden si POS no está regularizada
                }
            } else {
                // No es POS, tomar el valor original de la orden
                $amount = $sale->importe_total;
            }

            $totalSales += $amount;

        }

        return [
            'sales_total' => round($totalSales, 2),
        ];
    }

    public function getChartDataCashFlow(Request $request)
    {
        $filter = $request->input('filter', 'daily');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = [
            'labels' => [],
            'incomes' => [], // Ingresos por fecha
            'expenses' => []  // Egresos por fecha
        ];

        // Variables para el total de ingresos y egresos
        $totalIncome = 0;
        $totalExpense = 0;

        if ($filter === 'daily') {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $cashData = $this->getCashData($startDate, $endDate);

            $data['labels'][] = $startDate->format('d-m-Y');
            $data['incomes'][] = $cashData['income_total'];
            $data['expenses'][] = $cashData['expense_total'];

            $totalIncome = $cashData['income_total'];
            $totalExpense = $cashData['expense_total'];

        } elseif ($filter === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $data['labels'][] = $date->format('d-m-Y');

                $cashData = $this->getCashData($date, $date);
                $data['incomes'][] = $cashData['income_total'];
                $data['expenses'][] = $cashData['expense_total'];

                $totalIncome += $cashData['income_total'];
                $totalExpense += $cashData['expense_total'];
            }

        } elseif ($filter === 'monthly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subMonths($i)->startOfMonth();
                $endMonth = $date->copy()->endOfMonth();

                $data['labels'][] = $date->format('m-Y');

                $cashData = $this->getCashData($date, $endMonth);
                $data['incomes'][] = $cashData['income_total'];
                $data['expenses'][] = $cashData['expense_total'];

                $totalIncome += $cashData['income_total'];
                $totalExpense += $cashData['expense_total'];
            }

        } elseif ($filter === 'date_range' && $startDate && $endDate) {
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);

            while ($startDate <= $endDate) {
                $data['labels'][] = $startDate->format('d-m-Y');

                $cashData = $this->getCashData($startDate, $startDate);
                $data['incomes'][] = $cashData['income_total'];
                $data['expenses'][] = $cashData['expense_total'];

                $totalIncome += $cashData['income_total'];
                $totalExpense += $cashData['expense_total'];

                $startDate->addDay();
            }
        } else {
            return response()->json(['error' => 'Invalid filter'], 400);
        }

        // Calcular utilidad
        $profit = $totalIncome - $totalExpense;

        // Agregar datos de totales
        $data['total_income'] = number_format($totalIncome, 2, '.', '');
        $data['total_expense'] = number_format($totalExpense, 2, '.', '');
        $data['profit'] = number_format($profit, 2, '.', '');

        return response()->json($data);
    }

    private function getCashData($startDate, $endDate)
    {
        $expenseTotal = 0;
        $incomeTotal = 0;
        /**
        INGRESOS:
        Sales
        Ingresos de caja
        **/
        // Sumar ingresos (type = income) + (type = sale con regularize = 1)
        $incomeTotal = CashMovement::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('arqueo', false)
            ->where(function ($query) {
                $query->where('type', 'income')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'sale')->where('regularize', 1);
                    });
            })
            ->sum('amount');

        /**
        EGRESOS:
            Egresos de caja
            Facturas de Finanzas con el flag egreso
            Ordenes de compra y servicios
            Paga al personal (Si hubiese),
         **/

        // Sumar egresos (type = expense)
        $expenseCaja = CashMovement::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('arqueo', false)
            ->where('type', 'expense')
            ->sum('amount');

        $facturasFinanzasEgresos = Entry::with('supplier')
            ->with('category_invoice')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('finance', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalFinanzasEgreso = 0;

        foreach ( $facturasFinanzasEgresos as $finanzasEgreso )
        {
            $totalFinanzasEgreso += $finanzasEgreso->total;
        }

        $entries = Entry::with('supplier')->with('category_invoice')
            ->where('entry_type', 'Por compra')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->get();
        $orderServices = OrderService::with('supplier')
            ->where('regularize', 'r')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $ingresosTotal = 0;
        foreach ( $entries as $entry )
        {
            $ingresosTotal += $entry->total;
        }

        $serviciosTotal = 0;
        foreach ( $orderServices as $orderService )
        {
            $serviciosTotal += $orderService->total;
        }

        // TODO: Pagos al personal


        $expenseTotal = $expenseCaja + $totalFinanzasEgreso + $ingresosTotal + $serviciosTotal;

        return [
            'income_total' => round($incomeTotal, 2),
            'expense_total' => round($expenseTotal, 2)
        ];
    }
}
