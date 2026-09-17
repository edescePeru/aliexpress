<?php

namespace App\Http\Controllers;

use App\Alimony;
use App\AssistanceDetail;
use App\Boleta;
use App\DateDimension;
use App\Discount;
use App\Due;
use App\FifthCategory;
use App\Gratification;
use App\Holiday;
use App\License;
use App\Loan;
use App\MedicalRest;
use App\PaySlip;
use App\PensionSystem;
use App\PercentageWorker;
use App\PermitHour;
use App\Refund;
use App\SpecialBonus;
use App\Suspension;
use App\Vacation;
use App\Worker;
use App\WorkingDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;
use App\Services\SettingService;

class BoletaController extends Controller
{
    public function indexPaySlip()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('boleta.index', compact('permissions'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Boleta $boleta)
    {
        //
    }

    public function edit(Boleta $boleta)
    {
        //
    }

    public function update(Request $request, Boleta $boleta)
    {
        //
    }

    public function destroy(Boleta $boleta)
    {
        //
    }

    public function createBoletaByWorker()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::where('enable', true)
            ->get();

        $years = DateDimension::distinct()->get(['year']);

        $types = collect([
            [
                'id' => 1,
                'name' => 'Semanal'
            ]/*,
            [
                'id' => 2,
                'name' => 'Mensual'
            ]*/
        ]);

        /*foreach ( $types as $type )
        {
            dump($type['id']);
        }*/
        /*dump($workers);
        dump($years);
        dump($types);*/
        return view('boleta.createByWorker', compact( 'permissions', 'workers', 'years', 'types'));

    }

    public function saveBoletaWorkerWeekly()
    {
        $type = $_GET['type'];
        $year = $_GET['year'];
        $month = $_GET['month'];
        $week = $_GET['week'];
        $worker_id = $_GET['worker'];
        $info = $_GET['info'];

        /*dump($info['pensionDeAlimentos']);
        dd();*/
        $tipo = ($type == 1) ? 'w':'m' ;

        DB::beginTransaction();
        try {
            $pension = Alimony::where('week', $week)
                ->where('month', $month)
                ->where('year', $year)
                ->where('type', $tipo)
                ->where('worker_id', $worker_id)
                ->first();

            if ( isset($pension) )
            {
                $pension->delete();
                // Guardamos la pension de alimentos
                $alimony = Alimony::create([
                    'week' => $week,
                    'month' => $month,
                    'year' => $year,
                    'date' => Carbon::now('America/Lima'),
                    'amount' => (float) $info['pensionDeAlimentos'],
                    'worker_id' => $worker_id,
                    'type' => $type
                ]);
            } else {
                // Guardamos la pension de alimentos
                $alimony = Alimony::create([
                    'week' => $week,
                    'month' => $month,
                    'year' => $year,
                    'date' => Carbon::now('America/Lima'),
                    'amount' => (float) $info['pensionDeAlimentos'],
                    'worker_id' => $worker_id,
                    'type' => $type
                ]);
            }

            $worker = Worker::find($worker_id);

            $paySlipLast = PaySlip::where('codigo', $info['codigo'])
                ->where('semana', $info['semana'])
                ->where('fecha', $info['fecha'])->first();

            if ( isset($paySlipLast) )
            {
                $paySlipLast->delete();
                // TODO: Guardar la boleta y sus detalles
                $paySlip = PaySlip::create([
                    'empresa' => $info['empresa'],
                    'ruc' => $info['ruc'],
                    'codigo' => $info['codigo'],
                    'nombre' => $info['nombre'],
                    'cargo' => $info['cargo'],
                    'semana' => $info['semana'],
                    'fecha' => $info['fecha'],
                    'pagoxdia' => $info['pagoXDia'],
                    'pagoXHora' => $info['pagoXHora'],
                    'diasTrabajados' => $info['diasTrabajados'],
                    'asignacionFamiliarDiaria' => $info['asignacionFamiliarDiaria'],
                    'asignacionFamiliarSemanal' => $info['asignacionFamiliarSemanal'],
                    'horasOrdinarias' => $info['horasOrdinarias'],
                    'montoHorasOrdinarias' => $info['montoHorasOrdinarias'],
                    'horasAl25' => $info['horasAl25'],
                    'montoHorasAl25' => $info['montoHorasAl25'],
                    'horasAl35' => $info['horasAl35'],
                    'montoHorasAl35' => $info['montoHorasAl35'],
                    'horasAl100' => $info['horasAl100'],
                    'montoHorasAl100' => $info['montoHorasAl100'],
                    'dominical' => $info['dominical'],
                    'montoDominical' => $info['montoDominical'],
                    'montoBonus' => $info['montoBonus'],
                    'vacaciones' => $info['vacaciones'],
                    'montoVacaciones' => $info['montoVacaciones'],
                    'reintegro' => $info['reintegro'],
                    'gratificaciones' => $info['gratificaciones'],
                    'totalIngresos' => $info['totalIngresos'],
                    'sistemaPension' => $info['sistemaPension'],
                    'montoSistemaPension' => $info['montoSistemaPension'],
                    'rentaQuintaCat' => $info['rentaQuintaCat'],
                    'pensionDeAlimentos' => $info['pensionDeAlimentos'],
                    'prestamo' => $info['prestamo'],
                    'otros' => $info['otros'],
                    'totalDescuentos' => $info['totalDescuentos'],
                    'essalud' => $info['essalud'],
                    'totalNetoPagar' => $info['totalNetoPagar'],
                    'year' => $year
                ]);

            } else {
                // TODO: Guardar la boleta y sus detalles
                $paySlip = PaySlip::create([
                    'empresa' => $info['empresa'],
                    'ruc' => $info['ruc'],
                    'codigo' => $info['codigo'],
                    'nombre' => $info['nombre'],
                    'cargo' => $info['cargo'],
                    'semana' => $info['semana'],
                    'fecha' => $info['fecha'],
                    'pagoxdia' => $info['pagoXDia'],
                    'pagoXHora' => $info['pagoXHora'],
                    'diasTrabajados' => $info['diasTrabajados'],
                    'asignacionFamiliarDiaria' => $info['asignacionFamiliarDiaria'],
                    'asignacionFamiliarSemanal' => $info['asignacionFamiliarSemanal'],
                    'horasOrdinarias' => $info['horasOrdinarias'],
                    'montoHorasOrdinarias' => $info['montoHorasOrdinarias'],
                    'horasAl25' => $info['horasAl25'],
                    'montoHorasAl25' => $info['montoHorasAl25'],
                    'horasAl35' => $info['horasAl35'],
                    'montoHorasAl35' => $info['montoHorasAl35'],
                    'horasAl100' => $info['horasAl100'],
                    'montoHorasAl100' => $info['montoHorasAl100'],
                    'dominical' => $info['dominical'],
                    'montoDominical' => $info['montoDominical'],
                    'montoBonus' => $info['montoBonus'],
                    'vacaciones' => $info['vacaciones'],
                    'montoVacaciones' => $info['montoVacaciones'],
                    'reintegro' => $info['reintegro'],
                    'gratificaciones' => $info['gratificaciones'],
                    'totalIngresos' => $info['totalIngresos'],
                    'sistemaPension' => $info['sistemaPension'],
                    'montoSistemaPension' => $info['montoSistemaPension'],
                    'rentaQuintaCat' => $info['rentaQuintaCat'],
                    'pensionDeAlimentos' => $info['pensionDeAlimentos'],
                    'prestamo' => $info['prestamo'],
                    'otros' => $info['otros'],
                    'totalDescuentos' => $info['totalDescuentos'],
                    'essalud' => $info['essalud'],
                    'totalNetoPagar' => $info['totalNetoPagar'],
                    'year' => $year
                ]);

            }


            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Boleta generada con éxito. Vaya al listado de boletas para descargar'], 200);


    }

    public function indexBoletasSemanales( $worker_id )
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $worker = Worker::find($worker_id);

        return view('boleta.indexBoletaWorker', compact('permissions', 'worker'));
    }

