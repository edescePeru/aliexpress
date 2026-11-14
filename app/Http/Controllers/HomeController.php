<?php

namespace App\Http\Controllers;

use App\Services\Sales\MetaProgress;

use App\Helpers\MetaCalendarHelper;

use App\Services\SettingService;

use App\Support\TenantContext;

use App\ContactName;
use App\Customer;
use App\DateDimension;
use App\Entry;
use App\Location;
use App\Material;
use App\Output;
use App\Sale;
use App\Supplier;
use App\Warehouse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();

        if ($user && $user->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

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
        $rankingPeriodText = null;
        if (auth()->user()->can('showBoxMetas_dashboard')
            && TenantContext::tenantIdOrNull() && TenantContext::companyIdOrNull()) {
            $type = app(SettingService::class)->get('sales.goals.frequency');
            if (in_array($type, ['semanal', 'quincenal', 'mensual'], true)) {
                $day = DateDimension::whereDate('date', $fechaActual->toDateString())->first();
                if ($type !== 'semanal' || $day) {
                    $period = MetaCalendarHelper::getRangeForPeriod($type, [
                        'year' => $fechaActual->year,
                        'month' => $fechaActual->month,
                        'week' => $day ? $day->week_of_year : null,
                        'quincena' => $fechaActual->day <= 15 ? 1 : 2,
                    ]);
                    $rankingPeriodText = $period['start']->format('d/m/Y').' al '.$period['end']->format('d/m/Y');
                    $rows = app(MetaProgress::class)->rows($type, $period['start'], $period['end']);
                    usort($rows, function ($a, $b) { return $b['porcentaje'] <=> $a['porcentaje']; });
                    $rankingMetasDashboard = array_slice($rows, 0, 10);
                }
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
                'rankingPeriodText'
            )
        );
    }
}
