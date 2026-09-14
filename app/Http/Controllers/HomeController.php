<?php

namespace App\Http\Controllers;

use App\ContactName;
use App\Customer;
use App\DateDimension;
use App\Entry;
use App\Helpers\MetaCalendarHelper;
use App\Location;
use App\Material;
use App\Meta;
use App\Output;
use App\Sale;
use App\Supplier;
use App\Warehouse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\SettingService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function dashboard()
    {
        $customerCount = Customer::count();
        $contactNameCount = ContactName::count();
        $supplierCount = Supplier::count();
        $materialCount = Material::count();
        $entriesCount = Entry::where('finance', false)->count();
        $invoiceCount = Entry::where('finance', true)->count();
        $outputCount = Output::count();

        $locations = [];
        $locations2 = Location::with(['area', 'warehouse', 'shelf', 'level', 'container', 'position'])->get();

        $almacenes = [];
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            array_push($almacenes, ['id'=> $warehouse->id, 'warehouse' => $warehouse->name]);

        }

        foreach ( $locations2 as $location )
        {
            $l = 'AR:'.$location->area->name.'|AL:'.$location->warehouse->name.'|AN:'.$location->shelf->name.'|NIV:'.$location->level->name.'|CON:'.$location->container->name.'|POS:'.$location->position->name;
            array_push($locations, ['id'=> $location->id, 'location' => $l]);
        }

        Carbon::setLocale(config('app.locale'));

        $fechaActual = Carbon::now('America/Lima');

        $currentYear = $fechaActual->year;
        $currentMonth = $fechaActual->month;

        $years = DateDimension::distinct()->get(['year']);

        $months = DateDimension::distinct()
            ->where('year', $currentYear)
            ->orderBy('month')
            ->get(['month', 'month_name']);

        /**
         * =============================
         *  RANKING DE METAS (TOP 10)
         * =============================
         */
        $rankingMetasDashboard = [];
        $rankingPeriodText     = null;

        // leer tipo_meta
        $tipoMeta = app(SettingService::class)->get('sales.goals.frequency');
        $tiposValidos = ['semanal', 'quincenal', 'mensual'];

        if (in_array($tipoMeta, $tiposValidos)) {

            try {
                // Determinar año / mes / semana / quincena según HOY
                $hoy = $fechaActual->copy();

                if ($tipoMeta === 'mensual') {
                    $params = [
                        'year'  => $hoy->year,
                        'month' => $hoy->month,
                    ];
                } elseif ($tipoMeta === 'quincenal') {
                    $quincena = ($hoy->day <= 15) ? 1 : 2;
                    $params = [
                        'year'     => $hoy->year,
                        'month'    => $hoy->month,
                        'quincena' => $quincena,
                    ];
                } else { // semanal
                    $rowHoy = DateDimension::whereDate('date', $hoy->toDateString())->first();
                    if ($rowHoy) {
                        $params = [
                            'year' => $rowHoy->year,
                            'month' => $rowHoy->month,
                            'week'  => $rowHoy->week_of_year, // usamos week_of_year como en MetaCalendarHelper
                        ];
                    } else {
                        $params = null;
                    }
                }

                if ($params) {
                    // obtener rango real usando el helper
                    $range = MetaCalendarHelper::getRangeForPeriod($tipoMeta, $params);
                    $inicio = $range['start']->copy()->startOfDay();
                    $fin    = $range['end']->copy()->endOfDay();

                    $rankingPeriodText = $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y');

                    // metas del periodo
                    $metas = Meta::with('workers')
                        ->whereDate('fecha_inicio', $inicio->toDateString())
                        ->whereDate('fecha_fin', $fin->toDateString())
                        ->get();

                    $tempRanking = [];

                    foreach ($metas as $meta) {
                        foreach ($meta->workers as $worker) {
                            // suma de ventas del trabajador en el periodo
                            $ventasTotal = Sale::where('worker_id', $worker->id)
                                ->whereBetween('date_sale', [$inicio, $fin])
                                ->where('state_annulled', false)
                                ->sum('importe_total');

                            $porcentaje = 0;
                            if ($meta->monto > 0) {
                                $porcentaje = round(($ventasTotal / $meta->monto) * 100, 2);
                            }

                            $tempRanking[] = [
                                'worker_name'  => trim($worker->first_name . ' ' . $worker->last_name),
                                'meta_nombre'  => $meta->nombre,
                                'meta_monto'   => (float) $meta->monto,
                                'ventas_total' => (float) $ventasTotal,
                                'porcentaje'   => $porcentaje,
                            ];
                        }
                    }

                    // ordenar por porcentaje desc
                    usort($tempRanking, function ($a, $b) {
                        return $b['porcentaje'] <=> $a['porcentaje'];
                    });

                    // tomar solo top 10
                    $rankingMetasDashboard = array_slice($tempRanking, 0, 10);
                }

            } catch (\Throwable $e) {
                // Si falla algo, simplemente dejamos el ranking vacío
                $rankingMetasDashboard = [];
            }
        }

        return view('dashboard.dashboard',
            compact(
                'customerCount',
                'contactNameCount',
                'supplierCount',
                'materialCount',
                'entriesCount',
                'invoiceCount',
                'outputCount',
                'locations',
                'almacenes',
                'years',
                'months',
                'currentYear',
                'currentMonth',
                'rankingMetasDashboard',
                'rankingPeriodText',
                'tipoMeta'
            )
        );
    }
}