    public function getBoletaworker( $worker_id )
    {
        $boletas = PaySlip::where('codigo', $worker_id)
            ->orderBy('created_at', 'desc')->get();

        return datatables($boletas)->toJson();
    }

    public function imprimirBoletaSemanal( $boleta_id )
    {
        $boleta = PaySlip::find($boleta_id);
        $worker = Worker::find($boleta->codigo);
        $fecha = $boleta->fecha;
        $fechaCorrecta = str_replace("/", "-", $fecha);

        $view = view('exports.boletaSemanal', compact('boleta'));

        $pdf = PDF::loadHTML($view);

        $name = 'Boleta_'.$fechaCorrecta.' '.$worker->last_name.' '.$worker->first_name.'.pdf';

        return $pdf->stream($name);
    }

    public function verBoletaSemanal( $boleta_id )
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $boleta = PaySlip::find($boleta_id);
        $worker = Worker::find($boleta->codigo);

        return view('boleta.showBoleta', compact('permissions','worker', 'boleta'));

    }

    public function saveBoletaWorkerMonthly()
    {
        $type = $_GET['type'];
        $year = $_GET['year'];
        $month = $_GET['month'];
        $week = $_GET['week'];
        $worker_id = $_GET['worker'];
        $info = $_GET['info'];

        /*dump($info['empresa']);
        dd();*/

        $tipo = ($type == 1) ? 'w':'m' ;

        DB::beginTransaction();
        try {
            $pension = Alimony::where('week', $week)
                ->where('month', $month)
                ->where('year', $year)
                ->where('type', $type)
                ->where('worker_id', $worker_id)
                ->first();
            if ( isset($pension) )
            {
                $pension->delete();
                // Guardamos la pension de alimentos
                $alimony = Alimony::create([
                    'week' => $week,
                    'month' => $month,
                    'year' => $year,
                    'date' => Carbon::now('America/Lima'),
                    'amount' => 0,
                    'worker_id' => $worker_id,
                    'type' => $type
                ]);
            } else {
                // Guardamos la pension de alimentos
                $alimony = Alimony::create([
                    'week' => $week,
                    'month' => $month,
                    'year' => $year,
                    'date' => Carbon::now('America/Lima'),
                    'amount' => 0,
                    'worker_id' => $worker_id,
                    'type' => $type
                ]);
            }



            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Boleta generada con éxito. Vaya al listado de boletas para descargar'], 200);

    }

    public function generateBoletaWorker()
    {
        $settings = app(SettingService::class);

        $type = $_GET['type'];
        $year = $_GET['year'];
        $month = $_GET['month'];
        $week = $_GET['week'];
        $worker_id = $_GET['worker'];

        $worker = Worker::find($worker_id);

        $daysOfWeek = (int) $settings->get(
            'payroll.days_per_week'
        );

        if ( $type == 1 )
        {
            // Es semanal
            /*$dateFirst = DateDimension::where('year', $year)
                ->where('month', $month)
                ->where('week', $week)
                ->orderBy('date', 'asc')
                ->first();*/
            /*$dateFirst = DateDimension::where('year', $year)
                ->where('week', $week)
                ->orderBy('date', 'asc')
                ->first();*/
            /*$dateFirst = DateDimension::where('year', $year)
                ->where('week', $week)
                ->where('day_of_week', 1) // 1 representa el lunes
                ->first();*/
            $dateFirst = DateDimension::where('year', $year)
                ->where('month', $month)
                ->where('week', $week)
                ->where('day_of_week', 1)
                ->orderBy('date', 'asc')
                ->first();

            if ( !isset($dateFirst) )
            {
                $dateFirst = DateDimension::where('year', $year)
                    ->where('month', $month-1)
                    ->where('week', $week)
                    ->where('day_of_week', 1)
                    ->orderBy('date', 'asc')
                    ->first();
            }

            //dump($dateFirst);
            /*$dateLast = DateDimension::where('year', $year)
                ->where('month', $month)
                ->where('week', $week)
                ->orderBy('date', 'desc')
                ->first();*/
           /* $dateLast = DateDimension::where('year', $year)
                ->where('week', $week)
                ->orderBy('date', 'desc')
                ->first();*/
            /*$dateLast = DateDimension::where('year', $year)
            ->where('week', $week)
            ->where('day_of_week', 7) // 7 representa el domingo
            ->first();*/
            $dateLast = DateDimension::where('date', '>=', $dateFirst->date)
                ->orderBy('date', 'asc')
                ->limit(1)
                ->offset(6) // Avanzamos 6 días para obtener el final de la semana
                ->first();

            //dump($dateLast);
            //dd();

            $start = (($dateFirst->day<10) ? '0'.$dateFirst->day:$dateFirst->day).'/'.(($dateFirst->month<10) ? '0'.$dateFirst->month:$dateFirst->month).'/'.$dateFirst->year;
            $end = (($dateLast->day<10) ? '0'.$dateLast->day:$dateLast->day).'/'.(($dateLast->month<10) ? '0'.$dateLast->month:$dateLast->month).'/'.$dateLast->year;

            $periodo = $start .' al '.$end;

            $semana = $week;
        }
        else {
            // Es mensual
            $dateFirst = DateDimension::where('year', $year)
                ->where('month', $month)
                ->first();
            $dateLast = DateDimension::where('year', $year)
                ->where('month', $month)
                ->orderBy('date', 'desc')
                ->first();

            $start = (($dateFirst->day<10) ? '0'.$dateFirst->day:$dateFirst->day).'/'.(($dateFirst->month<10) ? '0'.$dateFirst->month:$dateFirst->month).'/'.$dateFirst->year;
            $end = (($dateLast->day<10) ? '0'.$dateLast->day:$dateLast->day).'/'.(($dateLast->month<10) ? '0'.$dateLast->month:$dateLast->month).'/'.$dateLast->year;

            $periodo = (($month < 10) ? '0'.$month : $month).'/'.$year;
            $semana = '';
        }

        $arrayByWeek = $this->getTotalHoursByWorker($worker_id, $start, $end);

        $h_ord = 0;
        $h_25 = 0;
        $h_35 = 0;
        $h_100 = 0;
        $h_esp = 0;

        for ($i=0; $i<count($arrayByWeek); $i++)
        {
            $h_ord += $arrayByWeek[$i]['h_ord'];
            $h_25 += $arrayByWeek[$i]['h_25'];
            $h_35 += $arrayByWeek[$i]['h_35'];
            $h_100 += $arrayByWeek[$i]['h_100'];
            $h_esp += $arrayByWeek[$i]['h_esp'];
        }

        // Datos para la boleta Semanal
        if ( $type == 1 )
        {
            // Empleador y empleado
            $empresa = 'SERMEIND FABRICACIONES INDUSTRIALES S.A.C.';
            $ruc = '20540001384';
            $codigo = $worker->id;
            $nombre = $worker->first_name . ' ' . $worker->last_name;
            $cargo = ( $worker->work_function_id == null ) ? 'Sin cargo': $worker->work_function->description;

            // Ingresos
            $pagoXDia = ($worker->daily_salary == null) ? 0 : $worker->daily_salary;
            $horasXDia = (float) $settings->get('payroll.hours_per_day');
            $diasMes = (int) $settings->get('payroll.days_per_month');
            $horasSemanales = (float) $settings->get('payroll.hours_per_week');
            $pagoXHora = round($worker->daily_salary/$horasXDia,2);
            $diasTrabajados = round(($h_ord + $h_esp)/$horasXDia, 2);
            //dd($diasTrabajados);
            // TODO: Usar el porcentageWorker
            $assign_family = PercentageWorker::where('name', 'assign_family')->first();
            $rmv = PercentageWorker::where('name', 'rmv')->first();
            $asignacionFamiliarDiaria = ($worker->num_children == 0 || $worker->num_children == null) ? 0: round(($rmv->value*($assign_family->value/100))/$diasMes, 2);
            $asignacionFamiliarSemanal = ($worker->num_children == 0 || $worker->num_children == null) ? 0: round((($rmv->value*($assign_family->value/100))/$diasMes)*$daysOfWeek, 2);
            $horasOrdinarias = round(($h_ord + $h_esp), 2);
            $montoHorasOrdinarias = round(($h_ord + $h_esp)*($worker->daily_salary/$horasXDia), 2);
            $horasAl25 = round($h_25, 2);
            $montoHorasAl25 = round($h_25*(($worker->daily_salary/$horasXDia)*1.25), 2);
            $horasAl35 = round($h_35, 2);
            $montoHorasAl35 = round($h_35*(($worker->daily_salary/$horasXDia)*1.35), 2);
            $horasAl100 = round($h_100, 2);
            $montoHorasAl100 = round($h_100*(($worker->daily_salary/$horasXDia)*2), 2);
            $dominical = round(($h_ord + $h_esp)/$horasSemanales, 2);
            $montoDominical = round((($h_ord + $h_esp)/$horasSemanales)*($pagoXDia), 2);

            $amountBonus = $this->getBonusByWorker($worker_id, $start, $end);

            $hoursVacation = $this->getVacationByWorker($worker_id, $start, $end);
            $vacaciones = $hoursVacation;
            $montoVacaciones = round($hoursVacation*$pagoXHora, 2);
            //$montoVacaciones = 0;

            $amountRefund = $this->getRefundByWorker($worker_id, $start, $end);
            $reintegro = round($amountRefund, 2);

            $amountGratification = $this->getGratificationByWorker($worker_id, $start, $end);
            $gratificaciones = round($amountGratification, 2);

            $totalIngresos = round($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $amountBonus + $montoVacaciones + $reintegro + $gratificaciones, 2);

            // Descuento
            $systemPension = ($worker->pension_system_id == null) ? 'No tiene': PensionSystem::find($worker->pension_system_id);
            $sistemaPension = ($worker->pension_system_id == null) ? 'No tiene': $systemPension->description;
            //$porcentageSistemaPension = 100;

            if ( $worker->pension_system_id == null )
            {
                $porcentageSistemaPension = 0;
            } else {
                if ( $worker->percentage_pension_system == null || $worker->percentage_pension_system == 0 )
                {
                    $porcentageSistemaPension = $systemPension->percentage;
                } else {
                    $porcentageSistemaPension = $worker->percentage_pension_system;
                }
            }
            //$montoSistemaPension = ($worker->pension_system_id == null) ? 0 : round(($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $amountBonus + $montoVacaciones + $reintegro)*($systemPension->percentage/100), 2);
            $montoSistemaPension = ($worker->pension_system_id == null) ? 0 : round(($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $amountBonus + $montoVacaciones + $reintegro)*($porcentageSistemaPension/100), 2);

            $amountRentaQuintaCat = $this->getRentaQuintaByWorker($worker_id, $start, $end);
            $rentaQuintaCat = round($amountRentaQuintaCat, 2);

            $pensionDeAlimentos = ($worker->pension == 0) ? 0 : round( ($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $amountBonus + $montoVacaciones + $reintegro + $gratificaciones - $montoSistemaPension - $rentaQuintaCat)*($worker->pension/100) , 2);

            $amountLoan = $this->getLoanByWorker($worker_id, $start, $end);
            $prestamo = round($amountLoan, 2);

            $amountOtros = $this->getDiscountByWorker($worker_id, $start, $end);
            $otros = round($amountOtros, 2);

            $totalDescuentos = round($montoSistemaPension + $rentaQuintaCat + $pensionDeAlimentos + $prestamo + $otros, 2);

            // Aporte
            $percentageEssalud = PercentageWorker::where('name', 'essalud')->first();
            $essalud = round(($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $amountBonus + $montoVacaciones + $reintegro + $gratificaciones)*($percentageEssalud->value/100), 2);

            $totalNetoPagar = round($totalIngresos - $totalDescuentos, 2) ;

            // TODO: Crear el porcentageWorker HoursDiary = 8

            return response()->json([
                'empresa' => $empresa,
                'ruc' => $ruc,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'cargo' => $cargo,
                'semana' => $semana,
                'fecha' => $periodo,
                'pagoXDia' => $pagoXDia,
                'pagoXHora' => $pagoXHora,
                'diasTrabajados' => $diasTrabajados,
                'asignacionFamiliarDiaria' => $asignacionFamiliarDiaria,
                'asignacionFamiliarSemanal' => $asignacionFamiliarSemanal,
                'horasOrdinarias' => $horasOrdinarias,
                'montoHorasOrdinarias' => $montoHorasOrdinarias,
                'horasAl25' => $horasAl25,
                'montoHorasAl25' => $montoHorasAl25,
                'horasAl35' => $horasAl35,
                'montoHorasAl35' => $montoHorasAl35,
                'horasAl100' => $horasAl100,
                'montoHorasAl100' => $montoHorasAl100,
                'dominical' => $dominical,
                'montoDominical' => $montoDominical,
                'montoBonus' => $amountBonus,
                'vacaciones' => $vacaciones,
                'montoVacaciones' => $montoVacaciones,
                'reintegro' => $reintegro,
                'gratificaciones' => $gratificaciones,
                'totalIngresos' => $totalIngresos,
                'sistemaPension' => $sistemaPension,
                'montoSistemaPension' => $montoSistemaPension,
                'rentaQuintaCat' => $rentaQuintaCat,
                'pensionDeAlimentos' => $pensionDeAlimentos,
                'prestamo' => $prestamo,
                'otros' => $otros,
                'totalDescuentos' => $totalDescuentos,
                'essalud' => $essalud,
                'totalNetoPagar' => $totalNetoPagar
            ], 200);

        } else {

            $ruc = '20540001384';
            $empleador = 'SERMEIND FABRICACIONES INDUSTRIALES S.A.C.';
            $tipoDocumento = 'DNI';
            $dni = $worker->dni;
            $mombreApellidos = $worker->first_name . ' ' . $worker->last_name;
            $cargo = ( $worker->work_function_id == null ) ? '': $worker->work_function->description;
            $situacion = 'ACTIVO O SUBSIDIADO';
            $fechaIngreso = ($worker->admission_date == null) ? '': $worker->admission_date->format('d/m/Y');
            $tipoTrabajador = 'Empleado';
            $regimenPensionario = ($worker->pension_system == null) ? '':$worker->pension_system->description;
            $CUSPP = '';
            $horasXDia = (float) $settings->get('payroll.hours_per_day');
            $numberDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $diasLaborados = round(($h_ord + $h_esp)/$horasXDia, 2); // Dias Trabajados
            $diasNoLaborados = $numberDays - $diasLaborados;
            $diasSubsidiados = 0; // Preguntar
            $condicion = 'Domiciliado';
            $totalHorasOrdinarias = $h_ord+$h_esp;
            $totalHorasSobretiempo = $h_25+$h_35;
            $suspensiones = $this->getSuspensionsWorker($worker_id, $start, $end);
            $pagoXHora = round($worker->daily_salary/$horasXDia,2);
            $trabajoSobretiempo25 = round($h_25*($pagoXHora*1.25), 2);
            $trabajoSobretiempo35 = round($h_35*($pagoXHora*1.35), 2);

            $trabajoEnFeriadoODiaDescanso = round($h_100*($pagoXHora*2), 2);

            $remuneracionOJornalBasico = round(($h_ord+$h_esp)*$pagoXHora, 2);
            $bonificacionExtraordinariaTemporal = 125.89; // Preguntar

            $amountGratification = $this->getGratificationByWorker($worker_id, $start, $end);
            $gratificacion = round($amountGratification, 2);

            $comisionAfpPorcentual = 0;

            $amountQuinta = $this->getRentaQuintaByWorker($worker_id, $start, $end);
            $rentaQuintaCategoria = round($amountQuinta, 2);

            $primaDeSeguroAFP = 50.02; // Preguntar

            $pagoXDia = $worker->daily_salary;
            $horasSemanales = (float) $settings->get('payroll.hours_per_week');
            $assign_family = PercentageWorker::where('name', 'assign_family')->first();
            $rmv = PercentageWorker::where('name', 'rmv')->first();
            $horasOrdinarias = round(($h_ord + $h_esp), 2);
            $montoHorasOrdinarias = round(($h_ord + $h_esp)*(($worker->daily_salary/$horasXDia)*1), 2);
            $horasAl25 = round($h_25, 2);
            $montoHorasAl25 = round($h_25*(($worker->daily_salary/$horasXDia)*1.25), 2);
            $horasAl35 = round($h_35, 2);
            $montoHorasAl35 = round($h_35*(($worker->daily_salary/$horasXDia)*1.35), 2);
            $horasAl100 = round($h_100, 2);
            $montoHorasAl100 = round($h_100*(($worker->daily_salary/$horasXDia)*2), 2);
            $dominical = round((($h_ord + $h_esp)/$horasSemanales)*4, 2);
            $montoDominical = round(((($h_ord + $h_esp)/$horasSemanales)*4)*($pagoXDia), 2);
            $asignacionFamiliarSemanal = round((($rmv->value*($assign_family->value/100))/$numberDays)*$numberDays, 2);

            $daysVacation = $this->getVacationByWorker($worker_id, $start, $end);
            $vacaciones = $daysVacation*$horasXDia;
            $montoVacaciones = round($daysVacation*$horasXDia*$pagoXHora, 2);

            $amountRefund = $this->getRefundByWorker($worker_id, $start, $end);
            $reintegro = round($amountRefund, 2);


            $systemPension = ($worker->pension_system_id == null) ? 'No tiene': PensionSystem::find($worker->pension_system_id);
            $SPPAportacionObligatoria = ($worker->pension_system_id == null) ? 0 : round(($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $montoVacaciones + $reintegro)*($systemPension->percentage/100), 2);

            $percentageEssalud = PercentageWorker::where('name', 'essalud')->first();
            $ESSALUD = round(($asignacionFamiliarSemanal + $montoHorasOrdinarias + $montoHorasAl25 + $montoHorasAl35 +  $montoHorasAl100 + $montoDominical + $montoVacaciones + $reintegro + $gratificacion)*($percentageEssalud->value/100), 2);

            $totalDeIngresos = round($trabajoSobretiempo25 + $trabajoSobretiempo35 + $trabajoEnFeriadoODiaDescanso + $remuneracionOJornalBasico + $bonificacionExtraordinariaTemporal + $gratificacion, 2);

            $totalDeDescuentos = round($comisionAfpPorcentual + $rentaQuintaCategoria + $primaDeSeguroAFP + $SPPAportacionObligatoria, 2);

            $netoAPagar = round($totalDeIngresos - $totalDeDescuentos, 2);
            return response()->json([
                'ruc_m' => 'RUC: '.$ruc,
                'empleador_m' => 'Empleador: '.$empleador,
                'periodo_m' => 'Periodo: '.$periodo,
                'tipo_m' => $tipoDocumento,
                'dni_m' => $dni,
                'empleado_m' => $mombreApellidos,
                'situacion_m' => $situacion,
                'fecha_ingreso_m' => $fechaIngreso,
                'tipo_trabajador_m' => $tipoTrabajador,
                'sistema_pensionario_m' => $regimenPensionario,
                'cuspp_m' => $CUSPP,
                'dias_laborados_m' => $diasLaborados,
                'dias_no_laborados_m' => $diasNoLaborados,
                'dias_subsidiados_m' => $diasSubsidiados,
                'condicion_m' => $condicion,
                'jornada_ordinaria_m' => $totalHorasOrdinarias,
                'sobretiempo_m' => $totalHorasSobretiempo,
                'trabajo_sobretiempo_25_m' => $trabajoSobretiempo25,
                'trabajo_sobretiempo_35_m' => $trabajoSobretiempo35,
                'trabajo_en_feriado_m' => $trabajoEnFeriadoODiaDescanso,
                'remuneracion_jornal_basico_m' => $remuneracionOJornalBasico,
                'bonificacion_extraordinaria_temporal_m' => $bonificacionExtraordinariaTemporal,
                'gratificacion_m' => $gratificacion,
                'comision_afp_porcentual_m' => $comisionAfpPorcentual,
                'renta_quinta_categoria_m' => $rentaQuintaCategoria,
                'prima_seguro_afp_m' => $primaDeSeguroAFP,
                'aportacion_obligatoria_m' => $SPPAportacionObligatoria,
                'neto_pagar_m' => $netoAPagar,
                'essalud_m' => $ESSALUD,
            ], 200);
        }
    }

    public function getBonusByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $dates = DateDimension::whereDate('date', '>=',$date_start)
            ->whereDate('date', '<=',$end_start)
            ->orderBy('date', 'ASC')
            ->get();

        $amountBonus = 0;
        foreach ( $dates as $date )
        {
            $fecha = Carbon::create($date->year, $date->month, $date->day);
            $specialBonuses = SpecialBonus::whereDate('date',$fecha->format('Y-m-d'))
                ->where('worker_id', $worker->id)
                ->get();
            if ( !empty($specialBonuses) )
            {
                foreach ( $specialBonuses as $bonus )
                {
                    $amountBonus+=$bonus->amount;
                }
            }
        }

        return $amountBonus;
    }

    public function getDiscountByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $dates = DateDimension::whereDate('date', '>=',$date_start)
            ->whereDate('date', '<=',$end_start)
            ->orderBy('date', 'ASC')
            ->get();

        $amountDiscount = 0;
        foreach ( $dates as $date )
        {
            $fecha = Carbon::create($date->year, $date->month, $date->day);
            $discounts = Discount::whereDate('date',$fecha->format('Y-m-d'))
                ->where('worker_id', $worker->id)
                ->get();
            if ( !empty($discounts) )
            {
                foreach ( $discounts as $discount )
                {
                    $amountDiscount+=$discount->amount;
                }
            }
        }

        return $amountDiscount;
    }

    public function getSuspensionsWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $duesArray = [];

        $suspensions = Suspension::whereDate('date_start','>=',$date_start)
            ->whereDate('date_end', '<=',$end_start)
            ->where('worker_id', $worker->id)
            ->get();

        return $suspensions;
    }

    public function getLoanByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $dates = DateDimension::whereDate('date', '>=',$date_start)
            ->whereDate('date', '<=',$end_start)
            ->orderBy('date', 'ASC')
            ->get();

        $amountLoan = 0;
        foreach ( $dates as $date )
        {
            $fecha = Carbon::create($date->year, $date->month, $date->day);
            $dues = Due::whereDate('date',$fecha->format('Y-m-d'))
                ->where('worker_id', $worker->id)
                ->get();
            if ( !empty($dues) )
            {
                foreach ( $dues as $due )
                {
                    $amountLoan+=$due->amount;
                }

            }
        }

        return $amountLoan;
    }


    public function getRentaQuintaByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $amountRentaQuintaCat = 0;

        $fifthCategory = FifthCategory::whereDate('date', '>=', $date_start)
            ->whereDate('date', '<=', $end_start)
            ->where('worker_id', $worker->id)
            ->first();

        if ( !empty($fifthCategory) )
        {
            $amountRentaQuintaCat+=$fifthCategory->amount;
        }

        return $amountRentaQuintaCat;
    }

    public function getGratificationByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $amountRentaQuintaCat = 0;

        $gratification = Gratification::whereDate('date', '>=', $date_start)
            ->whereDate('date', '<=', $end_start)
            ->where('worker_id', $worker->id)
            ->first();

        if ( !empty($gratification) )
        {
            $amountRentaQuintaCat+=$gratification->amount;
        }

        return $amountRentaQuintaCat;
    }


    public function getRefundByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $dates = DateDimension::whereDate('date', '>=',$date_start)
            ->whereDate('date', '<=',$end_start)
            ->orderBy('date', 'ASC')
            ->get();

        $amountRefund = 0;
        foreach ( $dates as $date )
        {
            $fecha = Carbon::create($date->year, $date->month, $date->day);
            $refunds = Refund::whereDate('date',$fecha->format('Y-m-d'))
                ->where('worker_id', $worker->id)
                ->get();
            if ( !empty($refunds) )
            {
                foreach ( $refunds as $refund )
                {
                    $amountRefund+=$refund->amount;
                }
            }
        }

        return $amountRefund;
    }

    public function getVacationByWorker($worker_id, $start, $end)
    {
        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $worker = Worker::find($worker_id);

        $dates = DateDimension::whereDate('date', '>=',$date_start)
            ->whereDate('date', '<=',$end_start)
            ->orderBy('date', 'ASC')
            ->get();

        $hoursVacation = 0;
        $time_break = (float) app(SettingService::class)->get('hr.break_hours');
        foreach ( $dates as $date )
        {
            $fecha = Carbon::create($date->year, $date->month, $date->day);
            $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                ->where('worker_id', $worker->id)
                ->where('status', 'V')
                ->first();
            if ( !empty($assistance_detail) )
            {
                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                //dump('Horas Trabajadas: '. $hoursWorked);
                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                $workingDay = WorkingDay::find($assistance_detail->working_day_id);
                if ($wD && $workingDay->id == $wD->id)
                {
                    if ( $hoursWorked > 4 )
                    {
                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                    } else {
                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount, 2);
                    }

                } else {
                    $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                }

                $hoursVacation+=$hoursNeto;
            }
        }

        return $hoursVacation;
    }

    public function getTotalHoursByWorker($worker_id, $start, $end)
    {
        $arrayByWeek = [];
        if ( $start != '' || $end != '' )
        {
            $date_start = Carbon::createFromFormat('d/m/Y', $start);
            $end_start = Carbon::createFromFormat('d/m/Y', $end);

            $worker = Worker::find($worker_id);
            $dateCurrent = Carbon::now();

            $arrayByDates = [];
            $arrayByWeek = [];
            $arrayByWeekMonth = [];

            // TODO: Array By Dates
            $yearCurrent = $dateCurrent->year;

            $dates = DateDimension::whereDate('date', '>=',$date_start)
                ->whereDate('date', '<=',$end_start)
                ->orderBy('date', 'ASC')
                ->get();

            //dump($dates);

            foreach ( $dates as $date )
            {
                $arrayDayAssistances = [];

                $fecha = Carbon::create($date->year, $date->month, $date->day);
                //dump($fecha);
                $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                    ->where('worker_id', $worker->id)
                    ->whereNotIn('status', ['S', 'F', 'P', 'U', 'TC'])
                    ->first();
                //dump($assistance_detail);
                if ( !empty($assistance_detail) )
                {
                    //dump('Entre opr que si hay asistencia');
                    // TODO: Verificamos las horas especiales: DM, V, L
                    $medicalRests = MedicalRest::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($medicalRests);
                    $vacations = Vacation::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($vacations);
                    $licenses = License::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($licenses);
                    $permit_hour = PermitHour::whereDate('date_start', '=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($permit_hour);
                    $time_break = (float) app(SettingService::class)->get('hr.break_hours');
                    //dump($time_break);
                    $workingDay = WorkingDay::find($assistance_detail->working_day_id);
                    //dump($workingDay);
                    if ( !$this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        //dump('Entré porque no es Feriado y es dia normal');
                        // TODO: No feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 || count($permit_hour)>0 )
                        {
                            if(count($permit_hour)>0 )
                            {
                                //TODO: OBTENER LAS HORAS TRABAJADAS
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //dump('Horas Trabajadas: '. $hoursWorked);
                                //TODO: OBTENER LAS HORAS NETO $hoursWorked - $permit_hour[0]->hour
                                $hoursNeto = round($hoursWorked - $permit_hour[0]->hour - $assistance_detail->hours_discount - $time_break, 2);
                                //dump('Horas de permiso por hora: '. $permit_hour[0]);
                                //TODO: AGREGAR AL ARRAY PUSH EN LAS HORAS ORDINARIAS
                                array_push($arrayDayAssistances, [
                                    $hoursNeto,
                                    0,
                                    0,
                                    0,
                                    0,
                                ]);
                            } elseif (count($vacations)>0) {
                                array_push($arrayDayAssistances, [
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                ]);
                            } else {
                                ///dump('Entré porque hay Horas especiales');
                                // TODO: Con H-ESP
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //dump('Horas Trabajadas: '. $hoursWorked);
                                //$hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    if ( $hoursWorked > 4 )
                                    {
                                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                    } else {
                                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount, 2);
                                    }

                                } else {
                                    $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                }
                                //dump('Horas Trabajadas: '. $hoursNeto);
                                array_push($arrayDayAssistances, [
                                    0,
                                    0,
                                    0,
                                    0,
                                    $hoursNeto,
                                ]);
                            }
                            //dump($arrayDayAssistances);
                        } else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();

                            if ($wD && $workingDay->id == $wD->id) {
                                // Existe $wD y además coincide el ID
                                if ($hoursWorked > 4) {
                                    $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                } else {
                                    $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount, 2);
                                }
                            } else {
                                // No existe $wD o no coincide el ID
                                $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            }
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                //dump('Entre porqe detectamos horas extras');
                                // TODO: Detectamos horas extras
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    $hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount , 2);
                                } else {
                                    $hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);
                                }
                                //$hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);
                                //dump('$hoursOrdinary' . $hoursOrdinary);
                                $hoursExtrasTotals = $hoursTotals - $hoursOrdinary;
                                //dump('$hoursExtrasTotals' . $hoursExtrasTotals);
                                if ( $hoursExtrasTotals > 0 && $hoursExtrasTotals < 2 ) {
                                    $hours25 = $hoursExtrasTotals;
                                    //dump('$hours25' . $hours25);
                                } else {
                                    $hours25 = 2;
                                    //dump('$hours25' . $hours25);
                                    $hours35 = $hoursExtrasTotals-2;
                                    //dump('$hours35' . $hours35);
                                }
                            } else {
                                //dump('Entre porqe no detectamos horas extras');
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    $hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount ;
                                } else {
                                    $hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                }
                                //$hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                //dump('$hoursOrdinary' . $hoursOrdinary);
                            }

                            array_push($arrayDayAssistances, [
                                $hoursOrdinary,
                                $hours25,
                                $hours35,
                                $hours100,
                                0,
                            ]);
                            //dump($arrayDayAssistances);
                        }

                    } elseif ( !$this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: No feriado - Domingo
                        if ( count($medicalRests)>0 /*|| count($vacations)>0*/ || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                0,
                                $hoursNeto,
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursTotals,
                                0,
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        // TODO: Feriado - Dia Normal (L-S)
                        // TODO: Ultimo Cambio
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            if ( count($vacations)>0 )
                            {
                                array_push($arrayDayAssistances, [
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                ]);
                            } else {
                                // TODO: Con H-ESP
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //$hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    if ( $hoursWorked > 4 )
                                    {
                                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                    } else {
                                        $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount, 2);
                                    }

                                } else {
                                    $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                }
                                array_push($arrayDayAssistances, [
                                    0,
                                    0,
                                    0,
                                    0,
                                    $hoursNeto,
                                ]);
                            }

                        } else {
                            // TODO: Sin H-ESP
                            if ( $assistance_detail->status == 'A' )
                            {
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    if ( $hoursWorked > 4 )
                                    {
                                        $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                    } else {
                                        $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount, 2);
                                    }

                                } else {
                                    $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                }
                                //$hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $assistance_detail->hours_discount;

                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                    // TODO: Detectamos horas extras
                                    $hoursOrdinary1 = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);

                                    $hoursExtrasTotals = $hoursTotals - $hoursOrdinary1;
                                    $hours100 = $hoursExtrasTotals;
                                } else {
                                    $hoursOrdinary1 = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                }

                                array_push($arrayDayAssistances, [
                                    $hoursOrdinary,
                                    0,
                                    0,
                                    $hours100+$hoursOrdinary1,
                                    0,
                                ]);
                            } elseif ( $assistance_detail->status == 'H' ) {
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ($wD && $workingDay->id == $wD->id)
                                {
                                    if ( $hoursWorked > 4 )
                                    {
                                        $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                    } else {
                                        $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount, 2);
                                    }

                                } else {
                                    $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                }
                                $hoursOrdinary = 0;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                    // TODO: Detectamos horas extras
                                    $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                    if ($wD && $workingDay->id == $wD->id)
                                    {
                                        $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount ;
                                    } else {
                                        $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                    }
                                    //$hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);

                                    $hoursExtrasTotals = $hoursTotals - $hoursOrdinary;
                                    $hours100 = $hoursExtrasTotals;
                                } else {
                                    $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                    if ($wD && $workingDay->id == $wD->id)
                                    {
                                        $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount ;
                                    } else {
                                        $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                    }
                                    //$hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                }

                                array_push($arrayDayAssistances, [
                                    $hoursOrdinary,
                                    0,
                                    0,
                                    0,
                                    0,
                                ]);
                            }

                        }

                    } elseif ( $this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: Feriado - Domingo
                        if ( count($medicalRests)>0 /*|| count($vacations)>0*/ || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursNeto,
                                $hoursNeto,
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursTotals,
                                0,
                            ]);
                        }
                    }

                }
                else {
                    if ( $fecha->isSunday() ) {
                        array_push($arrayDayAssistances, [
                            '',
                            '',
                            '',
                            0,
                            0,
                        ]);
                    } else {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            0,
                            0,
                            0,
                        ]);
                    }
                }

                array_push($arrayByDates, [
                    'week' => $date->week,
                    'date' => $date->date->format('d/m/Y'),
                    'month' => $date->month_name_year,
                    'day' => $date->day,
                    'assistances' => $arrayDayAssistances
                ]);
            }

            //dump($arrayByDates);
            //dd();

            $first = true;

            $h_ord = 0;
            $h_25 = 0;
            $h_35 = 0;
            $h_100 = 0;
            $h_esp = 0;

            $fecha = '';
            $week = '';
            $month = '';

            //dump($arrayByDates);
            //dd();

            for ( $i=0; $i<count($arrayByDates); $i++ )
            {
                //dump($arrayByDates[$i]['date']);
                if ( $first ) {
                    //dump( 'Sem Act '.$arrayByDates[$i]['week'] .' - Sem Sig '. $arrayByDates[$i+1]['week'] );
                    $week2 = ($i == count($arrayByDates)-1) ? 0:$arrayByDates[$i+1]['week'] ;
                    //dump($week);
                    if ( $arrayByDates[$i]['week'] != $week2 )
                    {
                        $dayStart = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . 'DEL '. $dayStart .' AL ' . $dayStart;
                        $week = $week . $arrayByDates[$i]['week'];
                        $month = $month . $arrayByDates[$i]['month'];

                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = true;
                        array_push($arrayByWeek, [
                            'week'  => $week,
                            'date'  => $fecha,
                            'month' => $month,
                            'h_ord' => $h_ord,
                            'h_25'  => $h_25,
                            'h_35'  => $h_35,
                            'h_100' => $h_100,
                            'h_esp' => $h_esp,
                        ]);
                        //dump($arrayByWeek);

                        $fecha = '';
                        $week = '';
                        $month = '';
                        $h_ord = 0;
                        $h_25 = 0;
                        $h_35 = 0;
                        $h_100 = 0;
                        $h_esp = 0;


                    } else {
                        $dayStart = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . 'DEL '. $dayStart .' AL ';
                        $week = $week . $arrayByDates[$i]['week'];
                        $month = $month . $arrayByDates[$i]['month'];

                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = false;

                    }

                }
                else {
                    if ( ($i == count($arrayByDates)-1) || ( (isset($arrayByDates[$i+1])) && ($arrayByDates[$i]['week'] != $arrayByDates[$i+1]['week']) ) )
                    {
                        $dayEnd = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . $dayEnd;
                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = true;
                        array_push($arrayByWeek, [
                            'week'  => $week,
                            'date'  => $fecha,
                            'month' => $month,
                            'h_ord' => $h_ord,
                            'h_25'  => $h_25,
                            'h_35'  => $h_35,
                            'h_100' => $h_100,
                            'h_esp' => $h_esp,
                        ]);
                        //dump($arrayByWeek);

                        $fecha = '';
                        $week = '';
                        $month = '';
                        $h_ord = 0;
                        $h_25 = 0;
                        $h_35 = 0;
                        $h_100 = 0;
                        $h_esp = 0;

                    } else {
                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = false;
                    }


                }

            }
        }
        /*else {

            $worker = Worker::find($worker_id);
            $dateCurrent = Carbon::now();

            $arrayByDates = [];
            $arrayByWeek = [];
            $arrayByWeekMonth = [];

            // TODO: Array By Dates
            $yearCurrent = $dateCurrent->year;

            $dates = DateDimension::where('year', $yearCurrent)
                ->orderBy('date', 'ASC')
                ->get();

            foreach ( $dates as $date )
            {
                $arrayDayAssistances = [];

                $fecha = Carbon::create($date->year, $date->month, $date->day);
                //dump($fecha);
                $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                    ->where('worker_id', $worker->id)
                    ->where('status', '<>', 'S')
                    ->first();
                //dump($assistance_detail);
                if ( !empty($assistance_detail) )
                {
                    //dump('Entre opr que si hay asistencia');
                    // TODO: Verificamos las horas especiales: DM, V, L
                    $medicalRests = MedicalRest::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($medicalRests);
                    $vacations = Vacation::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($vacations);
                    $licenses = License::whereDate('date_start', '<=',$fecha->format('Y-m-d'))
                        ->whereDate('date_end', '>=',$fecha->format('Y-m-d'))
                        ->where('worker_id', $worker->id)
                        ->get();
                    //dump($licenses);
                    $time_break = (float) app(SettingService::class)->get('hr.break_hours');
                    //dump($time_break);
                    $workingDay = WorkingDay::find($assistance_detail->working_day_id);
                    //dump($workingDay);
                    if ( !$this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        //dump('Entré porque no es Feriado y es dia normal');
                        // TODO: No feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            ///dump('Entré porque hay Horas especiales');
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            //dump('Horas Trabajadas: '. $hoursWorked);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Trabajadas: '. $hoursNeto);
                            array_push($arrayDayAssistances, [
                                0,
                                0,
                                0,
                                0,
                                $hoursNeto,
                            ]);
                            //dump($arrayDayAssistances);
                        } else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                            if ( $workingDay->id == $wD->id )
                            {
                                $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount, 2);
                            } else {
                                $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            }
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                //dump('Entre porqe detectamos horas extras');
                                // TODO: Detectamos horas extras
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ( $workingDay->id == $wD->id )
                                {
                                    $hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount , 2);
                                } else {
                                    $hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);
                                }
                                //$hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);
                                //dump('$hoursOrdinary' . $hoursOrdinary);
                                $hoursExtrasTotals = $hoursTotals - $hoursOrdinary;
                                //dump('$hoursExtrasTotals' . $hoursExtrasTotals);
                                if ( $hoursExtrasTotals > 0 && $hoursExtrasTotals < 2 ) {
                                    $hours25 = $hoursExtrasTotals;
                                    //dump('$hours25' . $hours25);
                                } else {
                                    $hours25 = 2;
                                    //dump('$hours25' . $hours25);
                                    $hours35 = $hoursExtrasTotals-2;
                                    //dump('$hours35' . $hours35);
                                }
                            } else {
                                //dump('Entre porqe no detectamos horas extras');
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                if ( $workingDay->id == $wD->id )
                                {
                                    $hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount ;
                                } else {
                                    $hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                }
                                //$hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                //dump('$hoursOrdinary' . $hoursOrdinary);
                            }

                            array_push($arrayDayAssistances, [
                                $hoursOrdinary,
                                $hours25,
                                $hours35,
                                $hours100,
                                0,
                            ]);
                            //dump($arrayDayAssistances);
                        }

                    } elseif ( !$this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: No feriado - Domingo
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                0,
                                $hoursNeto,
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursTotals,
                                0,
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        // TODO: Feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                0,
                                0,
                                0,
                                0,
                                $hoursNeto,
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            $hoursOrdinary = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                // TODO: Detectamos horas extras
                                $hoursOrdinary = round( (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount , 2);

                                $hoursExtrasTotals = $hoursTotals - $hoursOrdinary;
                                $hours100 = $hoursExtrasTotals;
                            } else {
                                $hoursOrdinary = (Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                            }

                            array_push($arrayDayAssistances, [
                                $hoursOrdinary,
                                0,
                                0,
                                $hours100+$hoursOrdinary,
                                0,
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: Feriado - Domingo
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursNeto,
                                $hoursNeto,
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                '',
                                '',
                                '',
                                $hoursTotals,
                                0,
                            ]);
                        }
                    }

                }
                else {
                    if ( $fecha->isSunday() ) {
                        array_push($arrayDayAssistances, [
                            '',
                            '',
                            '',
                            0,
                            0,
                        ]);
                    } else {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            0,
                            0,
                            0,
                        ]);
                    }
                }

                array_push($arrayByDates, [
                    'week' => $date->week,
                    'date' => $date->date->format('d/m/Y'),
                    'month' => $date->month_name_year,
                    'day' => $date->day,
                    'assistances' => $arrayDayAssistances
                ]);
            }

            //dump($arrayByDates);
            //dd();

            $first = true;

            $h_ord = 0;
            $h_25 = 0;
            $h_35 = 0;
            $h_100 = 0;
            $h_esp = 0;

            $fecha = '';
            $week = '';
            $month = '';

            for ( $i=0; $i<count($arrayByDates); $i++ )
            {
                //dump($arrayByDates[$i]['date']);
                if ( $first ) {
                    $week2 = ($i == count($arrayByDates)-1) ? 0:$arrayByDates[$i+1]['week'] ;
                    //dump($week);
                    if ( $arrayByDates[$i]['week'] != $week2 )
                    {
                        $dayStart = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . 'DEL '. $dayStart .' AL ' . $dayStart;
                        $week = $week . $arrayByDates[$i]['week'];
                        $month = $month . $arrayByDates[$i]['month'];

                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = true;
                        array_push($arrayByWeek, [
                            'week'  => $week,
                            'date'  => $fecha,
                            'month' => $month,
                            'h_ord' => $h_ord,
                            'h_25'  => $h_25,
                            'h_35'  => $h_35,
                            'h_100' => $h_100,
                            'h_esp' => $h_esp,
                        ]);

                        $fecha = '';
                        $week = '';
                        $month = '';
                        $h_ord = 0;
                        $h_25 = 0;
                        $h_35 = 0;
                        $h_100 = 0;
                        $h_esp = 0;


                    } else {
                        $dayStart = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . 'DEL '. $dayStart .' AL ';
                        $week = $week . $arrayByDates[$i]['week'];
                        $month = $month . $arrayByDates[$i]['month'];

                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = false;

                    }
                } else {
                    if ( ($i == count($arrayByDates)-1) || ( (isset($arrayByDates[$i+1])) && ($arrayByDates[$i]['week'] != $arrayByDates[$i+1]['week']) ) )
                    {
                        $dayEnd = ($arrayByDates[$i]['day'] < 10) ? '0'.$arrayByDates[$i]['day']: $arrayByDates[$i]['day'];

                        $fecha = $fecha . $dayEnd;
                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = true;
                        array_push($arrayByWeek, [
                            'week'  => $week,
                            'date'  => $fecha,
                            'month' => $month,
                            'h_ord' => $h_ord,
                            'h_25'  => $h_25,
                            'h_35'  => $h_35,
                            'h_100' => $h_100,
                            'h_esp' => $h_esp,
                        ]);

                        $fecha = '';
                        $week = '';
                        $month = '';
                        $h_ord = 0;
                        $h_25 = 0;
                        $h_35 = 0;
                        $h_100 = 0;
                        $h_esp = 0;

                    } else {
                        $h_ord += ($arrayByDates[$i]['assistances'][0][0] == '') ? 0: $arrayByDates[$i]['assistances'][0][0];
                        $h_25  += ($arrayByDates[$i]['assistances'][0][1] == '') ? 0: $arrayByDates[$i]['assistances'][0][1];
                        $h_35  += ($arrayByDates[$i]['assistances'][0][2] == '') ? 0: $arrayByDates[$i]['assistances'][0][2];
                        $h_100 += ($arrayByDates[$i]['assistances'][0][3] == '') ? 0: $arrayByDates[$i]['assistances'][0][3];
                        $h_esp += ($arrayByDates[$i]['assistances'][0][4] == '') ? 0: $arrayByDates[$i]['assistances'][0][4];
                        $first = false;
                    }


                }


            }
        }*/

        //dump($arrayByWeek);
        //dump($arrayByDates);
        //dd();

        return $arrayByWeek;

    }

    private function isHoliday(Carbon $fecha)
    {
        $holiday = Holiday::whereDate('date_complete', '=',$fecha->format('Y-m-d'))->first();
        return ( !empty($holiday) ) ? true:false ;
    }

}
