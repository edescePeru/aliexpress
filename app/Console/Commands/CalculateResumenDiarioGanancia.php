<?php

namespace App\Console\Commands;

use App\GananciaDiaria;
use App\GananciaDiariaDetail;
use App\Sale;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateResumenDiarioGanancia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:ganancia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcule la cantidad de productos con tipos vendidos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handleO()
    {
        $date_resumen = Carbon::now('America/Lima')->toDateString();
        $total_quantity_sale = 0;
        $total_sale = 0;
        $total_utility = 0;

        // Obtener las ventas del día
        $sales = Sale::with('details.material')
            ->where('state_annulled', false)
            ->whereDate('date_sale', $date_resumen)->get();

        // Guardar GananciaDiaria
        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $total_quantity_sale += $detail->quantity;
                $total_sale += $detail->total - $detail->discount;
                $unit_price = $detail->material->unit_price;
                $total_utility += ($detail->total - $detail->discount) - ($unit_price * $detail->quantity);
            }
        }

        $ganancia_diaria = GananciaDiaria::create([
            'date_resumen' => $date_resumen,
            'quantity_sale' => $total_quantity_sale,
            'total_sale' => $total_sale,
            'total_utility' => $total_utility,
        ]);

        // Agrupar por material_id
        $detailsGroupedByMaterial = [];

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $materialId = $detail->material_id;

                if (!isset($detailsGroupedByMaterial[$materialId])) {
                    $detailsGroupedByMaterial[$materialId] = [
                        'material_id' => $materialId,
                        'quantity' => 0,
                        'price_sale' => 0,
                        'utility' => 0,
                    ];
                }

                $detailsGroupedByMaterial[$materialId]['quantity'] += $detail->quantity;
                $detailsGroupedByMaterial[$materialId]['price_sale'] += $detail->total - $detail->discount;
                $unit_price = $detail->material->unit_price;
                $detailsGroupedByMaterial[$materialId]['utility'] += ($detail->total - $detail->discount) - ($unit_price * $detail->quantity);
            }
        }

        // Guardar cada detalle en GananciaDiariaDetail
        foreach ($detailsGroupedByMaterial as $materialDetail) {
            GananciaDiariaDetail::create([
                'ganancia_diaria_id' => $ganancia_diaria->id,
                'date_detail' => $date_resumen,
                'material_id' => $materialDetail['material_id'],
                'quantity' => $materialDetail['quantity'],
                'price_sale' => $materialDetail['price_sale'],
                'utility' => $materialDetail['utility'],
            ]);
        }

        $this->info('Resumen diario y detalles guardados exitosamente.');

        return 0;
    }

    public function handle()
    {
        $date_resumen = Carbon::now('America/Lima')->toDateString();

        $total_quantity_sale = 0;
        $total_sale = 0;
        $total_utility = 0;

        $sales = Sale::with([
            'details.material',
            'details.stockItem',
        ])
            ->where('state_annulled', false)
            ->whereDate('date_sale', $date_resumen)
            ->get();

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $quantity = (float) ($detail->quantity ?? 0);
                $netSale = (float) ($detail->total ?? 0) - (float) ($detail->discount ?? 0);

                // Nuevo modelo: costo real guardado en sale_details
                $cost = (float) ($detail->total_cost ?? 0);

                // Fallback por si hay ventas antiguas sin total_cost
                if ($cost <= 0 && $detail->material) {
                    $cost = (float) ($detail->material->unit_price ?? 0) * $quantity;
                }

                $total_quantity_sale += $quantity;
                $total_sale += $netSale;
                $total_utility += $netSale - $cost;
            }
        }

        $ganancia_diaria = GananciaDiaria::create([
            'date_resumen' => $date_resumen,
            'quantity_sale' => $total_quantity_sale,
            'total_sale' => $total_sale,
            'total_utility' => $total_utility,
        ]);

        $detailsGroupedByStockItem = [];

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $stockItemId = $detail->stock_item_id;
                $materialId = $detail->material_id;

                // Si es antiguo y no tiene stock_item_id, agrupamos por material como fallback
                $groupKey = $stockItemId
                    ? 'stock_' . $stockItemId
                    : 'material_' . $materialId;

                if (!isset($detailsGroupedByStockItem[$groupKey])) {
                    $detailsGroupedByStockItem[$groupKey] = [
                        'material_id' => $materialId,
                        'stock_item_id' => $stockItemId,
                        'quantity' => 0,
                        'price_sale' => 0,
                        'utility' => 0,
                    ];
                }

                $quantity = (float) ($detail->quantity ?? 0);
                $netSale = (float) ($detail->total ?? 0) - (float) ($detail->discount ?? 0);

                $cost = (float) ($detail->total_cost ?? 0);

                if ($cost <= 0 && $detail->material) {
                    $cost = (float) ($detail->material->unit_price ?? 0) * $quantity;
                }

                $detailsGroupedByStockItem[$groupKey]['quantity'] += $quantity;
                $detailsGroupedByStockItem[$groupKey]['price_sale'] += $netSale;
                $detailsGroupedByStockItem[$groupKey]['utility'] += $netSale - $cost;
            }
        }

        foreach ($detailsGroupedByStockItem as $detailGroup) {
            GananciaDiariaDetail::create([
                'ganancia_diaria_id' => $ganancia_diaria->id,
                'date_detail' => $date_resumen,
                'material_id' => $detailGroup['material_id'],
                'stock_item_id' => $detailGroup['stock_item_id'],
                'quantity' => $detailGroup['quantity'],
                'price_sale' => $detailGroup['price_sale'],
                'utility' => $detailGroup['utility'],
            ]);
        }

        $this->info('Resumen diario y detalles guardados exitosamente.');

        return 0;
    }
}
