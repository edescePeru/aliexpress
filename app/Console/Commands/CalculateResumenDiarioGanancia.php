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
    public function handle()
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
}
