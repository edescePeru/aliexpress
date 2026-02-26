<?php

namespace App\Http\Controllers;

use App\GananciaDiaria;
use App\GananciaDiariaDetail;
use App\Sale;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\GananciasTrabajadorExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GananciaVentasDetalladoExport;

class GananciaDiariaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('ganancia.index', compact( 'permissions'));

    }

    public function indexTrabajador()
    {
        $user = Auth::user();

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::select(
            'id',
            DB::raw("CONCAT(first_name, ' ', last_name) as name")
        )
            ->whereHas('user', function ($q) {
                $q->where('id', '!=', 1);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('ganancia.indexTrabajador', compact( 'permissions', 'workers'));

    }

    private function resolveUnitCost($detail): float
    {
        // 1) Si el detalle ya guardó costo unitario, úsalo
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }

        // 2) Fallback: costo actual del material (para ventas antiguas)
        return (float) (optional($detail->material)->unit_price ?? 0);
    }

    public function exportGananciaVentasDetallado(Request $request)
    {
        $creator   = $request->input('creator');
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        $fileName = 'ganancia_ventas_detallado_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new GananciaVentasDetalladoExport($creator, $startDate, $endDate),
            $fileName
        );
    }

    public function getDataGananciasTrabajador(Request $request, $pageNumber = 1)
    {
        $perPage   = 10;
        $creator   = $request->input('creator');
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        // ==========================
        // BASE QUERY (con relaciones)
        // ==========================
        if ($startDate == "" || $endDate == "") {
            $query = Sale::with(['worker', 'details.material'])
                ->where('state_annulled', false)
                ->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal  = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Sale::with(['worker', 'details.material'])
                ->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal)
                ->where('state_annulled', false)
                ->orderBy('created_at', 'DESC');
        }

        if (!empty($creator)) {
            // si Sale tiene campo worker_id:
            $query->where('worker_id', $creator);
        }

        // ==========================
        // TOTALES GENERALES (de todo el filtro, sin paginación)
        // ==========================
        $total_quantity_sale_global = 0;
        $total_total_sale_global    = 0;
        $total_total_utility_global = 0;

        $allSales = (clone $query)->get();

        foreach ($allSales as $saleGlobal) {
            foreach ($saleGlobal->details as $detailGlobal) {

                // ✅ Ignorar servicios u otros detalles sin material
                if (empty($detailGlobal->material_id)) {
                    continue;
                }

                $qtyGlobal       = (float) $detailGlobal->quantity;
                $lineTotalGlobal = (float) $detailGlobal->total;
                $lineDiscGlobal  = (float) $detailGlobal->discount;

                $netLineGlobal = $lineTotalGlobal - $lineDiscGlobal;

                $unitCostGlobal = $this->resolveUnitCost($detailGlobal);
                $costLineGlobal = $unitCostGlobal * $qtyGlobal;

                $total_quantity_sale_global += $qtyGlobal;
                $total_total_sale_global    += $netLineGlobal;
                $total_total_utility_global += ($netLineGlobal - $costLineGlobal);
            }
        }

        // ==========================
        // PAGINACIÓN
        // ==========================
        $array = [];

        $totalFilteredRecords = $query->count();
        $totalPages           = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord   = min($totalFilteredRecords, $pageNumber * $perPage);

        $sales = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        // ==========================
        // DATA POR CADA SALE (PÁGINA ACTUAL)
        // ==========================
        foreach ($sales as $sale) {
            $quantity_sale = 0;
            $total_sale    = 0;
            $total_utility = 0;

            foreach ($sale->details as $detail) {

                // ✅ Ignorar servicios u otros detalles sin material
                if (empty($detail->material_id)) {
                    continue;
                }

                $qty          = (float) $detail->quantity;
                $lineTotal    = (float) $detail->total;
                $lineDiscount = (float) $detail->discount;

                $netLine = $lineTotal - $lineDiscount;

                $unitCost = $this->resolveUnitCost($detail);
                $costLine = $unitCost * $qty;

                $quantity_sale += $qty;
                $total_sale    += $netLine;
                $total_utility += ($netLine - $costLine);
            }

            $tipo_comprobante = null;
            if ($sale->type_document == '01')
            {
                $tipo_comprobante = 'factura';
            } elseif ( $sale->type_document == '03' ) {
                $tipo_comprobante = 'boleta';
            }

            $tipo_documento_cliente = null;
            if ($sale->tipo_documento_cliente == 1)
            {
                $tipo_documento_cliente = 'dni';
            } elseif ( $sale->tipo_documento_cliente == 6 ) {
                $tipo_documento_cliente = 'ruc';
            }

            $printUrl = route('puntoVenta.print', $sale->id); // ticket por defecto

            if (!empty($sale->pdf_path)) {
                // PDF local guardado
                $printUrl = asset('comprobantes/pdfs/' . $sale->pdf_path);
            }

            $array[] = [
                "id"            => $sale->id,
                "print_url"     => $printUrl,
                "date_resumen"  => $sale->created_at->format('d/m/Y'),
                "quantity_sale" => $quantity_sale,
                "total_sale"    => round($total_sale, 2),
                "total_utility" => round($total_utility, 2),
                "print_label" => !empty($sale->pdf_path) ? 'Ver PDF' : 'Ver Ticket',
            ];
        }

        $pagination = [
            'currentPage'          => (int) $pageNumber,
            'totalPages'           => (int) $totalPages,
            'startRecord'          => $startRecord,
            'endRecord'            => $endRecord,
            'totalRecords'         => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        // Totales generales para enviar al Blade
        $totals = [
            'quantity_sale_sum' => $total_quantity_sale_global,
            'total_sale_sum'    => round($total_total_sale_global, 2),
            'total_utility_sum' => round($total_total_utility_global, 2),
        ];

        return [
            'data'       => $array,
            'pagination' => $pagination,
            'totals'     => $totals,
        ];
    }

    public function exportGananciasTrabajador(Request $request)
    {
        $creator   = $request->input('creator');
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        $fileName = 'ganancias_trabajador_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new GananciasTrabajadorExport($creator, $startDate, $endDate),
            $fileName
        );
    }

    public function getDataGanancias(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $array = [];
        $pagination = [];

        $query = GananciaDiaria::orderBy('id', 'desc');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $ganancias = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        foreach ( $ganancias as $ganancia )
        {
            array_push($array, [
                "id" => $ganancia->id,
                "date_resumen" => $ganancia->date_resumen->format('d/m/Y'),
                "quantity_sale" => $ganancia->quantity_sale,
                "total_sale" => $ganancia->total_sale,
                "total_utility" => $ganancia->total_utility
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function indexDetail($gananciaId)
    {
        $user = Auth::user();

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $ganancia = GananciaDiaria::find($gananciaId);

        return view('ganancia.indexDetail', compact( 'permissions', 'ganancia'));

    }

    public function indexDetailTrabajador($sale)
    {
        $user = Auth::user();

        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $sale = Sale::find($sale);

        return view('ganancia.indexDetailTrabajador', compact( 'permissions', 'sale'));
    }

    public function getDataGananciaDetails(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $ganancia_id = $request->input('ganancia_id');

        $array = [];
        $pagination = [];

        $query = GananciaDiariaDetail::where('ganancia_diaria_id', $ganancia_id)->orderBy('id', 'desc');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $gananciaDetails = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $quantity = 0;
        $price_sale = 0;
        $utility = 0;

        foreach ( $gananciaDetails as $detail )
        {
            array_push($array, [
                "id" => $detail->id,
                "date_detail" => $detail->date_detail->format('d/m/Y'),
                "material_id" => $detail->material_id,
                "material_description" => $detail->material->full_name,
                "quantity" => $detail->quantity,
                "price_sale" => $detail->price_sale,
                "utility" => $detail->utility
            ]);

            $quantity += $detail->quantity;
            $price_sale += $detail->price_sale;
            $utility += $detail->utility;
        }

        array_push($array, [
            "id" => 0,
            "date_detail" => "",
            "material_id" => "",
            "material_description" => "TOTAL",
            "quantity" => $quantity,
            "price_sale" => $price_sale,
            "utility" => $utility
        ]);

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(GananciaDiaria $gananciaDiaria)
    {
        //
    }

    public function edit(GananciaDiaria $gananciaDiaria)
    {
        //
    }

    public function update(Request $request, GananciaDiaria $gananciaDiaria)
    {
        //
    }

    public function destroy(GananciaDiaria $gananciaDiaria)
    {
        //
    }
}
