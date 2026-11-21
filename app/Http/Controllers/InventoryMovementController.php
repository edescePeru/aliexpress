<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\InventoryMovement;
use App\Material;
use App\Services\SettingService;
use App\Services\Inventory\KardexMovementQuery;
use App\Services\Materials\MaterialInventoryScope;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use App\StockItem;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list_kardex')->only(['index', 'kardex', 'kardexO']);
    }

    public function index()
    {
        app(MaterialInventoryScope::class)->context();

        return view('inventory.kardex');
    }

    /** Método antiguo sin ruta vigente. No consultar fuentes globales por material. */
    public function kardexO(Request $request, $materialId)
    {
        abort(410, 'Este Kardex antiguo está retirado. Use el Kardex por producto o variante.');
    }

    public function kardex(Request $request, $stockItemId)
    {
        $scope = app(MaterialInventoryScope::class);
        $scope->context();
        $data = $request->validate(['warehouse_id' => ['nullable', 'integer', 'min:1']]);
        $stockItem = StockItem::with('material')->where('tenant_id', TenantContext::tenantId())
            ->findOrFail($stockItemId);
        $scope->stockItem($stockItem);

        $warehouses = $scope->warehouses(DB::table('warehouses'));
        $warehouseId = $data['warehouse_id'] ?? null;
        if ($warehouseId !== null) {
            $warehouse = (clone $warehouses)->where('warehouses.id', $warehouseId)->first();
            abort_unless($warehouse, 404);
            $warehouses->where('warehouses.id', $warehouseId);
        }

        $currency = strtoupper((string) app(SettingService::class)->get('finance.base_currency'));
        $movements = app(KardexMovementQuery::class)
            ->forStockItem($stockItem->id, $warehouses->select('warehouses.id'), $currency);

        $saldoCantidad = 0.0;
        $saldoImporte  = 0.0;
        $costoPromedio = 0.0;
        $rows = [];

        $simboloMoneda = $currency === 'USD' ? '$ ' : 'S/. ';

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
                    'receipt_id' => $m->receipt_id,
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
                $costoPromedio = $saldoCantidad > 0 ? $saldoImporte / $saldoCantidad : 0.0;

                $rows[] = [
                    'date' => optional($m->movement_date)->format('Y-m-d'),
                    'type' => 'OUT',
                    'source_type' => $m->source_type,
                    'source_id' => $m->source_id,
                    'receipt_id' => $m->receipt_id,
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
            'warehouse_name' => $warehouse->name ?? null,
            'rows' => $rows,
        ]);
    }
}
