<?php

namespace App\Http\Controllers;

use App\AreaWorker;
use App\CivilStatus;
use App\Contract;
use App\EmergencyContact;
use App\Exports\WorkersInfoExport;
use App\FinishContract;
use App\Http\Requests\FinishContractDeleteRequest;
use App\Http\Requests\FinishContractStoreRequest;
use App\PensionSystem;
use App\PercentageWorker;
use App\Relationship;
use App\User;
use App\Work;
use App\Worker;
use App\WorkFunction;
use App\WorkingDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkerController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('worker.index', compact('permissions'));
    }

    public function exportWorkers()
    {
        $filtros = $_GET['filtros'];

        // Inicializar la consulta con el modelo Worker
        $query = Worker::query();

        // Filtrar los filtros para incluir solo las columnas existentes en la tabla
        $filtros = array_filter($filtros, function ($filtro) {
            // Verificar si la columna existe en la tabla workers
            return Schema::hasColumn('workers', $filtro);
        });

        // Verificar si "work_function_id" está presente en los filtros y cargar la relación correspondiente
        if (in_array('civil_status_id', $filtros)) {
            $query->with('civil_status');
        }

        if (in_array('pension_system_id', $filtros)) {
            $query->with('pension_system');
        }

        if (in_array('area_worker_id', $filtros)) {
            $query->with('area_worker');
        }

        // Seleccionar las columnas indicadas en el array $filtros
        $query->select($filtros);

        // Obtener los resultados de la consulta
        $workers = $query->get();

        // Mapear nombres de columnas originales a nombres modificados
        $columnasModificadas = [
            "dni"                       => "DNI",
            "first_name"                => "NOMBRES",
            "last_name"                 => "APELLIDOS",
            "personal_address"          => "DIRECCIÓN",
            "phone"                     => "TELÉFONO",
            "email"                     => "EMAIL",
            "work_function_id"          => "CARGO",
            "gender"                    => "GÉNERO",
            "birthplace"                => "FECHA NACIMIENTO",
            "level_school"              => "NIVEL ESTUDIOS",
            "num_children"              => "N° HIJOS",
            "admission_date"            => "FECHA INGRESO",
            "termination_date"          => "FECHA DE CESE",
            "daily_salary"              => "SALARIO DIARIO",
            "monthly_salary"            => "SALARIO MENSUAL",
            "pension"                   => "PENSION DE ALIMENTO",
            "essalud"                   => "ESSALUD",
            "assign_family"             => "ASIGNACIÓN FAMILIAR",
            "five_category"             => "QUINTA CATEGORÍA",
            "civil_status_id"           => "ESTADO CIVIL",
            "pension_system_id"         => "SISTEMA PENSION",
            "percentage_pension_system" => "PORC. SIST. PENSION",
            "observation"               => "OBSERVACIÓN",
            "area_worker_id"            => "AREA",
            "profession"                => "PROFESIÓN",
            "reason_for_termination"    => "MOTIVO DE CESE"
            // Agrega más columnas según sea necesario
        ];

        // Estructurar datos en un array asociativo
        $datos = [];

        // Agregar encabezados de columna modificados al array
        $encabezados = [];
        foreach ($filtros as $filtro) {
            $encabezados[] = $columnasModificadas[$filtro] ?? $filtro;
        }
        $datos[] = $encabezados;

        // Agregar datos al array
        foreach ($workers as $worker) {
            $fila = [];
            foreach ($filtros as $filtro) {
                // Obtener el valor de la columna o de la relación
                $valor = ($worker->$filtro ?? '');

                // Personalizar la obtención de datos de las relaciones y manejar casos especiales
                switch ($filtro) {
                    case 'work_function_id':
                        $valor = optional($worker->work_function)->description ?? '';
                        break;
                    case 'civil_status_id':
                        $valor = optional($worker->civil_status)->description ?? '';
                        break;
                    case 'pension_system_id':
                        $pensionSystem = optional($worker->pension_system);
                        $valor = $pensionSystem->description ?? $pensionSystem->percentage;
                        break;
                    case 'percentage_pension_system':
                        $valor = $worker->percentage_pension_system ?? optional($worker->pension_system)->percentage ?? '';
                        break;
                    case 'area_worker_id':
                        $valor = optional($worker->area_worker)->name ?? '';
                        break;
                    // Agrega más casos según sea necesario
                    default:
                        // Mantén el valor original si no es una relación especial
                }

                $fila[] = $valor;
            }
            $datos[] = $fila;
        }

        // Hacer lo que quieras con los resultados
        //dd($datos);
        return (new WorkersInfoExport($datos))->download('trabajadores.xlsx');

    }

    public function getWorkers()
    {
        $workers = Worker::where('enable', 1)->get();
        $arrayWorkers = [];

        foreach ( $workers as $worker )
        {
            $haveContract = false;
            $canFinishContract = false;
            $canFinishContractEdit = false;

            $contract = DB::table('contracts')->where('worker_id', $worker->id)->latest('updated_at')->first();
            $haveContract = ($contract != null) ? true:false ;

            if ( $haveContract )
            {
                $last_finish_contract = DB::table('finish_contracts')->where('worker_id', $worker->id)->where('contract_id', $contract->id)->latest('updated_at')->first();
                if ( !isset($last_finish_contract) )
                {
                    // TODO: No existe un termino de contrato
                    $canFinishContract = true;
                    $canFinishContractEdit = false;
                } else {
                    // TODO: Existe un termino de contrato
                    if ( $last_finish_contract->active == 1 )
                    {
                        // TODO: Existe un termino de contrato activo
                        $canFinishContract = false;
                        $canFinishContractEdit = true;
                    } elseif ( $last_finish_contract->active == 0 ) {
                        // TODO: Existe un termino de contrato inactivo
                        $canFinishContract = false;
                        $canFinishContractEdit = false;
                    }
                }
            } else {
                // TODO: Si no hay contratos
                $canFinishContract = false;
                $canFinishContractEdit = false;
            }

            /*if (isset($last_finish_contract))
            {
                if ($last_finish_contract->active == 1)
                {
                    $haveFinishContract = true;
                    $haveFinishContractEdit = true;
                } else {
                    $haveFinishContract = false;
                }
            } else {
                if ($haveContract)
                {
                    $haveFinishContract = false;
                } else {
                    $haveFinishContract = false;
                }

            }*/
            //$haveFinishContract = ($finish_contract != null) ? true:false ;
            array_push( $arrayWorkers, [
                'id' => $worker->id,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'personal_address' => $worker->personal_address,
                'birthplace' => ($worker->birthplace == null) ? '':$worker->birthplace->format('d/m/Y'),
                'age' => ($worker->birthplace == null) ? '': Carbon::parse($worker->birthplace)->age,
                'phone' => $worker->phone,
                'email' => $worker->email,
                'level_school' => $worker->level_school,
                'profession' => $worker->profession,
                'reason_for_termination' => $worker->reason_for_termination,
                'image' => $worker->image,
                'dni' => $worker->dni,
                'admission_date' => ($worker->admission_date == null) ? '': $worker->admission_date->format('d/m/Y'),
                'num_children' => $worker->num_children,
                'daily_salary' => $worker->daily_salary,
                'monthly_salary' => $worker->monthly_salary,
                'pension' => ($worker->pension == null || $worker->pension == "") ? "":$worker->pension,
                'gender' => $worker->gender,
                'essalud' => $worker->essalud,
                'assign_family' => $worker->assign_family,
                'five_category' => ($worker->five_category == 1) ? 'SI':'NO',
                'termination_date' => ($worker->termination_date == null) ? '':$worker->termination_date->format('d/m/Y'),
                'observation' => $worker->observation,
                'contract' => ($worker->contract_id == null) ? '': $worker->contract->code,
                'civil_status' => ($worker->civil_status_id == null) ? '': $worker->civil_status->description,
                'work_function' => ($worker->work_function_id == null) ? '': $worker->work_function->description,
                'pension_system' => ($worker->pension_system_id == null) ? '': $worker->pension_system->description,
                'percentage_pension_system' => ($worker->percentage_pension_system == null || $worker->percentage_pension_system == 0) ? '':$worker->percentage_pension_system,
                'area_worker' => ($worker->area_worker_id == null) ? '': $worker->area_worker->name,
                'have_contract' => $haveContract,
                'canFinishContract' => $canFinishContract,
                'canFinishContractEdit' => $canFinishContractEdit
            ] );
        }

        return datatables($arrayWorkers)->toJson();
    }

    public function getWorkersBoleta()
    {
        $workers = Worker::where('enable', 1)
            ->orderBy('last_name')->get();
        $arrayWorkers = [];

        foreach ( $workers as $worker )
        {
            array_push( $arrayWorkers, [
                'id' => $worker->id,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'dni' => $worker->dni,
                'work_function' => ($worker->work_function_id == null) ? '': $worker->work_function->description,
                'area_worker' => ($worker->area_worker_id == null) ? '': $worker->area_worker->name,
            ] );
        }

        return datatables($arrayWorkers)->toJson();
    }

    public function create()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $sueldo = PercentageWorker::where('name', 'rmv')->first();
        $essalud = PercentageWorker::where('name', 'essalud')->first();
        $assign_family = PercentageWorker::where('name', 'assign_family')->first();

        $value_assign_family = round((float)$sueldo->value/(float)$assign_family->value, 2);
        $value_essalud = $essalud->value;

        $civil_statuses = CivilStatus::select('id', 'description')->get();
        $work_functions = WorkFunction::select('id', 'description')->get();
        $pension_systems = PensionSystem::select('id', 'description', 'percentage')->get();
        $relationships = Relationship::select('id', 'description')->get();
        //$working_days = WorkingDay::select('id', 'description')->where('enable', 1)->get();

        $areaWorkers = AreaWorker::select('id', 'name')->get();

        return view('worker.create', compact('value_essalud','value_assign_family','permissions','civil_statuses', 'work_functions', 'pension_systems', 'relationships', 'areaWorkers'));
    }

    public function store(Request $request)
    {
        //dd($request);

        DB::beginTransaction();
        try {
            $sueldo = PercentageWorker::where('name', 'rmv')->first();
            $essalud = PercentageWorker::where('name', 'essalud')->first();
            $assign_family = PercentageWorker::where('name', 'assign_family')->first();

            $value_assign_family = round((float)$sueldo->value/(float)$assign_family->value, 2);
            $value_essalud = $essalud->value;

            // Creamos el email con el formato mapellido@sermeind.com
            $nombres = $request->get('first_name');
            $apellidos = $request->get('last_name');

            $primeraLetraNombres = strtolower($this->eliminar_tildes(substr($nombres,0,1)));
            $pos = strpos($apellidos, ' ');

            $primerApellido = '';

            if ( $pos !== false )
            {
                $primerApellido = strtolower($this->eliminar_tildes(substr($apellidos,0,$pos)));
            } else {
                $primerApellido = strtolower($apellidos);
            }

            // Creamos al usuario
            $user = User::create([
                'name' => $request->get('first_name').' '.$request->get('last_name'),
                'email' => $primeraLetraNombres.$primerApellido.'@venti360.com',
                'password' => bcrypt('venti3602025'),
                'image' => 'no_image.png'
            ]);

            $user->assignRole('worker');

            // Creamos el trabajador
            $worker = Worker::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'personal_address' => $request->get('personal_address'),
                'birthplace' => ($request->get('birthplace') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('birthplace')) : null,
                'phone' => $request->get('phone'),
                'email' => ($request->get('email') == '' || $request->get('email') == null ) ? $primeraLetraNombres.$primerApellido.'@erp.com' : $request->get('email') ,
                'level_school' => $request->get('level_school'),
                'reason_for_termination' => $request->get('reason_for_termination'),
                'profession' => $request->get('profession'),
                'image' => 'no_image.png',
                'dni' => $request->get('dni'),
                'admission_date' => ($request->get('admission_date') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('admission_date')) : null,
                'num_children'=> $request->get('num_children'),
                'daily_salary' => $request->get('daily_salary'),
                'monthly_salary' => $request->get('monthly_salary'),
                'pension' => $request->get('pension'),
                'gender' => $request->get('gender'),
                'essalud' => $value_essalud,
                'assign_family' => ($request->get('num_children') > 0) ? round($value_assign_family/30,2):0 ,
                //'five_category' => $request->get('five_category'),
                'termination_date' => ($request->get('termination_date') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('termination_date')) : null,
                'observation' => $request->get('observation'),
                'user_id' => $user->id,
                'civil_status_id' => ($request->get('civil_status') == 0) ? null: $request->get('civil_status'),
                'work_function_id' => ($request->get('work_function') == 0) ? null: $request->get('work_function'),
                'pension_system_id' => ($request->get('pension_system') == 0) ? null: $request->get('pension_system'),
                //'working_day_id' => ($request->get('working_day') == 0) ? null: $request->get('working_day'),
                'area_worker_id' => ($request->get('area_worker') == 0) ? null: $request->get('area_worker'),
                'percentage_pension_system' => ($request->get('percentage_pension_system') == 0 || $request->get('percentage_pension_system') == "") ? null: $request->get('percentage_pension_system'),
            ]);

            // Creacion de los contactos de emergencia
            $names = $request->get('contacts');
            $relations = $request->get('relations');
            $phones = $request->get('phones');

            if ( $names != null )
            {
                for( $i=0; $i<count($phones); $i++ )
                {
                    $emergencyContact = EmergencyContact::create([
                        'name' => $names[$i],
                        'relationship_id' => $relations[$i],
                        'worker_id' => $worker->id,
                        'phone' => $phones[$i]
                    ]);
                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            //dump($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Colaborador registrado con éxito.'], 200);

    }

    public function eliminar_tildes($cadena){

        $originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
        $modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby';
        $cadena = utf8_decode($cadena);

        $cadena = strtr($cadena, utf8_decode($originales), $modificadas);

        return utf8_encode($cadena);
    }

    public function show(Worker $worker)
    {
        //
    }

    public function edit($id)
    {
        $worker = Worker::with('emergency_contacts')->find($id);

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $sueldo = PercentageWorker::where('name', 'rmv')->first();
        $essalud = PercentageWorker::where('name', 'essalud')->first();
        $assign_family = PercentageWorker::where('name', 'assign_family')->first();

        $value_assign_family = round((float)$sueldo->value/(float)$assign_family->value, 2);
        $value_essalud = $essalud->value;

        $civil_statuses = CivilStatus::select('id', 'description')->get();
        $work_functions = WorkFunction::select('id', 'description')->get();
        $pension_systems = PensionSystem::select('id', 'description', 'percentage')->get();
        $relationships = Relationship::select('id', 'description')->get();
        //$working_days = WorkingDay::select('id', 'description')->where('enable', 1)->get();

        $areaWorkers = AreaWorker::select('id', 'name')->get();

        return view('worker.edit', compact('value_essalud','value_assign_family','permissions','civil_statuses', 'work_functions', 'pension_systems', 'relationships', 'worker', 'areaWorkers'));

    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $sueldo = PercentageWorker::where('name', 'rmv')->first();
            $essalud = PercentageWorker::where('name', 'essalud')->first();
            $assign_family = PercentageWorker::where('name', 'assign_family')->first();

            $value_assign_family = round((float)$sueldo->value/(float)$assign_family->value, 2);
            $value_essalud = $essalud->value;

            // Creamos el email con el formato mapellido@sermeind.com
            $nombres = $request->get('first_name');
            $apellidos = $request->get('last_name');

            $primeraLetraNombres = strtolower($this->eliminar_tildes(substr($nombres,0,1)));
            $pos = strpos($apellidos, ' ');

            $primerApellido = '';

            if ( $pos !== false )
            {
                $primerApellido = strtolower($this->eliminar_tildes(substr($apellidos,0,$pos)));
            } else {
                $primerApellido = strtolower($apellidos);
            }
            $worker = Worker::find($id);

            $user = User::find($worker->user_id);
            $user->email = $primeraLetraNombres.$primerApellido.'@erp.com';
            $user->save();
            // Modificamos el trabajador

            $worker->first_name = $request->get('first_name');
            $worker->last_name = $request->get('last_name');
            $worker->personal_address = $request->get('personal_address');
            $worker->birthplace = ($request->get('birthplace') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('birthplace')) : null;
            $worker->phone = $request->get('phone');
            $worker->email = ($request->get('email') == '' || $request->get('email') == null ) ? $primeraLetraNombres.$primerApellido.'@erp.com' : $request->get('email') ;
            $worker->level_school = $request->get('level_school');
            $worker->profession = $request->get('profession');
            $worker->reason_for_termination = $request->get('reason_for_termination');
            $worker->dni = $request->get('dni');
            $worker->admission_date = ($request->get('admission_date') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('admission_date')) : null;
            $worker->num_children = $request->get('num_children');
            $worker->daily_salary = $request->get('daily_salary');
            $worker->monthly_salary = $request->get('monthly_salary');
            $worker->pension = $request->get('pension');
            $worker->gender = $request->get('gender');
            $worker->essalud = $value_essalud;
            $worker->assign_family = ($request->get('num_children') > 0) ? round($value_assign_family/30,2):0;
            //$worker->five_category = $request->get('five_category');
            $worker->termination_date = ($request->get('termination_date') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('termination_date')) : null;
            $worker->observation = $request->get('observation');
            $worker->civil_status_id = ($request->get('civil_status') == 0) ? null: $request->get('civil_status');
            $worker->work_function_id = ($request->get('work_function') == 0) ? null: $request->get('work_function');
            $worker->pension_system_id = ($request->get('pension_system') == 0) ? null: $request->get('pension_system');
            //$worker->working_day_id = ($request->get('working_day') == 0) ? null: $request->get('working_day');
            $worker->area_worker_id = ($request->get('area_worker') == 0) ? null: $request->get('area_worker');
            $worker->percentage_pension_system = ($request->get('percentage_pension_system') == 0 || $request->get('percentage_pension_system') == "") ? null: $request->get('percentage_pension_system');

            $worker->save();

            // Primero eliminamos los contactos
            $emergencyContacts = EmergencyContact::where('worker_id', $worker->id)->get();

            foreach ( $emergencyContacts as $emergencyContact )
            {
                $emergencyContact->delete();
            }

            // Creacion de los contactos de emergencia
            $names = $request->get('contacts');
            $relations = $request->get('relations');
            $phones = $request->get('phones');

            if ( $names != null )
            {
                for( $i=0; $i<count($phones); $i++ )
                {
                    $emergencyContact = EmergencyContact::create([
                        'name' => $names[$i],
                        'relationship_id' => $relations[$i],
                        'worker_id' => $worker->id,
                        'phone' => $phones[$i]
                    ]);
                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Colaborador actualizado con éxito.'], 200);

    }

    public function destroy($worker_id)
    {
        DB::beginTransaction();
        try {

            $worker = Worker::find($worker_id);

            $user = User::where('id',$worker->user_id )->first();

            if ( !is_null($user) )
            {
                $user->enable = false;
                $user->save();
            }

            $worker->enable = false;
            $worker->save();

            // TODO: Verificar si hay algun finishContract
            $finishContracts = FinishContract::where('worker_id', $worker->id)
                ->where('active', 1)
                ->get();
            if ( !is_null($finishContracts) )
            {
                foreach ( $finishContracts as $contract )
                {
                    $contract->active = false;
                    $contract->save();
                }

            }
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Colaborador inhabilitado con éxito.'], 200);

    }

    public function enable($worker_id)
    {
        DB::beginTransaction();
        try {

            $worker = Worker::find($worker_id);

            $user = User::where('id',$worker->user_id )->first();

            if ( !is_null($user) )
            {
                $user->enable = true;
                $user->save();
            }

            $worker->enable = true;
            $worker->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Colaborador habilitado con éxito.'], 200);

    }

    public function indexEnable()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('worker.indexEnable', compact('permissions'));
    }

    public function getWorkersEnable()
    {
        $workers = Worker::where('enable', 0)->get();
        $arrayWorkers = [];

        foreach ( $workers as $worker )
        {
            $haveContract = rand(0,1);

            array_push( $arrayWorkers, [
                'id' => $worker->id,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'personal_address' => $worker->personal_address,
                'birthplace' => ($worker->birthplace == null) ? '':$worker->birthplace->format('d/m/Y'),
                'age' => ($worker->birthplace == null) ? '': Carbon::parse($worker->birthplace)->age,
                'phone' => $worker->phone,
                'email' => $worker->email,
                'level_school' => $worker->level_school,
                'profession' => $worker->profession,
                'reason_for_termination' => $worker->reason_for_termination,
                'image' => $worker->image,
                'dni' => $worker->dni,
                'admission_date' => ($worker->admission_date == null) ? '': $worker->admission_date->format('d/m/Y'),
                'num_children' => $worker->num_children,
                'daily_salary' => $worker->daily_salary,
                'monthly_salary' => $worker->monthly_salary,
                'pension' => ($worker->pension == null || $worker->pension == "") ? "":$worker->pension,
                'gender' => $worker->gender,
                'essalud' => $worker->essalud,
                'assign_family' => $worker->assign_family,
                'five_category' => ($worker->five_category == 1) ? 'SI':'NO',
                'termination_date' => ($worker->termination_date == null) ? '':$worker->termination_date->format('d/m/Y'),
                'observation' => $worker->observation,
                'contract' => ($worker->contract_id == null) ? '': $worker->contract->code,
                'civil_status' => ($worker->civil_status_id == null) ? '': $worker->civil_status->description,
                'work_function' => ($worker->work_function_id == null) ? '': $worker->work_function->description,
                'pension_system' => ($worker->pension_system_id == null) ? '': $worker->pension_system->description,
                'percentage_pension_system' => ($worker->percentage_pension_system == null || $worker->percentage_pension_system == 0) ? '':$worker->percentage_pension_system,
                'area_worker' => ($worker->area_worker_id == null) ? '': $worker->area_worker->name,
                'have_contract' => rand(0,1)
            ] );
        }

        return datatables($arrayWorkers)->toJson();
    }

    public function pruebaCadenas()
    {
        $nombres = 'Gilberto';
        $apellidos = 'Huamán López';

        dump($apellidos);

        $primeraLetraNombres = strtolower(substr($nombres,0,1));
        $pos = strpos($apellidos, ' ');
        dump($primeraLetraNombres);
        $primerApellido = '';

        if ( $pos !== false )
        {
            $primerApellido = strtolower(substr($apellidos,0,$pos));
        }
        dump(substr($apellidos,0,$pos));
        dump($this->eliminar_tildes(substr($apellidos,0,$pos)));
        dump(strtolower($this->eliminar_tildes(substr($apellidos,0,$pos))));
        dump($this->eliminar_tildes($primeraLetraNombres));
        dump($this->eliminar_tildes($primerApellido));
        $email = '@sermeind.com.pe';

        dump($email);
    }

    public function getDataFinishContractWorker($worker_id)
    {
        $contract = DB::table('contracts')->where('worker_id', $worker_id)->latest('updated_at')->first();

        return response()->json([
            'contract_id' => $contract->id,
            'contract_name' => $contract->code
        ], 200);
    }

    public function getDataFinishContractWorkerEdit($worker_id)
    {
        $contract = DB::table('contracts')->where('worker_id', $worker_id)->latest('updated_at')->first();
        $last_finish_contract = DB::table('finish_contracts')->where('worker_id', $worker_id)->latest('created_at')->first();
        // Convierte la cadena a un objeto Carbon
        $date_finish = Carbon::createFromFormat('Y-m-d', $last_finish_contract->date_finish);
        // Formatea la fecha
        $formatted_date = $date_finish->format('d/m/Y');

        return response()->json([
            'contract_id' => $contract->id,
            'contract_name' => $contract->code,
            'date_finish' => $formatted_date,
            'reason' => $last_finish_contract->reason,
            'finish_contract_id' => $last_finish_contract->id
        ], 200);
    }

    public function finishContract( FinishContractStoreRequest $request )
    {
        DB::beginTransaction();
        try {
            // Creamos el email con el formato mapellido@sermeind.com
            $worker_id = $request->get('worker_id');
            $contract_id = $request->get('contract_id');
            $date_finish = ($request->get('date_finish') != null) ? Carbon::createFromFormat('d/m/Y', $request->get('date_finish')) : null;
            $reason = $request->get('reason');
            $type = $request->get('type');
            $finish_contract_id = $request->get('finish_contract_id');

            if ( $type == "s" )
            {
                // Creamos al usuario
                $finish_contract = FinishContract::create([
                    'worker_id' => $worker_id,
                    'contract_id' => $contract_id,
                    'date_finish' => $date_finish,
                    'reason' => $reason,
                    'active' => 1
                ]);
            } elseif ( $type == "e" ) {
                $finish_contract = FinishContract::find($finish_contract_id);
                $finish_contract->date_finish = $date_finish;
                $finish_contract->reason = $reason;
                $finish_contract->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            //dump($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contrato finalizado editado con éxito.'], 200);
    }

    public function getDataFinishContractWorkerDelete($worker_id)
    {
        $contract = DB::table('contracts')->where('worker_id', $worker_id)->latest('updated_at')->first();
        $last_finish_contract = DB::table('finish_contracts')->where('worker_id', $worker_id)->latest('created_at')->first();
        // Convierte la cadena a un objeto Carbon
        $date_finish = Carbon::createFromFormat('Y-m-d', $last_finish_contract->date_finish);
        // Formatea la fecha
        $formatted_date = $date_finish->format('d/m/Y');

        return response()->json([
            'contract_id' => $contract->id,
            'contract_name' => $contract->code,
            'date_finish' => $formatted_date,
            'reason' => $last_finish_contract->reason,
            'finish_contract_id' => $last_finish_contract->id
        ], 200);
    }

    public function finishContractDelete( FinishContractDeleteRequest $request )
    {
        DB::beginTransaction();
        try {
            // Creamos el email con el formato mapellido@sermeind.com
            $worker_id = $request->get('worker_id');
            $contract_id = $request->get('contract_id');
            $finish_contract_id = $request->get('finish_contract_id');

            $finish_contract = FinishContract::find($finish_contract_id);
            if ( isset($finish_contract) )
            {
                $finish_contract->delete();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            //dump($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Finalización de contrato eliminado con éxito.'], 200);
    }
}
