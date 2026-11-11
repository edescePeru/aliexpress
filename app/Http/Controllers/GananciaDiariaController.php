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
        $permissions = Auth::user()->getPermissionsViaRoles()->pluck('name')->toArray();
        $report = app(\App\Services\Sales\UserProfitReport::class);
        $authors = $report->authors();
        return view('ganancia.indexTrabajador', compact('permissions', 'authors', 'report'));
    }

    private function resolveUnitCostO($detail): float
    {
        // 1) Si el detalle ya guardó costo unitario, úsalo
        if (!is_null($detail->unit_cost) && (float)$detail->unit_cost > 0) {
            return (float)$detail->unit_cost;
        }

        // 2) Fallback: costo actual del material (para ventas antiguas)
        return (float) (optional($detail->material)->unit_price ?? 0);
    }

    private function resolveUnitCost($detail): float
    {
        // 1) Nuevo modelo: costo unitario guardado en sale_details
        if (!is_null($detail->unit_cost) && (float) $detail->unit_cost > 0) {
            return (float) $detail->unit_cost;
        }

        // 2) Si tiene total_cost y quantity, calcular unitario
        if (
            !is_null($detail->total_cost) &&
            (float) $detail->total_cost > 0 &&
            (float) $detail->quantity > 0
        ) {
            return (float) $detail->total_cost / (float) $detail->quantity;
        }

        // 3) Fallback stock item: costo promedio actual
        if ($detail->stockItem && $detail->stockItem->inventoryLevels) {
            $avg = (float) $detail->stockItem->inventoryLevels->avg('average_cost');

            if ($avg > 0) {
                return $avg;
            }
        }

        // 4) Fallback legacy
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

        $report = app(\App\Services\Sales\UserProfitReport::class);
        $query = $report->query($creator, $startDate, $endDate)->with('details')
            ->orderByDesc('date_sale')->orderByDesc('id');
        $totals = $report->totals((clone $query)->get());

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
            $row = $report->totals([$sale]);

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
                "author"        => $report->author($sale),
                "detail_url"    => route('ganancia.detail.usuario', $sale->id),
                "print_url"     => $printUrl,
                "date_resumen"  => \Carbon\Carbon::parse($sale->date_sale)->format('d/m/Y'),
                "quantity_sale" => $row['quantity_sale_sum'],
                "total_sale"    => $row['total_sale_sum'],
                "total_utility" => $row['total_utility_sum'],
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

        $query = app(\App\Services\Sales\DailyProfitReport::class)->summaries()->orderByDesc('date_resumen')->orderByDesc('id');

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

        $ganancia = app(\App\Services\Sales\DailyProfitReport::class)->summaries()->findOrFail($gananciaId);

        return view('ganancia.indexDetail', compact( 'permissions', 'ganancia'));

    }

    public function indexDetailTrabajador($sale)
    {
        $report = app(\App\Services\Sales\UserProfitReport::class);
        $sale = $report->query()->with('details')->findOrFail($sale);
        return view('ganancia.indexDetailTrabajador', compact('report', 'sale'));
    }

    public function getDataGananciaDetailsO(Request $request, $pageNumber = 1)
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

    public function getDataGananciaDetails(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $request->validate(['ganancia_id' => 'required|integer|min:1']);
        $summary = app(\App\Services\Sales\DailyProfitReport::class)->summaries()->findOrFail($request->input('ganancia_id'));

        $array = [];

        $query = $summary->details()->with(['material', 'stockItem'])
            ->orderBy('id', 'desc');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $gananciaDetails = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        foreach ($gananciaDetails as $detail) {
            $description = optional($detail->stockItem)->display_name
                ?? optional($detail->material)->full_name
                ?? ($detail->material_id || $detail->stock_item_id ? 'Producto' : 'Servicios y venta libre');

            $array[] = [
                "id" => $detail->id,
                "date_detail" => $detail->date_detail->format('d/m/Y'),
                "material_id" => $detail->material_id,
                "stock_item_id" => $detail->stock_item_id,
                "material_description" => $description,
                "quantity" => $detail->quantity,
                "price_sale" => $detail->price_sale,
                "utility" => $detail->utility
            ];

        }

        $array[] = [
            "id" => 0,
            "date_detail" => "",
            "material_id" => "",
            "stock_item_id" => "",
            "material_description" => "TOTAL DEL DÍA",
            "quantity" => $summary->quantity_sale,
            "price_sale" => $summary->total_sale,
            "utility" => $summary->total_utility
        ];

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
