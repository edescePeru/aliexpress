<?php

namespace App\Http\Controllers;

use App\Assistance;
use App\AssistanceDetail;
use App\DateDimension;
use App\Exports\AssistanceExcelMultipleSheets;
use App\Exports\HoursDiaryExcelExport;
use App\Exports\TotalHoursExcelMultipleSheet;
use App\Exports\TotalPaysAccountsExcelMultipleSheet;
use App\FinishContract;
use App\Holiday;
use App\License;
use App\MedicalRest;
use App\PaySlip;
use App\Permit;
use App\PermitHour;
use App\Regime;
use App\RegimeDetail;
use App\Suspension;
use App\UnpaidLicense;
use App\Vacation;
use App\Worker;
use App\WorkerAccount;
use App\WorkingDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\SettingService;

class AssistanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $assistances = Assistance::select('id', 'date_assistance')->get();
        $holidays = Holiday::select('id', 'date_complete', 'description')->get();

        $events = [];
        foreach ( $assistances as $assistance )
        {
            array_push($events, [
                'title' => 'Asistencia '.$assistance->date_assistance->format('d/m/Y'),
                'start' => $assistance->date_assistance->format('Y-m-d'),
                'backgroundColor' => '#0A23F7', //red
                'borderColor' => '#0A23F7', //red
                'allDay' => true
            ]);

        }

        foreach ( $holidays as $holiday )
        {
            array_push($events, [
                'title' => $holiday->description,
                'start' => $holiday->date_complete->format('Y-m-d'),
                'backgroundColor' => '#117811', //red
                'borderColor' => '#117811', //red
                'allDay' => true
            ]);
        }

        return view('assistance.index', compact( 'permissions', 'events'));

    }

    public function checkAssitanceForCreate( $fecha )
    {
        $date_assistance = Carbon::createFromFormat('Y-m-d', $fecha);

        $assistance = Assistance::where('date_assistance', $fecha)->first();
        if ( isset($assistance) )
        {
            // Si existe cronograma, redireccionar al manage
            return response()->json([
                'message' => 'Redireccionando ...',
                'url' => route('assistance.register', $assistance->id),
                'res' => 1
            ], 200);

        } else {
            if ( Auth::user()->hasRole('contabilidad') )
            {
                return response()->json([
                    'message' => 'Lo sentimos. No tiene los permisos de crear asistencias.',
                    'url' => "",
                    'res' => 3
                ], 200);
            } else {
                // Si no hay cronograma, creamos y redireccionamos
                $assistance2 = Assistance::create([
                    'date_assistance' => $date_assistance
                ]);

                // Creamos los detalles de la asistencia
                $workers = Worker::where('enable', true)
                    ->get();

                //$workingDay = WorkingDay::where('enable', true)->first();

                foreach ( $workers as $worker) {
                    // TODO: Revisamos si hay Descansos Medicos
                    //dump($worker->first_name .' '. $worker->last_name);
                    $medicalRests = MedicalRest::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $vacations = Vacation::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $licenses = License::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $permits = Permit::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $suspensions = Suspension::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $unpaidLicenses = UnpaidLicense::whereDate('date_start', '<=',$assistance2->date_assistance)
                        ->whereDate('date_end', '>=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();
                    $permits_hours = PermitHour::whereDate('date_start', '=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->get();

                    $finish_contract = FinishContract::whereDate('date_finish', '<=',$assistance2->date_assistance)
                        ->where('worker_id', $worker->id)
                        ->where('active', 1)
                        ->get();

                    // TODO: Seleccionar segun el día si es LUN - JUE y SAB La jornada 1
                    // TODO: Si es VIE la jornada 2

                    /*if ( $date_assistance->dayOfWeek != Carbon::FRIDAY )
                    {*/
                    // TODO: Se selecciona el regimen luego el dia y por ultimo el horario
                    $regime = Regime::where('active', true)->first();
                    $dayNum = $assistance2->date_assistance->dayOfWeek;

                    $regimeDetail = RegimeDetail::where('regime_id', $regime->id)
                        ->where('dayNumber', $dayNum)->first();

                    //$workingDay = WorkingDay::where('enable', true)->first();
                    $workingDay = WorkingDay::find($regimeDetail->working_day_id);
                    /*} else {
                        $workingDay = WorkingDay::where('enable', true)->skip(1)->take(1)->first();
                    }*/
                    if ( count($medicalRests) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'M'
                        ]);
                    } elseif ( count($vacations) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'V'
                        ]);
                    } elseif ( count($licenses) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'L'
                        ]);
                    } elseif ( count($permits) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'P'
                        ]);
                    } elseif ( count($suspensions) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'S'
                        ]);
                    } elseif ( count($unpaidLicenses) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'U'
                        ]);
                    } elseif ( count($permits_hours) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'PH'
                        ]);
                    } elseif ( count($finish_contract) > 0 ) {
                        AssistanceDetail::create([
                            'date_assistance' => $assistance2->date_assistance,
                            'hour_entry' => $workingDay->time_start,
                            'hour_out' => $workingDay->time_fin,
                            'worker_id' => $worker->id,
                            'assistance_id' => $assistance2->id,
                            'working_day_id' => $workingDay->id,
                            'status' => 'TC'
                        ]);
                    } else {
                        if ( $this->isHoliday($assistance2->date_assistance) )
                        {
                            AssistanceDetail::create([
                                'date_assistance' => $assistance2->date_assistance,
                                'hour_entry' => $workingDay->time_start,
                                'hour_out' => $workingDay->time_fin,
                                'worker_id' => $worker->id,
                                'assistance_id' => $assistance2->id,
                                'working_day_id' => $workingDay->id,
                                'status' => 'H'
                            ]);
                        } else {
                            AssistanceDetail::create([
                                'date_assistance' => $assistance2->date_assistance,
                                'hour_entry' => $workingDay->time_start,
                                'hour_out' => $workingDay->time_fin,
                                'worker_id' => $worker->id,
                                'assistance_id' => $assistance2->id,
                                'working_day_id' => $workingDay->id,
                                'status' => 'A'
                            ]);
                        }

                    }

                }

                //dd();
                return response()->json([
                    'message' => 'Se ha creado la asistencia y redireccionando ... ',
                    'url' => route('assistance.register', $assistance2->id),
                    'res' => 2
                ], 200);
            }

        }
    }

    public function createAssistance($assistance_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::where('enable', true)
            ->get();
        //dump($workers);

        $workingDays = WorkingDay::where('enable', true)
            ->get();
        //dump($workingDays);
        $assistance = Assistance::find($assistance_id);
        //dump($assistance);
        $arrayAssistances = [];
        foreach ( $workers as $worker) {
            $assistancesDetail = AssistanceDetail::where('assistance_id', $assistance_id)
                ->where('worker_id', $worker->id)->first();
            //dump($assistancesDetail);
            if ( isset( $assistancesDetail ) )
            {
                array_push($arrayAssistances, [
                    'worker' => $worker->first_name.' '.$worker->last_name,
                    'worker_id' => $worker->id,
                    'working_day' => $assistancesDetail->working_day_id,
                    'hour_entry' => $assistancesDetail->hour_entry,
                    'hour_out' => $assistancesDetail->hour_out,
                    'status' => $assistancesDetail->status,
                    'obs_justification' => $assistancesDetail->obs_justification,
                    'hours_discount' => $assistancesDetail->hours_discount,
                    'assistance_detail_id' => $assistancesDetail->id
                ]);
            } else {
                array_push($arrayAssistances, [
                    'worker' => $worker->first_name.' '.$worker->last_name,
                    'worker_id' => $worker->id,
                    'working_day' => '',
                    'hour_entry' => '',
                    'hour_out' => '',
                    'status' => '',
                    'obs_justification' => '',
                    'assistance_detail_id' => '',
                    'hours_discount' => 0,
                ]);
            }
        }

        //dump($arrayAssistances);
        //dd();

        return view('assistance.register', compact( 'permissions', 'assistance', 'workingDays', 'arrayAssistances'));

    }

    public function showAssistance()
    {
        Carbon::setLocale(config('app.locale'));
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::where('enable', true)->get();

        $date = Carbon::now('America/Lima')->locale('es');
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;
        $weekOfYear = $date->weekOfYear;

        $arrayAssistances = [];
        $arrayWeeksDays = [];
        $arrayWeeks = [];
        $arrayWeekWithDays = [];

        for ( $i = 1; $i<=$date->daysInMonth; $i++ )
        {
            $fecha = Carbon::create($yearCurrent,$monthCurrent,$i);
            array_push($arrayWeeks, $fecha->weekOfYear);

        }
        $arrayWeeksDays = array_values(array_unique($arrayWeeks));
        //dump($arrayWeeksDays);


        for ( $i = 0; $i<count($arrayWeeksDays); $i++ )
        {
            $days = [];
            for ( $j = 1; $j<=$date->daysInMonth; $j++ ) {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $j);
                if ( $fecha->weekOfYear == $arrayWeeksDays[$i] )
                {
                    array_push($days, $fecha->format('jS \d\e M'));
                }

            }
            array_push($arrayWeekWithDays, [
                'week' => 'Semana ' . $arrayWeeksDays[$i],
                'days' => $days
            ]);
        }

        //dump($arrayWeekWithDays);
        /*for ( $k=0 ; $k<count($arrayWeekWithDays) ; $k++)
        {
            dump($arrayWeekWithDays[$k]['week']);
        }*/

        $arraySummary = [];
        $cantA = 0;
        $cantF = 0;
        $cantS = 0;
        $cantM = 0;
        $cantJ = 0;
        $cantV = 0;
        $cantP = 0;
        $cantT = 0;
        $cantH = 0;
        $cantPH = 0;
        $cantTC = 0;

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];
            $cantA = 0;
            $cantF = 0;
            $cantS = 0;
            $cantM = 0;
            $cantJ = 0;
            $cantV = 0;
            $cantP = 0;
            $cantT = 0;
            $cantH = 0;
            $cantL = 0;
            $cantU = 0;
            $cantPH = 0;
            $cantTC = 0;

            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
                $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                    ->where('worker_id', $worker->id)
                    ->first();

                if ( !empty($assistance_detail) )
                {
                    $color = '';
                    $estado = '';
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    if ( $assistance_detail->status == 'A' )
                    {
                        $color = '#28a745';
                        $estado = 'A';
                        $cantA = $cantA + 1;
                    } elseif ( $assistance_detail->status == 'F' ) {
                        $color = '#dc3545';
                        $estado = 'F';
                        $cantF = $cantF + 1;
                    } elseif ( $assistance_detail->status == 'S' ){
                        $color = '#52585d';
                        $estado = 'S';
                        $cantS = $cantS + 1;
                    } elseif ( $assistance_detail->status == 'M' ){
                        $color = '#17a2b8';
                        $estado = 'M';
                        $cantM = $cantM + 1;
                    } elseif ( $assistance_detail->status == 'J' ){
                        $color = '#ffc107';
                        $estado = 'J';
                        $cantJ = $cantJ + 1;
                    } elseif ( $assistance_detail->status == 'V' ){
                        $color = '#f012be';
                        $estado = 'V';
                        $cantV = $cantV + 1;
                    } elseif ( $assistance_detail->status == 'P' ){
                        $color = '#007bff';
                        $estado = 'P';
                        $cantP = $cantP + 1;
                    } elseif ( $assistance_detail->status == 'H' ){
                        $color = '#001f3f';
                        $estado = 'H';
                        $cantH = $cantH + 1;
                    } elseif ( $assistance_detail->status == 'L' ){
                        $color = '#42F1CC';
                        $estado = 'L';
                        $cantL = $cantL + 1;
                    } elseif ( $assistance_detail->status == 'T' ){
                        $color = '#6610f2';
                        $estado = 'T';
                        $cantT = $cantT + 1;
                    } elseif ( $assistance_detail->status == 'U' ){
                        $color = '#c32169';
                        $estado = 'U';
                        $cantU = $cantU + 1;
                    }elseif ( $assistance_detail->status == 'PH' ){
                        $color = '#9cf210';
                        $estado = 'PH';
                        $cantPH = $cantPH + 1;
                    } elseif ( $assistance_detail->status == 'TC' ){
                        $color = '#ff851b';
                        $estado = 'TC';
                        $cantTC = $cantTC + 1;
                    }  else {
                        $color = '#fff';
                        $estado = 'N';
                    }

                    array_push($arrayDayAssistances, [
                        'day' => $assistance_detail->date_assistance,
                        'number_day' => $i,
                        'status' => $estado,
                        'color' => $color,
                        'bg_color' => $backgroundColor
                    ]);
                } else {
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    array_push($arrayDayAssistances, [
                        'day' => $fecha->format('d/m/Y'),
                        'number_day' => $i,
                        'status' => 'N',
                        'color' => '#fff',
                        'bg_color' => $backgroundColor
                    ]);
                }

            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);

            array_push($arraySummary, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'cantA' => $cantA,
                'cantF' => $cantF,
                'cantS' => $cantS,
                'cantM' => $cantM,
                'cantJ' => $cantJ,
                'cantV' => $cantV,
                'cantP' => $cantP,
                'cantT' => $cantT,
                'cantH' => $cantH,
                'cantL' => $cantL,
                'cantU' => $cantU,
                'cantPH' => $cantPH,
                'cantTC' => $cantTC
            ]);
        }



        //dump($arrayAssistances);

        return view('assistance.show', compact( 'permissions', 'yearCurrent', 'monthCurrent', 'nameMonth', 'weekOfYear', 'arrayWeekWithDays', 'arrayAssistances', 'arraySummary'));

    }

    public function getAssistancesMonthYear( $month, $year )
    {
        $workers = Worker::where('enable', true)->get();

        $date = Carbon::create($year,$month,1);
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;

        $arrayAssistances = [];
        $arrayWeeksDays = [];
        $arrayWeeks = [];
        $arrayWeekWithDays = [];

        for ( $i = 1; $i<=$date->daysInMonth; $i++ )
        {
            $fecha = Carbon::create($yearCurrent,$monthCurrent,$i);
            array_push($arrayWeeks, $fecha->weekOfYear);

        }
        $arrayWeeksDays = array_values(array_unique($arrayWeeks));
        //dump($arrayWeeksDays);


        for ( $i = 0; $i<count($arrayWeeksDays); $i++ )
        {
            $days = [];
            for ( $j = 1; $j<=$date->daysInMonth; $j++ ) {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $j);
                if ( $fecha->weekOfYear == $arrayWeeksDays[$i] )
                {
                    array_push($days, $fecha->format('jS \d\e M'));
                }

            }
            array_push($arrayWeekWithDays, [
                'week' => 'Semana ' . $arrayWeeksDays[$i],
                'days' => $days
            ]);
        }

        //dump($arrayWeekWithDays);
        /*for ( $k=0 ; $k<count($arrayWeekWithDays) ; $k++)
        {
            dump($arrayWeekWithDays[$k]['week']);
        }*/
        $arraySummary = [];
        $cantA = 0;
        $cantF = 0;
        $cantS = 0;
        $cantM = 0;
        $cantJ = 0;
        $cantV = 0;
        $cantP = 0;
        $cantT = 0;
        $cantH = 0;
        $cantL = 0;
        $cantPH = 0;
        $cantTC = 0;

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];
            $cantA = 0;
            $cantF = 0;
            $cantS = 0;
            $cantM = 0;
            $cantJ = 0;
            $cantV = 0;
            $cantP = 0;
            $cantT = 0;
            $cantH = 0;
            $cantL = 0;
            $cantU = 0;
            $cantPH = 0;
            $cantTC = 0;
            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
                $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                    ->where('worker_id', $worker->id)
                    ->first();

                if ( !empty($assistance_detail) )
                {
                    $color = '';
                    $estado = '';
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    if ( $assistance_detail->status == 'A' )
                    {
                        $color = '#28a745';
                        $estado = 'A';
                        $cantA = $cantA + 1;
                    } elseif ( $assistance_detail->status == 'F' ) {
                        $color = '#dc3545';
                        $estado = 'F';
                        $cantF = $cantF + 1;
                    } elseif ( $assistance_detail->status == 'S' ){
                        $color = '#52585d';
                        $estado = 'S';
                        $cantS = $cantS + 1;
                    } elseif ( $assistance_detail->status == 'M' ){
                        $color = '#17a2b8';
                        $estado = 'M';
                        $cantM = $cantM + 1;
                    } elseif ( $assistance_detail->status == 'J' ){
                        $color = '#ffc107';
                        $estado = 'J';
                        $cantJ = $cantJ + 1;
                    } elseif ( $assistance_detail->status == 'V' ){
                        $color = '#f012be';
                        $estado = 'V';
                        $cantV = $cantV + 1;
                    } elseif ( $assistance_detail->status == 'P' ){
                        $color = '#007bff';
                        $estado = 'P';
                        $cantP = $cantP + 1;
                    } elseif ( $assistance_detail->status == 'H' ){
                        $color = '#001f3f';
                        $estado = 'H';
                        $cantH = $cantH + 1;
                    } elseif ( $assistance_detail->status == 'L' ){
                        $color = '#42F1CC';
                        $estado = 'L';
                        $cantL = $cantL + 1;
                    } elseif ( $assistance_detail->status == 'T' ){
                        $color = '#6610f2';
                        $estado = 'T';
                        $cantT = $cantT + 1;
                    } elseif ( $assistance_detail->status == 'U' ){
                        $color = '#c32169';
                        $estado = 'U';
                        $cantU = $cantU + 1;
                    } elseif ( $assistance_detail->status == 'PH' ){
                        $color = '#9cf210';
                        $estado = 'PH';
                        $cantPH = $cantPH + 1;
                    } elseif ( $assistance_detail->status == 'TC' ){
                        $color = '#ff851b';
                        $estado = 'TC';
                        $cantTC = $cantTC + 1;
                    } else {
                        $color = '#fff';
                        $estado = 'N';
                    }

                    array_push($arrayDayAssistances, [
                        'day' => $assistance_detail->date_assistance,
                        'number_day' => $i,
                        'status' => $estado,
                        'color' => $color,
                        'bg_color' => $backgroundColor
                    ]);
                } else {
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    array_push($arrayDayAssistances, [
                        'day' => $fecha->format('d/m/Y'),
                        'number_day' => $i,
                        'status' => 'N',
                        'color' => '#fff',
                        'bg_color' => $backgroundColor
                    ]);
                }

            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);

            array_push($arraySummary, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'cantA' => $cantA,
                'cantF' => $cantF,
                'cantS' => $cantS,
                'cantM' => $cantM,
                'cantJ' => $cantJ,
                'cantV' => $cantV,
                'cantP' => $cantP,
                'cantT' => $cantT,
                'cantH' => $cantH,
                'cantL' => $cantL,
                'cantU' => $cantU,
                'cantPH' => $cantPH,
                'cantTC' => $cantTC
            ]);
        }

        return response()->json([
            'arrayAssistances' => $arrayAssistances,
            'arrayWeekWithDays' => $arrayWeekWithDays,
            'arraySummary' => $arraySummary
        ], 200);

    }

    public function exportAssistancesMonthYear()
    {
        $year = $_GET['year'];
        $month = $_GET['month'];;

        $workers = Worker::where('enable', true)->get();

        $date = Carbon::create($year,$month,1);
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;

        $dates = 'Reporte de Asistencias del mes de ' .$nameMonth.' del año '.$yearCurrent;

        $arrayAssistances = [];

        $arraySummary = [];
        $cantA = 0;
        $cantF = 0;
        $cantS = 0;
        $cantM = 0;
        $cantJ = 0;
        $cantV = 0;
        $cantP = 0;
        $cantT = 0;
        $cantH = 0;
        $cantL = 0;
        $cantPH = 0;
        $cantTC = 0;

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];
            $cantA = 0;
            $cantF = 0;
            $cantS = 0;
            $cantM = 0;
            $cantJ = 0;
            $cantV = 0;
            $cantP = 0;
            $cantT = 0;
            $cantH = 0;
            $cantL = 0;
            $cantU = 0;
            $cantPH = 0;
            $cantTC = 0;

            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
                $assistance_detail = AssistanceDetail::whereDate('date_assistance',$fecha->format('Y-m-d'))
                    ->where('worker_id', $worker->id)
                    ->first();

                if ( !empty($assistance_detail) )
                {
                    $color = '';
                    $estado = '';
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    if ( $assistance_detail->status == 'A' )
                    {
                        $color = '#28a745';
                        $estado = 'A';
                        $cantA = $cantA + 1;
                    } elseif ( $assistance_detail->status == 'F' ) {
                        $color = '#dc3545';
                        $estado = 'F';
                        $cantF = $cantF + 1;
                    } elseif ( $assistance_detail->status == 'S' ){
                        $color = '#52585d';
                        $estado = 'S';
                        $cantS = $cantS + 1;
                    } elseif ( $assistance_detail->status == 'M' ){
                        $color = '#17a2b8';
                        $estado = 'M';
                        $cantM = $cantM + 1;
                    } elseif ( $assistance_detail->status == 'J' ){
                        $color = '#ffc107';
                        $estado = 'J';
                        $cantJ = $cantJ + 1;
                    } elseif ( $assistance_detail->status == 'V' ){
                        $color = '#f012be';
                        $estado = 'V';
                        $cantV = $cantV + 1;
                    } elseif ( $assistance_detail->status == 'P' ){
                        $color = '#007bff';
                        $estado = 'P';
                        $cantP = $cantP + 1;
                    } elseif ( $assistance_detail->status == 'T' ){
                        $color = '#6610f2';
                        $estado = 'T';
                        $cantT = $cantT + 1;
                    } elseif ( $assistance_detail->status == 'H' ){
                        $color = '#6610f2';
                        $estado = 'H';
                        $cantH = $cantH + 1;
                    } elseif ( $assistance_detail->status == 'L' ){
                        $color = '#42F1CC';
                        $estado = 'L';
                        $cantL = $cantL + 1;
                    } elseif ( $assistance_detail->status == 'U' ){
                        $color = '#c32169';
                        $estado = 'U';
                        $cantU = $cantU + 1;
                    } elseif ( $assistance_detail->status == 'PH' ){
                        $color = '#9cf210';
                        $estado = 'PH';
                        $cantPH = $cantPH + 1;
                    } elseif ( $assistance_detail->status == 'TC' ){
                        $color = '#ff851b';
                        $estado = 'TC';
                        $cantTC = $cantTC + 1;
                    } else {
                        $color = '#fff';
                        $estado = 'N';
                    }

                    array_push($arrayDayAssistances, [
                        'day' => $assistance_detail->date_assistance,
                        'number_day' => $i,
                        'status' => $estado,
                        'color' => $color,
                        'bg_color' => $backgroundColor
                    ]);
                } else {
                    $backgroundColor = ($fecha->isSunday()) ? '#F18938': '#fff';

                    array_push($arrayDayAssistances, [
                        'day' => $fecha->format('d/m/Y'),
                        'number_day' => $i,
                        'status' => 'N',
                        'color' => '#fff',
                        'bg_color' => $backgroundColor
                    ]);
                }

            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);

            array_push($arraySummary, [
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'cantA' => $cantA,
                'cantF' => $cantF,
                'cantS' => $cantS,
                'cantM' => $cantM,
                'cantJ' => $cantJ,
                'cantV' => $cantV,
                'cantP' => $cantP,
                'cantT' => $cantT,
                'cantH' => $cantH,
                'cantL' => $cantL,
                'cantU' => $cantU,
                'cantPH' => $cantPH,
                'cantTC' => $cantTC
            ]);
        }

        return (new AssistanceExcelMultipleSheets($arrayAssistances, $arraySummary, $dates))->download('reporteAsistencias.xlsx');


        /*return response()->json([
            'arrayAssistances' => $arrayAssistances,
            'arrayWeekWithDays' => $arrayWeekWithDays,
            'arraySummary' => $arraySummary
        ], 200);*/

    }

    public function store(Request $request, $id_assistance, $id_worker)
    {
        DB::beginTransaction();
        try {
            $assistance = Assistance::find($id_assistance);
            $assistanceDetail = AssistanceDetail::create([
                'date_assistance' => $assistance->date_assistance,
                'hour_entry' => ($request->get('time_entry') == '')? null: $request->get('time_entry'),
                'hour_out' => ($request->get('time_out') == '')? null: $request->get('time_out'),
                'status' => ($request->get('status') == '')? null: $request->get('status'),
                'justification' => ($request->get('status') == 'FJ')? 1: 0,
                //'obs_justification' => ($request->get('obs_justification') == '')? null: $request->get('obs_justification'),
                'worker_id' => $id_worker,
                'assistance_id' => $id_assistance,
                'hours_discount' => ($request->get('hours_discount') == '')? 0: (float)$request->get('hours_discount'),
                'working_day_id' => ($request->get('working_day') == '')? null: $request->get('working_day'),
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Asistencia guardada con éxito.', 'assistanceDetail' => $assistanceDetail], 200);

    }

    public function update(Request $request, $assistanceDetail_id)
    {
        DB::beginTransaction();
        try {
            $assistanceDetail = AssistanceDetail::find($assistanceDetail_id);
            $assistanceDetail->hour_entry = ($request->get('time_entry') == '')? null: $request->get('time_entry');
            $assistanceDetail->hour_out = ($request->get('time_out') == '')? null: $request->get('time_out');
            $assistanceDetail->status = ($request->get('status') == '')? null: $request->get('status');
            $assistanceDetail->justification = ($request->get('status') == 'FJ')? 1: 0;
            //$assistanceDetail->obs_justification = ($request->get('obs_justification') == '')? null: $request->get('obs_justification');
            $assistanceDetail->hours_discount = ($request->get('hours_discount') == '')? 0: (float)$request->get('hours_discount');
            $assistanceDetail->working_day_id = ($request->get('working_day') == '')? null: $request->get('working_day');
            $assistanceDetail->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Asistencia actualizada con éxito.', 'assistanceDetail' => $assistanceDetail], 200);

    }

    public function destroy(Request $request, $assistanceDetail_id)
    {
        DB::beginTransaction();
        try {
            $assistanceDetail = AssistanceDetail::find($assistanceDetail_id);
            $assistanceDetail->delete();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Asistencia eliminada con éxito.', 'assistanceDetail' => $assistanceDetail], 200);

    }

    public function showHourDiary()
    {
        Carbon::setLocale(config('app.locale'));
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::where('enable', true)->get();

        $date = Carbon::now('America/Lima')->locale('es');
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;
        $weekOfYear = $date->weekOfYear;

        $arrayDays = [];
        $arrayHeaders = [];

        $colors = ['#E2EFDA', '#D9E1F2', '#FF7979'];
        //dump($colors);

        for ( $i = 1; $i<=$date->daysInMonth; $i++ )
        {
            $fecha = Carbon::create($yearCurrent,$monthCurrent,$i);
            $dayName = strtoupper($fecha->locale('es_ES')->dayName);
            $monthName = strtoupper($fecha->locale('es_ES')->monthName);

            if ( $fecha->dayOfWeek == Carbon::SUNDAY )
            {
                array_push($arrayDays, [
                    'nameDay' => $dayName.' '.$i.' '.$monthName,
                    'color' => $colors[2],
                    'colspan' => 2
                ]);

                array_push($arrayHeaders, ['H. 100%','H. ESP',$colors[2],
                ]);

            } else {
                if ( $i%2 == 0 ) // Es par
                {
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[1],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[1],
                    ]);
                } else {
                    // Es impar
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[0],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[0],
                    ]);
                }

            }

        }

        $arrayAssistances = [];

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];

            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
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
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 || count($permit_hour)>0)
                        {
                            if(count($permit_hour)>0 )
                            {
                                //TODO: OBTENER LAS HORAS TRABAJADAS
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //dump('Horas Trabajadas: '. $assistance_detail->hour_out_new);
                                //TODO: OBTENER LAS HORAS NETO $hoursWorked - $permit_hour[0]->hour
                                $hoursNeto = round($hoursWorked - $permit_hour[0]->hour - $assistance_detail->hours_discount - $time_break, 2);
                                //dump('Horas de permiso por hora: '. $hoursNeto);
                                //TODO: AGREGAR AL ARRAY PUSH EN LAS HORAS ORDINARIAS
                                array_push($arrayDayAssistances, [
                                    $hoursNeto,
                                    0,
                                    0,
                                    0,
                                    0,
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }
                            else{
                                ///dump('Entré porque hay Horas especiales');
                                // TODO: Con H-ESP
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //dump('Horas Trabajadas: '. $hoursWorked);
                                //$hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                //dump('Horas Trabajadas: '. $hoursNeto);
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }

                            //dump($arrayDayAssistances);
                        } else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            // Pregunta si es sabado
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
                                //dump('Entre porqe detectamos horas extras');
                                // TODO: Detectamos horas extras
                                $wD = WorkingDay::where('enable', true)->skip(2)->take(1)->first();
                                //if ( $workingDay->id == $wD->id ) $fecha->isSunday()
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
                                //if ( $workingDay->id == $wD->id )
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
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
                                0,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        // TODO: Feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
                            ]);
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
                                $hoursOrdinary = Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start);
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
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
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }

                        }

                    } elseif ( $this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: Feriado - Domingo
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                $hoursNeto,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }
                    }

                }
                else {
                    if ( $fecha->isSunday() ) {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            $colors[2],
                        ]);
                    } else {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            0,
                            0,
                            0,
                            ($i%2!=0) ? $colors[0]: $colors[1],
                        ]);
                    }
                }
            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name .' '. $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);
            //dump($arrayAssistances);
            //dd();
        }


        //dump($arrayDays);
        //dump($arrayHeaders);
        //dump($arrayAssistances);
        /*for ( $k=0 ; $k<2 ; $k++ )
        {
            dump($arrayAssistances[$k]['worker']);
            for ( $l=0 ; $l<count($arrayAssistances[$k]['assistances']) ; $l++ )
            {
                dump($arrayAssistances[$k]['assistances'][$l]);
                for ( $m=0 ; $m<count($arrayAssistances[$k]['assistances'][$l])-1 ; $m++ )
                {
                    dump($arrayAssistances[$k]['assistances'][$l][$m]);
                }
            }
        }*/
        //dd();


        return view('assistance.hourDiary', compact( 'permissions', 'yearCurrent', 'monthCurrent', 'nameMonth', 'arrayDays', 'arrayHeaders', 'arrayAssistances'));

    }

    private function isHoliday(Carbon $fecha)
    {
        $holiday = Holiday::whereDate('date_complete', '=',$fecha->format('Y-m-d'))->first();
        return ( !empty($holiday) ) ? true:false ;
    }

    public function getHourDiaryMonthYear( $month, $year )
    {
        $workers = Worker::where('enable', true)->get();

        $date = Carbon::create($year,$month,1);
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;

        $arrayDays = [];
        $arrayHeaders = [];

        $colors = ['#E2EFDA', '#D9E1F2', '#FF7979'];
        //dump($colors);

        for ( $i = 1; $i<=$date->daysInMonth; $i++ )
        {
            $fecha = Carbon::create($yearCurrent,$monthCurrent,$i);
            $dayName = strtoupper($fecha->locale('es_ES')->dayName);
            $monthName = strtoupper($fecha->locale('es_ES')->monthName);

            if ( $fecha->dayOfWeek == Carbon::SUNDAY )
            {
                array_push($arrayDays, [
                    'nameDay' => $dayName.' '.$i.' '.$monthName,
                    'color' => $colors[2],
                    'colspan' => 2
                ]);

                array_push($arrayHeaders, ['H. 100%','H. ESP',$colors[2],
                ]);

            } else {
                if ( $i%2 == 0 ) // Es par
                {
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[1],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[1],
                    ]);
                } else {
                    // Es impar
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[0],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[0],
                    ]);
                }

            }

        }

        $arrayAssistances = [];

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];

            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
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
                    $permit_hour = PermitHour::whereDate('date_start', '=',$fecha->format('Y-m-d'))
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
                        if ( count($medicalRests)>0 /*|| count($vacations)>0*/ || count($licenses)>0 || count($permit_hour)>0 )
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }
                            else{
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }
                            //dump($arrayDayAssistances);
                        } else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            //dump('Horas Trabajadas: ' . $hoursWorked);
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                if ($wD && $workingDay->id == $wD->id) {
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
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
                                0,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        // TODO: Feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
                            ]);
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
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
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
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }

                        }

                    } elseif ( $this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: Feriado - Domingo
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                $hoursNeto,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }
                    }

                }
                else {
                    if ( $fecha->isSunday() ) {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            $colors[2],
                        ]);
                    } else {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            0,
                            0,
                            0,
                            ($i%2!=0) ? $colors[0]: $colors[1],
                        ]);
                    }
                }
            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name .' '. $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);
            //dump($arrayAssistances);
            //dd();
        }

        return response()->json([
            'arrayDays' => $arrayDays,
            'arrayHeaders' => $arrayHeaders,
            'arrayAssistances' => $arrayAssistances
        ], 200);

    }

    public function exportHourDiaryMonthYear()
    {
        $year = $_GET['year'];
        $month = $_GET['month'];

        $workers = Worker::where('enable', true)->get();

        $date = Carbon::create($year,$month,1);
        $yearCurrent = $date->year;
        $monthCurrent = $date->month;
        $nameMonth = $date->monthName;

        $arrayDays = [];
        $arrayHeaders = [];

        $colors = ['#E2EFDA', '#D9E1F2', '#FF7979'];
        //dump($colors);

        for ( $i = 1; $i<=$date->daysInMonth; $i++ )
        {
            $fecha = Carbon::create($yearCurrent,$monthCurrent,$i);
            $dayName = strtoupper($fecha->locale('es_ES')->dayName);
            $monthName = strtoupper($fecha->locale('es_ES')->monthName);

            if ( $fecha->dayOfWeek == Carbon::SUNDAY )
            {
                array_push($arrayDays, [
                    'nameDay' => $dayName.' '.$i.' '.$monthName,
                    'color' => $colors[2],
                    'colspan' => 2
                ]);

                array_push($arrayHeaders, ['H. 100%','H. ESP',$colors[2],
                ]);

            } else {
                if ( $i%2 == 0 ) // Es par
                {
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[1],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[1],
                    ]);
                } else {
                    // Es impar
                    array_push($arrayDays, [
                        'nameDay' => $dayName.' '.$i.' '.$monthName,
                        'color' => $colors[0],
                        'colspan' => 5
                    ]);
                    array_push($arrayHeaders, ['H. ORD','H. 25%','H. 35%','H. 100%','H. ESP',$colors[0],
                    ]);
                }

            }

        }

        $arrayAssistances = [];

        foreach ( $workers as $worker)
        {
            $arrayDayAssistances = [];

            for ( $i = 1; $i<=$date->daysInMonth; $i++ )
            {
                $fecha = Carbon::create($yearCurrent, $monthCurrent, $i);
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
                        if ( count($medicalRests)>0 /*|| count($vacations)>0*/ || count($licenses)>0 || count($permit_hour)>0 )
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }
                            else{
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }
                            //dump($arrayDayAssistances);
                        }  else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            //dump('Horas Trabajadas: ' . $hoursWorked);
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                if ($wD && $workingDay->id == $wD->id) {
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
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
                                0,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }

                    } elseif ( $this->isHoliday($fecha) && !$fecha->isSunday() ) {
                        // TODO: Feriado - Dia Normal (L-S)
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
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
                                ($i%2!=0) ? $colors[0]: $colors[1],
                            ]);
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
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            } elseif ( $assistance_detail->status == 'H' ) {
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
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
                                //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                $hoursOrdinary = 0;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                    ($i%2!=0) ? $colors[0]: $colors[1],
                                ]);
                            }

                        }

                    } elseif ( $this->isHoliday($fecha) && $fecha->isSunday() ) {
                        // TODO: Feriado - Domingo
                        if ( count($medicalRests)>0 || count($vacations)>0 || count($licenses)>0 )
                        {
                            // TODO: Con H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            array_push($arrayDayAssistances, [
                                $hoursNeto,
                                $hoursNeto,
                                $colors[2],
                            ]);
                        } else {
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                            $hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);

                            array_push($arrayDayAssistances, [
                                $hoursTotals,
                                0,
                                $colors[2],
                            ]);
                        }
                    }

                }
                else {
                    if ( $fecha->isSunday() ) {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            $colors[2],
                        ]);
                    } else {
                        array_push($arrayDayAssistances, [
                            0,
                            0,
                            0,
                            0,
                            0,
                            ($i%2!=0) ? $colors[0]: $colors[1],
                        ]);
                    }
                }
            }

            array_push($arrayAssistances, [
                'worker' => $worker->first_name .' '. $worker->last_name,
                'assistances' => $arrayDayAssistances
            ]);
            //dump($arrayAssistances);
            //dd();
        }

        $dates = 'Reporte de Horas Diarias del mes de ' .$nameMonth.' del año '.$yearCurrent;

        return (new HoursDiaryExcelExport($arrayDays, $arrayHeaders, $arrayAssistances, $dates))->download('reporteHorasDiarias.xlsx');


        /*return response()->json([
            'arrayDays' => $arrayDays,
            'arrayHeaders' => $arrayHeaders,
            'arrayAssistances' => $arrayAssistances
        ], 200);*/
    }

    public function getTotalHoursByWorker($worker_id)
    {
        $start = $_GET['start'];
        $end = $_GET['end'];
        /*$start = '01/01/2022';
        $end = '31/01/2022';*/

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
                    ->whereNotIn('status', ['S', 'F', 'P','U', 'TC'])
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
                            }
                            else{
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            //dump($assistance_detail->hour_out_new > $workingDay->time_fin);
                            if ( $assistance_detail->hour_out_new > $workingDay->time_fin ){
                                //dump($assistance_detail->hour_out_new);
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
                                //dd('Fin');
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

                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $assistance_detail->hours_discount;

                                /*if ( $workingDay->id == $wD->id )
                                {

                                    //$hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $assistance_detail->hours_discount ;
                                } else {
                                    $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($assistance_detail->hour_entry)) - $time_break - $assistance_detail->hours_discount ;
                                }*/

                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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

            //dump($arrayByDates);

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
        else {

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
                            }
                            else{
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
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
        }

        //dump($arrayByWeek);
        //dump($arrayByDates);
        //dd();

        return json_encode([
            'arrayByWeek' =>$arrayByWeek,
            'arrayByDates'=>$arrayByDates
        ]);

    }

    public function exportTotalHoursWorker()
    {
        $start = $_GET['start'];
        $end = $_GET['end'];
        $worker_id = $_GET['worker'];

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
                            }
                            else{
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                    if ( $arrayByDates[$i]['week'] != $arrayByDates[$i+1]['week'] )
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

            $title = 'Reporte de Total Horas de ' . $worker->first_name . ' ' . $worker->last_name . ' desde ' .$start.' hasta '.$end;

            return (new TotalHoursExcelMultipleSheet($arrayByWeek, $arrayByDates, $title))->download('reporteTotalHoras.xlsx');

        } else {

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
                    ->whereNotIn('status', ['S', 'F', 'P','U', 'TC'])
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
                            }
                            else{
                                ///dump('Entré porque hay Horas especiales');
                                // TODO: Con H-ESP
                                $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
                                //dump('Horas Trabajadas: '. $hoursWorked);
                                //$hoursNeto = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                                //dump('Horas Trabajadas: '. $hoursNeto);
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
                            //dump($arrayDayAssistances);
                        } else {
                            //dump('Entré porque no hay Horas especiales');
                            // TODO: Sin H-ESP
                            $hoursWorked = Carbon::parse($assistance_detail->hour_out_new)->floatDiffInHours($assistance_detail->hour_entry);
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
                            //$hoursTotals = round($hoursWorked - $assistance_detail->hours_discount - $time_break, 2);
                            //dump('Horas Totales: ' . $hoursTotals);
                            $hoursOrdinary = 0;
                            $hours25 = 0;
                            $hours35 = 0;
                            $hours100 = 0;
                            if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                $hoursOrdinary = (Carbon::parse($workingDay->time_fin)->floatDiffInHours($workingDay->time_start)) - $time_break;
                                $hours100 = 0;
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                                if ( $assistance_detail->hour_out_new> $workingDay->time_fin ){
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
                    if ( $arrayByDates[$i]['week'] != $arrayByDates[$i+1]['week'] )
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

            $title = 'Reporte de Total Horas de ' . $worker->first_name . ' ' . $worker->last_name . ' durante todo el año.';

            return (new TotalHoursExcelMultipleSheet($arrayByWeek, $arrayByDates, $title))->download('reporteTotalHoras.xlsx');

        }

        //dump($arrayByWeek);
        //dd();

        /*return json_encode([
            'arrayByWeek' =>$arrayByWeek,
            'arrayByDates'=>$arrayByDates
        ]);*/

    }

    public function showTotalHours()
    {
        Carbon::setLocale(config('app.locale'));
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $workers = Worker::where('enable', true)->get();

        return view('assistance.totalHours', compact( 'permissions', 'workers'));

    }

    public function showTotalPays()
    {
        Carbon::setLocale(config('app.locale'));

        $fechaActual = Carbon::now('America/Lima');

        $currentYear = $fechaActual->year;
        $currentWeek = $fechaActual->week;

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $years = DateDimension::distinct()->get(['year']);

        $semanas = DateDimension::distinct()
            ->where('year', $currentYear)
            ->orderBy('week')
            ->get(['week']);

        $weeks = [];

        foreach ( $semanas as $semana )
        {
            $fechaInicioSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->startOfWeek();
            $fechaFinSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->endOfWeek();
            $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m');
            $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m');

            array_push($weeks, [
                "week" => $semana->week,
                "dateStart" => $fechaInicioSemanaFormateada,
                "dateEnd" => $fechaFinSemanaFormateada
            ]);
        }

        return view('assistance.totalPays', compact( 'permissions', 'years', 'weeks', 'currentYear', 'currentWeek'));

    }

    public function showTotalPaysAccounts()
    {
        Carbon::setLocale(config('app.locale'));

        $fechaActual = Carbon::now('America/Lima');

        $currentYear = $fechaActual->year;
        $currentWeek = $fechaActual->week;

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $years = DateDimension::distinct()->get(['year']);

        $semanas = DateDimension::distinct()
            ->where('year', $currentYear)
            ->orderBy('week')
            ->get(['week']);

        $weeks = [];

        foreach ( $semanas as $semana )
        {
            $fechaInicioSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->startOfWeek();
            $fechaFinSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->endOfWeek();
            $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m');
            $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m');

            array_push($weeks, [
                "week" => $semana->week,
                "dateStart" => $fechaInicioSemanaFormateada,
                "dateEnd" => $fechaFinSemanaFormateada
            ]);
        }

        return view('assistance.totalPaysAccounts', compact( 'permissions', 'years', 'weeks', 'currentYear', 'currentWeek'));

    }

    public function exportTotalPaysAccounts(){

        $year = $_GET['year'];
        $weekStart = $_GET['weekStart'];
        $weekEnd = $_GET['weekEnd'];

        if ( $weekStart != '' || $weekEnd != '' )
        {
            // TODO: Hay semanas especificadas
            $weeks = [];
            for ( $i=$weekStart; $i<=$weekEnd; $i++ )
            {
                $boletas = PaySlip::where('year', $year)
                    ->where('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;

                foreach ( $boletas as $boleta )
                {
                    $textAccounts = "";
                    $accounts = WorkerAccount::where('worker_id', $boleta->codigo)->get();
                    $accountsArray = $accounts->toArray();

                    foreach ($accountsArray as $key => $account) {
                        $num=$key+1;
                        $textAccounts .= "Cta.".$num.": ".$account['number_account'];
                        if ($key !== array_key_last($accountsArray)) {
                            $textAccounts .= "<br>";
                        }
                    }

                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "cuentas" => $textAccounts,
                        "monto" => round($boleta->totalNetoPagar, 2)
                    ]);
                    $total = $total + round($boleta->totalNetoPagar, 2);
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "cuentas" => "",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }


        } else {
            // TODO: Hay semanas especificadas
            $currentWeek = Carbon::now()->weekOfYear;
            $weeks = [];
            for ( $i=1; $i<=$currentWeek; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->whereIn('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;

                foreach ( $boletas as $boleta )
                {
                    $textAccounts = "";
                    $accounts = WorkerAccount::where('worker_id', $boleta->codigo)->get();
                    $accountsArray = $accounts->toArray();

                    foreach ($accountsArray as $key => $account) {
                        $num=$key+1;
                        $textAccounts .= "Cta.".$num.": ".$account['number_account'];

                        if ($key !== array_key_last($accountsArray)) {
                            $textAccounts .= "<br>";
                        }
                    }

                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "cuentas" => $textAccounts,
                        "monto" => $boleta->totalNetoPagar
                    ]);
                    $total = $total + $boleta->totalNetoPagar;
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "cuentas" => "",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }
        }

        return (new TotalPaysAccountsExcelMultipleSheet($weeks))->download('reporteTotalPagarCuentas.xlsx');

    }

    public function getWeeksTotalPaysByYear( $year )
    {
        $weeks = [];
        $semanas = DateDimension::distinct()->get(['week']);
        foreach ( $semanas as $semana )
        {
            $fechaInicioSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana->week)->startOfWeek();
            $fechaFinSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana->week)->endOfWeek();
            $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m');
            $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m');

            array_push($weeks, [
                "week" => $semana->week,
                "dateStart" => $fechaInicioSemanaFormateada,
                "dateEnd" => $fechaFinSemanaFormateada
            ]);
        }

        return $weeks;
    }

    public function getTotalPaysByYearWeek()
    {
        $year = $_GET['year'];
        $weekStart = $_GET['weekStart'];
        $weekEnd = $_GET['weekEnd'];

        if ( $weekStart != '' || $weekEnd != '' )
        {
            // TODO: Hay semanas especificadas
            $weeks = [];
            for ( $i=$weekStart; $i<=$weekEnd; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->where('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;
                foreach ( $boletas as $boleta )
                {
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "monto" => round($boleta->totalNetoPagar, 2)
                    ]);
                    $total = $total + round($boleta->totalNetoPagar, 2);
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }


        } else {
            // TODO: Hay semanas especificadas
            $currentWeek = Carbon::now()->weekOfYear;
            $weeks = [];
            for ( $i=1; $i<=$currentWeek; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->whereIn('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;
                foreach ( $boletas as $boleta )
                {
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "monto" => $boleta->totalNetoPagar
                    ]);
                    $total = $total + $boleta->totalNetoPagar;
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }
        }

        return response()->json([ "weeks" => $weeks ]);
    }

    public function getTotalPaysAccountsByYearWeek()
    {
        $year = $_GET['year'];
        $weekStart = $_GET['weekStart'];
        $weekEnd = $_GET['weekEnd'];

        if ( $weekStart != '' || $weekEnd != '' )
        {
            // TODO: Hay semanas especificadas
            $weeks = [];
            for ( $i=$weekStart; $i<=$weekEnd; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->where('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;

                foreach ( $boletas as $boleta )
                {
                    $textAccounts = "";
                    $accounts = WorkerAccount::where('worker_id', $boleta->codigo)->get();
                    foreach ( $accounts as $account )
                    {
                        $textAccounts = $textAccounts . $account->number_account . "<br>";
                    }
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "cuentas" => $textAccounts,
                        "monto" => round($boleta->totalNetoPagar, 2)
                    ]);
                    $total = $total + round($boleta->totalNetoPagar, 2);
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "cuentas" => "",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }


        } else {
            // TODO: Hay semanas especificadas
            $currentWeek = Carbon::now()->weekOfYear;
            $weeks = [];
            for ( $i=1; $i<=$currentWeek; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->whereIn('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;

                foreach ( $boletas as $boleta )
                {
                    $textAccounts = "";
                    $accounts = WorkerAccount::where('worker_id', $boleta->codigo)->get();

                    foreach ( $accounts as $account )
                    {
                        $textAccounts = $textAccounts . $account->number_account . "<br>";
                    }
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "cuentas" => $textAccounts,
                        "monto" => $boleta->totalNetoPagar
                    ]);
                    $total = $total + $boleta->totalNetoPagar;
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "cuentas" => "",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }
        }

        return response()->json([ "weeks" => $weeks ]);
    }

    public function getTitleWeek($year, $semana)
    {
        $fechaInicioSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana)->startOfWeek();
        $fechaFinSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana)->endOfWeek();
        $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m/Y');
        $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m/Y');

        $week = ($semana<10) ? '0'.$semana:$semana;

        return "SEMANA ".$week." DEL ".$fechaInicioSemanaFormateada. " AL ". $fechaFinSemanaFormateada;
    }

    public function showTotalBruto()
    {
        Carbon::setLocale(config('app.locale'));

        $fechaActual = Carbon::now('America/Lima');

        $currentYear = $fechaActual->year;
        $currentWeek = $fechaActual->week;

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $years = DateDimension::distinct()->get(['year']);

        $semanas = DateDimension::distinct()
            ->where('year', $currentYear)
            ->orderBy('week')
            ->get(['week']);

        $weeks = [];

        foreach ( $semanas as $semana )
        {
            $fechaInicioSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->startOfWeek();
            $fechaFinSemana = Carbon::now()->setISODate(Carbon::now()->year, $semana->week)->endOfWeek();
            $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m');
            $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m');

            array_push($weeks, [
                "week" => $semana->week,
                "dateStart" => $fechaInicioSemanaFormateada,
                "dateEnd" => $fechaFinSemanaFormateada
            ]);
        }

        return view('assistance.totalBruto', compact( 'permissions', 'years', 'weeks', 'currentYear', 'currentWeek'));

    }

    public function getWeeksTotalBrutoByYear( $year )
    {
        $weeks = [];
        $semanas = DateDimension::distinct()->get(['week']);
        foreach ( $semanas as $semana )
        {
            $fechaInicioSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana->week)->startOfWeek();
            $fechaFinSemana = Carbon::createFromDate($year, 1, 1)->setISODate($year, $semana->week)->endOfWeek();
            $fechaInicioSemanaFormateada = $fechaInicioSemana->format('d/m');
            $fechaFinSemanaFormateada = $fechaFinSemana->format('d/m');

            array_push($weeks, [
                "week" => $semana->week,
                "dateStart" => $fechaInicioSemanaFormateada,
                "dateEnd" => $fechaFinSemanaFormateada
            ]);
        }

        return $weeks;
    }

    public function getTotalBrutoByYearWeek()
    {
        $year = $_GET['year'];
        $weekStart = $_GET['weekStart'];
        $weekEnd = $_GET['weekEnd'];

        if ( $weekStart != '' || $weekEnd != '' )
        {
            // TODO: Hay semanas especificadas
            $weeks = [];
            for ( $i=$weekStart; $i<=$weekEnd; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->where('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;
                foreach ( $boletas as $boleta )
                {
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "monto" => round($boleta->totalIngresos+$boleta->totalDescuentos,2)
                    ]);
                    $total = $total + round($boleta->totalIngresos+$boleta->totalDescuentos,2);
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }


        } else {
            // TODO: Hay semanas especificadas
            $currentWeek = Carbon::now()->weekOfYear;
            $weeks = [];
            for ( $i=1; $i<=$currentWeek; $i++ )
            {
                //array_push($weeks, $i);
                // Boletas que pertenecen a ese año y semana
                $boletas = PaySlip::where('year', $year)
                    ->whereIn('semana', $i)
                    ->orderBy('semana', 'desc')
                    ->orderBy('codigo', 'asc')
                    ->get();
                $paySlips = [];
                $total=0;
                foreach ( $boletas as $boleta )
                {
                    array_push($paySlips, [
                        "codigo" => $boleta->codigo,
                        "trabajador" => $boleta->nombre,
                        "monto" => $boleta->totalIngresos+$boleta->totalDescuentos
                    ]);
                    $total = $total + $boleta->totalIngresos+$boleta->totalDescuentos;
                }

                array_push($paySlips, [
                    "codigo" => "<strong>#</strong>",
                    "trabajador" => "<strong>Suma total</strong>",
                    "monto" => "<strong>".$total."</strong>"
                ]);

                array_push($weeks, [
                    "week" => $i,
                    "year" => $year,
                    "title" => $this->getTitleWeek($year, $i),
                    "boletas" => $paySlips
                ]);
            }
        }

        return response()->json([ "weeks" => $weeks ]);
    }
}
