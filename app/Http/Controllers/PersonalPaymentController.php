<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\DateDimension;
use App\PaySlip;
use App\Projection;
use App\ProjectionDetail;
use App\Services\SettingService;
use App\Services\TipoCambioService;
use App\SueldoMensual;
use App\User;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalPaymentController extends Controller
{
    protected $monthsOfYear = [
        ['month' => 1, 'nameMonth' => 'Enero', 'shortName' => 'ENE'],
        ['month' => 2, 'nameMonth' => 'Febrero', 'shortName' => 'FEB'],
        ['month' => 3, 'nameMonth' => 'Marzo', 'shortName' => 'MAR'],
        ['month' => 4, 'nameMonth' => 'Abril', 'shortName' => 'ABR'],
        ['month' => 5, 'nameMonth' => 'Mayo', 'shortName' => 'MAY'],
        ['month' => 6, 'nameMonth' => 'Junio', 'shortName' => 'JUN'],
        ['month' => 7, 'nameMonth' => 'Julio', 'shortName' => 'JUL'],
        ['month' => 8, 'nameMonth' => 'Agosto', 'shortName' => 'AGO'],
        ['month' => 9, 'nameMonth' => 'Setiembre', 'shortName' => 'SET'],
        ['month' => 10, 'nameMonth' => 'Octubre', 'shortName' => 'OCT'],
        ['month' => 11, 'nameMonth' => 'Noviembre', 'shortName' => 'NOV'],
        ['month' => 12, 'nameMonth' => 'Diciembre', 'shortName' => 'DIC'],
    ];

    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
    }

    public function index()
    {
        Carbon::setLocale(config('app.locale'));

        $fechaActual = Carbon::now('America/Lima');

        $currentYear = $fechaActual->year;
        $currentMonth = $fechaActual->month;

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $years = DateDimension::distinct()->get(['year']);

        $months = DateDimension::distinct()
            ->where('year', $currentYear)
            ->orderBy('month')
            ->get(['month', 'month_name']);

        return view('personalPayment.list', compact( 'permissions', 'years', 'months', 'currentYear', 'currentMonth'));

    }

    public function getPersonalPaymentByMonth()
    {
        $year = $_GET["year"];
        $month = $_GET["month"];

        $tiposCambios = $this->getTypeExchange($year, $month);

        //dd($tiposCambios);


        //TODO: Primero obtenemos las fechas de ese mes y año
        $dates = DateDimension::where('year', $year)
            ->where('month', $month)
            ->orderBy('date', 'ASC')
            ->get();

        // TODO: Ahora recorremos las fechas para obtener las semanas y su cantidad de dias
        $semanas = [];

        foreach ( $dates as $date )
        {
            // Convierte la fecha en un objeto Carbon
            $carbonDate = Carbon::parse($date->date);

            // Obtén el número de la semana y el día de la semana actual
            $numeroSemana = $carbonDate->weekOfYear;
            $diaSemana = $carbonDate->dayOfWeek;

            // Si la semana no existe en el array, agrégala
            if (!isset($semanas[$numeroSemana])) {
                $semanas[$numeroSemana] = ['semana' => $numeroSemana, 'dias' => 0];
            }

            // Incrementa la cantidad de días de la semana actual
            $semanas[$numeroSemana]['dias']++;
        }

        $semanas = array_values($semanas);

        foreach ($semanas as &$element) {
            $semana = $element['semana'];

            //$fechaPrimerDia = Carbon::now()->setISODate(date('Y'), $semana)->startOfWeek();
            $fechaPrimerDia = Carbon::now()->setISODate($year, $semana)->startOfWeek();

            // Verificar si el primer día de la semana pertenece al mes dado
            if ($fechaPrimerDia->month == $month) {
                $element['firstDayWeek'] = $fechaPrimerDia->format('Y-m-d');
            } else {
                // En caso de que pertenezca a otro mes, obtener el primer día del mes dado
                //$fechaPrimerDia = Carbon::createFromDate(date('Y'), $month, 1);
                $fechaPrimerDia = Carbon::createFromDate($year, $month, 1);
                $element['firstDayWeek'] = $fechaPrimerDia->format('Y-m-d');
            }
        }

        unset($element);
        //dump($semanas);

        $currency = app(SettingService::class)->get('finance.base_currency');

        foreach ($semanas as &$element) {
            $firstDayWeek = $element['firstDayWeek'];
            //dump($firstDayWeek);
            //dd($tiposCambios);
            // Obtener la tasa de cambio para el día correspondiente utilizando tu función getExchange()

            if ($currency === 'USD')
            {
                $rate = $this->getExchange($firstDayWeek, $tiposCambios);
                $element['cambioCompra'] = (isset($rate)) ? (float)$rate->precioCompra:1;
                $element['cambioVenta'] = (isset($rate)) ? (float)$rate->precioVenta:1;
            } elseif ($currency === 'PEN') {
                $element['cambioCompra'] = 1;
                $element['cambioVenta'] = 1;
            }
            //$rate = $this->getExchange($firstDayWeek, $tiposCambios); // Reemplaza getExchange() con el nombre de tu propia función
            /**/
        }

        unset($element);

        $workers = Worker::where('enable', 1)->where('id', '<>', 1)->get();
        $personalPayments = [];
        foreach ( $workers as $worker )
        {
            $amountWeeks = [];
            $total = 0;
            for ( $i=0; $i<count($semanas); $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana

                $boleta = PaySlip::where('year', $year)
                    ->where('semana', $semanas[$i]['semana'])
                    ->where('codigo', $worker->id)
                    ->first();
                if ( isset( $boleta ) )
                {
                    array_push($amountWeeks, [
                        "semana" => $semanas[$i]['semana'],
                        "monto" => round((($boleta->totalIngresos+$boleta->totalDescuentos)/7)*$semanas[$i]['dias'],2)
                    ]);
                    $total = $total + round((($boleta->totalIngresos+$boleta->totalDescuentos)/7)*$semanas[$i]['dias'],2);
                } else {
                    array_push($amountWeeks, [
                        "semana" => $semanas[$i]['semana'],
                        "monto" => round(((0)/7)*$semanas[$i]['dias'],2)
                    ]);
                    $total = $total + 0;
                }

            }

            array_push($personalPayments, [
                "codigo" => $worker->id,
                "trabajador" => $worker->first_name. " ". $worker->last_name,
                "weeks" => $amountWeeks,
                "total" => $total
            ]);
        }

        // Obtener la lista de semanas
        $weeks = [];
        foreach ($personalPayments as $element) {
            foreach ($element['weeks'] as $week) {
                $weeks[$week['semana']] = 0;
            }
        }

        // Calcular la suma de montos por semana
        foreach ($personalPayments as $element) {
            foreach ($element['weeks'] as $week) {
                $weeks[$week['semana']] += $week['monto'];
            }
        }

        // Agregar la fila adicional con la suma de montos
        $sumaTotal = array_sum($weeks);
        $nuevaFila = [
            "codigo" => null,
            "trabajador" => null,
            "weeks" => [],
            "total" => $sumaTotal,
        ];
        foreach ($weeks as $semana => $monto) {
            $nuevaFila['weeks'][] = [
                "semana" => $semana,
                "monto" => $monto,
            ];
        }

        // Agregar la nueva fila al array original
        $personalPayments[] = $nuevaFila;

        //dump($personalPayments);

        // Realizamos la conversión
        $ultimaFila = end($personalPayments);

        //dump($ultimaFila);

        //dump($semanas);

        foreach ($ultimaFila['weeks'] as &$semana) {
            // Obtener el número de semana
            $numeroSemana = $semana['semana'];

            // Buscar el tipo de cambio correspondiente a la semana actual
            $tipoCambio = null;
            foreach ($semanas as $cambio) {
                if ($cambio['semana'] == $numeroSemana) {
                    $tipoCambio = $cambio;
                    break;
                }
            }

            // Verificar si se encontró el tipo de cambio
            if ($tipoCambio) {
                // Obtener el monto original en soles
                $montoEnSoles = $semana['monto'];

                // Realizar la conversión a dólares utilizando el tipo de cambio de compra
                $montoEnDolares = $montoEnSoles / $tipoCambio['cambioCompra'];

                // Agregar el monto convertido después de 'monto'
                $semana['montoEnDolares'] = $montoEnDolares;
            }
        }

        // Resultado final
        //dump($ultimaFila);

        $personalPayments[count($personalPayments) - 1] = $ultimaFila;

        unset($semana);

        //dump($personalPayments);

        // Obtén la última fila del arreglo $personalPayments
        $ultimaFila = end($personalPayments);

        // Inicializa la variable para el total en dólares
        $totalDolares = 0;

        // Calcula el total en dólares sumando los montos en dólares de cada semana
        foreach ($ultimaFila['weeks'] as $semana) {
            $montoEnDolares = $semana['montoEnDolares'];
            $totalDolares += $montoEnDolares;
        }

        // Agrega el total en dólares después del campo "total"
        $ultimaFila['totalDolares'] = $totalDolares;

        // Reemplaza la última fila del arreglo original con la fila modificada
        $personalPayments[count($personalPayments) - 1] = $ultimaFila;

        //dump($personalPayments);

        $projection = Projection::with('details')
            ->where('year', $year)
            ->where('month', $month)->first();

        $personalProjections = [];
        $projection_month_dollars = 0;
        $projection_month_soles = 0;
        $projection_week_dollars = 0;
        $projection_week_soles = 0;
        if ( isset($projection) )
        {
            $projection_month_dollars = $projection->projection_month_dollars;
            $projection_month_soles = $projection->projection_month_soles;
            $projection_week_dollars = $projection->projection_week_dollars;
            $projection_week_soles = $projection->projection_week_soles;
            foreach ( $projection->details as $detail )
            {
                array_push($personalProjections, [
                    "codigo" => $detail->worker->id,
                    "trabajador" => $detail->worker->first_name. " ". $detail->worker->last_name,
                    "sueldo" => ($detail->salary == null) ? 0:$detail->salary
                ]);
            }
        }

        $lastRow = $personalPayments[count($personalPayments)-1];
        $foundMonth = collect($this->monthsOfYear)->firstWhere('month', $month);

        // Creacion/Actualizacion de sueldos mensuales para el grafico
        $sueldoMensual = SueldoMensual::where('year', $year)
            ->where('month', $month)->first();

        if ( isset($sueldoMensual) )
        {
            // Actualizamos
            $sueldoMensual->total = $lastRow['totalDolares'];
            $sueldoMensual->save();
        } else {
            // Creamos
            $sueldoMensual = SueldoMensual::create([
                'year' => $year,
                'month' => $month,
                'nameMonth' => $foundMonth['nameMonth'],
                'shortName' => $foundMonth['shortName'],
                'total' => $lastRow['totalDolares'],
            ]);
        }

        $sueldosMensuales = SueldoMensual::where('year', $year)
            ->orderBy('month')
            ->get();

        // Obtener la suma total del campo 'total'
        $sumaTotal = $sueldosMensuales->sum('total');

        // Obtener el promedio de la columna 'total'
        $promedioTotal = $sueldosMensuales->avg('total');

        return response()->json([
            'personalPayments' => $personalPayments,
            'projections' => $personalProjections,
            'projection_dollars' => $projection_month_dollars,
            'projection_soles' => $projection_month_soles,
            'projection_week_soles' => $projection_week_soles,
            'projection_week_dollars' => $projection_week_dollars,
            'sueldosMensuales' => $sueldosMensuales,
            'sueldosMensualTotal' => $sumaTotal,
            'sueldosMensualPromedio' => $promedioTotal,
            'currency' => $currency
        ]);

    }

    public function getExchange($fecha, $tiposCambios)
    {
        $date = Carbon::createFromFormat('Y-m-d', $fecha);
        $dateCurrent = Carbon::now('America/Lima');

        if ( $date->lessThan($dateCurrent) )
        {
            // Buscar el elemento en la data que tenga la fecha indicada
            $elementoEncontrado = null;
            $elementoEncontrado = null;
            foreach ($tiposCambios as $elemento) {
                if ($elemento->fecha->format('Y-m-d') === $fecha) {
                    $elementoEncontrado = $elemento;
                    break; // Rompemos el loop si encontramos el elemento
                }
            }

            // Verificar si se encontró el elemento y hacer lo que necesites con él
            if ($elementoEncontrado) {
                // Aquí tienes el elemento que corresponde a la fecha buscada
                // Puedes imprimirlo o acceder a sus valores individuales
                $tipoCambioSunat = $elementoEncontrado;
            } else {
                // El elemento no fue encontrado
                $tipoCambioSunat = [];
            }

            return $tipoCambioSunat;
        } else {
            //dump('No entre');
            return null;
        }
    }

    public function getTypeExchange($year, $month)
    {
        //$token = 'apis-token-8651.OrHQT9azFQteF-IhmcLXP0W2MkemnPNX';

        $tipoCambio = $this->tipoCambioService->obtenerPorMonthYear($month, $year);
        return $tipoCambio;

        //return $tipoCambioSunat;
    }

    public function getPersonalPaymentByYear($year)
    {
        $array = [];
        $personalPayments = [];
        for ( $month = 1; $month<=12; $month++ )
        {
            //TODO: Primero obtenemos las fechas de ese mes y año
            $dates = DateDimension::where('year', $year)
                ->where('month', $month)
                ->orderBy('date', 'ASC')
                ->get();

            // TODO: Ahora recorremos las fechas para obtener las semanas y su cantidad de dias
            $semanas = [];

            foreach ( $dates as $date )
            {
                // Convierte la fecha en un objeto Carbon
                $carbonDate = Carbon::parse($date->date);

                // Obtén el número de la semana y el día de la semana actual
                $numeroSemana = $carbonDate->weekOfYear;

                // Si la semana no existe en el array, agrégala
                if (!isset($semanas[$numeroSemana])) {
                    $semanas[$numeroSemana] = ['semana' => $numeroSemana, 'dias' => 0];
                }

                // Incrementa la cantidad de días de la semana actual
                $semanas[$numeroSemana]['dias']++;
            }

            $semanas = array_values($semanas);

            foreach ($semanas as &$element) {
                $semana = $element['semana'];

                $fechaPrimerDia = Carbon::now()->setISODate(date('Y'), $semana)->startOfWeek();

                // Verificar si el primer día de la semana pertenece al mes dado
                if ($fechaPrimerDia->month == $month) {
                    $element['firstDayWeek'] = $fechaPrimerDia->format('Y-m-d');
                } else {
                    // En caso de que pertenezca a otro mes, obtener el primer día del mes dado
                    $fechaPrimerDia = Carbon::createFromDate(date('Y'), $month, 1);
                    $element['firstDayWeek'] = $fechaPrimerDia->format('Y-m-d');
                }
            }

            unset($element);

            /*foreach ($semanas as &$element) {
                $firstDayWeek = $element['firstDayWeek'];

                // Obtener la tasa de cambio para el día correspondiente utilizando tu función getExchange()
                $rate = $this->getExchange($firstDayWeek); // Reemplaza getExchange() con el nombre de tu propia función
                //dd($rate);
                $element['cambioCompra'] = (isset($rate)) ? (float)$rate->compra:1;
                $element['cambioVenta'] = (isset($rate)) ? (float)$rate->venta:1;
            }

            unset($element);*/

            //dump($semanas);

            //dd()

            $total = 0;
            for ( $i=0; $i<count($semanas); $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boleta = PaySlip::where('year', $year)
                    ->where('semana', $semanas[$i]['semana'])
                    ->first();
                if ( isset( $boleta ) )
                {
                    $total = $total + round((($boleta->totalIngresos+$boleta->totalDescuentos)/7)*$semanas[$i]['dias'],2);
                } else {
                    $total = $total + 0;
                }
            }
            array_push($personalPayments, [
                "month" => $month,
                "total" => $total
            ]);
            /*




            // Obtener la lista de semanas
            $weeks = [];
            foreach ($personalPayments as $element) {
                foreach ($element['weeks'] as $week) {
                    $weeks[$week['semana']] = 0;
                }
            }

            // Calcular la suma de montos por semana
            foreach ($personalPayments as $element) {
                foreach ($element['weeks'] as $week) {
                    $weeks[$week['semana']] += $week['monto'];
                }
            }

            // Agregar la fila adicional con la suma de montos
            $sumaTotal = array_sum($weeks);
            $nuevaFila = [
                "codigo" => null,
                "trabajador" => null,
                "weeks" => [],
                "total" => $sumaTotal,
            ];

            //dump($personalPayments);

            // Realizamos la conversión
            $ultimaFila = end($personalPayments);

            //dump($ultimaFila);

            //dump($semanas);

            foreach ($ultimaFila['weeks'] as &$semana) {
                // Obtener el número de semana
                $numeroSemana = $semana['semana'];

                // Buscar el tipo de cambio correspondiente a la semana actual
                $tipoCambio = null;
                foreach ($semanas as $cambio) {
                    if ($cambio['semana'] == $numeroSemana) {
                        $tipoCambio = $cambio;
                        break;
                    }
                }

                // Verificar si se encontró el tipo de cambio
                if ($tipoCambio) {
                    // Obtener el monto original en soles
                    $montoEnSoles = $semana['monto'];

                    // Realizar la conversión a dólares utilizando el tipo de cambio de compra
                    $montoEnDolares = $montoEnSoles / $tipoCambio['cambioCompra'];

                    // Agregar el monto convertido después de 'monto'
                    $semana['montoEnDolares'] = $montoEnDolares;
                }
            }

            // Resultado final
            //dump($ultimaFila);

            $personalPayments[count($personalPayments) - 1] = $ultimaFila;

            unset($semana);

            //dump($personalPayments);

            // Obtén la última fila del arreglo $personalPayments
            $ultimaFila = end($personalPayments);

            // Inicializa la variable para el total en dólares
            $totalDolares = 0;

            // Calcula el total en dólares sumando los montos en dólares de cada semana
            foreach ($ultimaFila['weeks'] as $semana) {
                $montoEnDolares = $semana['montoEnDolares'];
                $totalDolares += $montoEnDolares;
            }

            // Agrega el total en dólares después del campo "total"
            $ultimaFila['totalDolares'] = $totalDolares;

            // Reemplaza la última fila del arreglo original con la fila modificada
            $personalPayments[count($personalPayments) - 1] = $ultimaFila;

            //dump($personalPayments);

            $lastRow = $personalPayments[count($personalPayments)-1];
            $foundMonth = collect($this->monthsOfYear)->firstWhere('month', $month);

            array_push($array, [
                "month" => $month,
                "nameMonth" => $foundMonth['nameMonth'],
                "shortName" => $foundMonth['shortName'],
                "total" => $lastRow['totalDolares'],
            ]);*/
        }
        dump($personalPayments);
        dd();
    }
}
