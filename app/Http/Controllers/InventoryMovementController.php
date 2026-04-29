<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\InventoryMovement;
use App\Material;
use App\StockItem;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function test()
    {
        $movs = InventoryMovement::where('material_id', 123)
            ->orderBy('movement_date')
            ->get();

        dd($movs->take(5)->toArray());
    }

    public function index()
    {
        return view('inventory.kardex');
    }

    /**
     * Kardex por material.
     *
     * GET /kardex/{materialId}?from=2022-01-01&to=2022-12-31
     */
    public function kardexO(Request $request, $materialId)
    {
        // Opcional: validar que el material exista
        $material = Material::findOrFail($materialId);

        $from = $request->get('from'); // formato 'Y-m-d'
        $to   = $request->get('to');   // formato 'Y-m-d'

        // 1) Traer movimientos desde la vista
        $query = InventoryMovement::where('material_id', $materialId);

        if ($from) {
            $query->whereDate('movement_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('movement_date', '<=', $to);
        }

        $movements = $query
            ->orderBy('movement_date')
            ->orderBy('movement_id') // para orden estable
            ->get();

        // 2) Variables acumuladas
        $saldoCantidad = 0.0;
        $saldoImporte  = 0.0;
        $costoPromedio = 0.0;

        $rows = [];

        $flag = DataGeneral::where('name', 'type_current')->first();
        $currency = $flag->valueText;
        $simboloMoneda = "S/. ";

        if ( $currency == 'usd' )
        {
            $simboloMoneda = "$ ";
        }

        foreach ($movements as $m) {
            if ($m->movement_type === 'IN') {
                // ENTRADA
                $cantidadEntrada = (float) $m->quantity;
                $costoUnitEntrada = (float) $m->unit_cost; // ya viene en moneda base

                $importeEntrada = $cantidadEntrada * $costoUnitEntrada;

                $saldoCantidad += $cantidadEntrada;
                $saldoImporte  += $importeEntrada;

                $costoPromedio = $saldoCantidad > 0
                    ? $saldoImporte / $saldoCantidad
                    : 0.0;

                $rows[] = [
                    'date'           => optional($m->movement_date)->format('Y-m-d'),
                    'type'           => 'IN',
                    'source_type'    => $m->source_type,
                    'source_id'      => $m->source_id,
                    'qty_in'         => $cantidadEntrada,
                    'qty_out'        => 0,
                    'unit_cost_in'   => round($costoUnitEntrada, 4),
                    'unit_cost_out'  => null,
                    'total_in'       => round($importeEntrada, 2),
                    'total_out'      => 0,
                    'saldo_qty'      => round($saldoCantidad, 4),
                    'saldo_cost'     => round($costoPromedio, 4),
                    'saldo_total'    => round($saldoImporte, 2),
                    'simbolo_moneda' => $simboloMoneda
                ];
            } else {
                // SALIDA
                $cantidadSalida = (float) $m->quantity;

                // Valuamos la salida con el costo promedio vigente
                $costoUnitSalida = $costoPromedio;
                $importeSalida   = $cantidadSalida * $costoUnitSalida;

                $saldoCantidad -= $cantidadSalida;
                $saldoImporte  -= $importeSalida;

                // (Opcional) si no quieres permitir saldos negativos, aquí podrías clamp a 0

                $rows[] = [
                    'date'           => optional($m->movement_date)->format('Y-m-d'),
                    'type'           => 'OUT',
                    'source_type'    => $m->source_type,
                    'source_id'      => $m->source_id,
                    'qty_in'         => 0,
                    'qty_out'        => $cantidadSalida,
                    'unit_cost_in'   => null,
                    'unit_cost_out'  => round($costoUnitSalida, 4),
                    'total_in'       => 0,
                    'total_out'      => round($importeSalida, 2),
                    'saldo_qty'      => round($saldoCantidad, 4),
                    'saldo_cost'     => round($costoPromedio, 4), // el promedio no cambia en salidas
                    'saldo_total'    => round($saldoImporte, 2),
                    'simbolo_moneda' => $simboloMoneda
                ];
            }
        }

        // 3) Devolver JSON (para consumir con jQuery/DataTables)
        return response()->json([
            'material_id'   => $material->id,
            'material_name' => $material->full_description,
            'rows'          => $rows,
        ]);
    }

    public function kardex(Request $request, $stockItemId)
    {
        $stockItem = StockItem::with('material')->findOrFail($stockItemId);

        $warehouseId = $request->get('warehouse_id');

        $query = InventoryMovement::where('stock_item_id', $stockItemId);

        if (!empty($warehouseId)) {
            $query->where('warehouse_id', $warehouseId);
        }

        $movements = $query
            ->orderBy('movement_date')
            ->orderBy('movement_id')
            ->get();

        $saldoCantidad = 0.0;
        $saldoImporte  = 0.0;
        $costoPromedio = 0.0;
        $rows = [];

        $flag = DataGeneral::where('name', 'type_current')->first();
        $currency = $flag ? $flag->valueText : 'pen';
        $simboloMoneda = $currency == 'usd' ? '$ ' : 'S/. ';

        foreach ($movements as $m) {
            $cantidad = (float) $m->quantity;

            if ($m->movement_type === 'IN') {
                $costoUnitEntrada = (float) $m->unit_cost;
                $importeEntrada = $cantidad * $costoUnitEntrada;

                $saldoCantidad += $cantidad;
                $saldoImporte  += $importeEntrada;

                $costoPromedio = $saldoCantidad > 0
                    ? $saldoImporte / $saldoCantidad
                    : 0.0;

                $rows[] = [
                    'date' => optional($m->movement_date)->format('Y-m-d'),
                    'type' => 'IN',
                    'source_type' => $m->source_type,
                    'source_id' => $m->source_id,
                    'warehouse_id' => $m->warehouse_id,
                    'location_id' => $m->location_id,
                    'qty_in' => $cantidad,
                    'qty_out' => 0,
                    'unit_cost_in' => round($costoUnitEntrada, 4),
                    'unit_cost_out' => null,
                    'total_in' => round($importeEntrada, 2),
                    'total_out' => 0,
                    'saldo_qty' => round($saldoCantidad, 4),
                    'saldo_cost' => round($costoPromedio, 4),
                    'saldo_total' => round($saldoImporte, 2),
                    'simbolo_moneda' => $simboloMoneda,
                ];
            } else {
                $costoUnitSalida = (float) ($m->unit_cost ?? $costoPromedio);
                $importeSalida = $cantidad * $costoUnitSalida;

                $saldoCantidad -= $cantidad;
                $saldoImporte  -= $importeSalida;

                $rows[] = [
                    'date' => optional($m->movement_date)->format('Y-m-d'),
                    'type' => 'OUT',
                    'source_type' => $m->source_type,
                    'source_id' => $m->source_id,
                    'warehouse_id' => $m->warehouse_id,
                    'location_id' => $m->location_id,
                    'qty_in' => 0,
                    'qty_out' => $cantidad,
                    'unit_cost_in' => null,
                    'unit_cost_out' => round($costoUnitSalida, 4),
                    'total_in' => 0,
                    'total_out' => round($importeSalida, 2),
                    'saldo_qty' => round($saldoCantidad, 4),
                    'saldo_cost' => round($costoPromedio, 4),
                    'saldo_total' => round($saldoImporte, 2),
                    'simbolo_moneda' => $simboloMoneda,
                ];
            }
        }

        return response()->json([
            'stock_item_id' => $stockItem->id,
            'material_id' => $stockItem->material_id,
            'material_name' => optional($stockItem->material)->full_name,
            'stock_item_name' => $stockItem->display_name,
            'rows' => $rows,
        ]);
    }
}
