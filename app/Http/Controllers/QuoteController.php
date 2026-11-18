<?php

namespace App\Http\Controllers;

use App\Audit;
use App\ContactName;
use App\Customer;
use App\DateDimension;
use App\Equipment;
use App\EquipmentConsumable;
use App\EquipmentElectric;
use App\EquipmentMaterial;
use App\EquipmentTurnstile;
use App\EquipmentWorkday;
use App\EquipmentWorkforce;
use App\Exports\QuotesExcelDownload;
use App\Exports\QuotesReportExcelExport;
use App\FinanceWork;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\ImagesQuote;
use App\Material;
use App\MaterialTaken;
use App\Notification;
use App\NotificationUser;
use App\OutputDetail;
use App\PaymentDeadline;
use App\PorcentageQuote;
use App\Quote;
use App\QuoteUser;
use App\ResumenEquipment;
use App\ResumenQuote;
use App\Services\TipoCambioService;
use App\UnitMeasure;
use App\User;
use App\Workforce;
use Barryvdh\DomPDF\Facade as PDF;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Intervention\Image\Facades\Image;

class QuoteController extends Controller
{
    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
    }

    public function index()
    {
        $quotes = Quote::with(['customer'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.index', compact('quotes', 'permissions'));
    }

    public function getDataQuotesIndex(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $dateCurrent = Carbon::now('America/Lima');
            $date4MonthAgo = $dateCurrent->subMonths(6);
            $query = Quote::with('customer', 'deadline', 'users')
                /*->where('created_at', '>=', $date4MonthAgo)*/
                ->where('raise_status', 0)
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->orderBy('created_at', 'DESC');

        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->where('raise_status', 0)
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($description_quote) {
            $query->where('description_quote', 'LIKE', '%'.$description_quote.'%');
        }

        if ($year) {
            $query->whereYear('date_quote', $year);
        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($order) {
            $query->where('code_customer', 'LIKE', '%'.$order.'%');

        }

        if ($customer) {
            $query->whereHas('customer', function ($query2) use ($customer) {
                $query2->where('customer_id', $customer);
            });

        }

        if ($creator != "")
        {
            $query->whereHas('users', function ($query2) use ($creator) {
                $query2->where('user_id', $creator);
            });
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        case 'VB_finance':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('vb_finances', 1)
                                ->whereNull('vb_operations');
                            break;
                        case 'VB_operation':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('vb_finances', 1)
                                ->where('vb_operations', 1);
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close')
                                ->where(function ($q2) {
                                    $q2->where('vb_finances', null)
                                        ->where('vb_operations', null);
                                });
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        //$query = FinanceWork::with('quote', 'bank');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $quotes = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $array = [];

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = '<span class="badge bg-warning">Enviado</span>';
                } else {
                    $state = 'created';
                    $stateText = '<span class="badge bg-primary">Creada</span>';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = '<span class="badge bg-danger">Finalizada</span>';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){
                    if ( $quote->vb_finances == 1 && $quote->vb_operations == null )
                    {
                        $state = 'raise';
                        $stateText = '<span class="badge bg-success">Elevada</span>';
                        /*$state = 'VB_finance';
                        $stateText = '<span class="badge bg-gradient-navy text-white">V.B. Finanzas <br>'. $quote->date_vb_finances->format("d/m/Y") .' </span>';
                    */
                    } else {
                        if ( /*$quote->vb_finances == 1 &&*/ $quote->vb_operations == 1 )
                        {
                            $state = 'VB_operation';
                            $stateText = '<span class="badge bg-gradient-orange text-white">V.B. Operaciones <br> '.$quote->date_vb_operations->format("d/m/Y").'</span>';
                        } else {
                            if ( $quote->vb_operations == 0 || $quote->vb_operations == null )
                            {
                                $state = 'raise';
                                $stateText = '<span class="badge bg-success">Elevada</span>';
                            }
                        }
                    }
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  '<span class="badge bg-success">Confirmada</span>';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = '<span class="badge bg-danger">Cancelada</span>';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = '<span class="badge bg-success">Mostrar</span>';
            } else {
                $stateDecimals = '<span class="badge bg-danger">Ocultar</span>';
            }
            array_push($array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => number_format($quote->total_quote/1.18, 0),
                "total" => number_format($quote->total_quote/1, 0),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "send_state" => $quote->send_state
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

    public function index2V2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('date_quote')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayCustomers = Customer::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
        $arrayStates = [
            ["value" => "created", "display" => "CREADAS"],
            ["value" => "send", "display" => "ENVIADAS"],
            ["value" => "confirm", "display" => "CONFIRMADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quote.indexv2', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function getDataQuotesRaise(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $dateCurrent = Carbon::now('America/Lima');
            $date4MonthAgo = $dateCurrent->subMonths(6);
            $query = Quote::with('customer', 'deadline', 'users')
                /*->where('created_at', '>=', $date4MonthAgo)*/
                ->where('state_active','open')
                ->where('state','confirmed')
                ->orderBy('created_at', 'DESC');

        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->where('state_active','open')
                ->where('state','confirmed')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($description_quote) {
            $query->where('description_quote', 'LIKE', '%'.$description_quote.'%');
        }

        if ($year) {
            $query->whereYear('date_quote', $year);
        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($order) {
            $query->where('code_customer', 'LIKE', '%'.$order.'%');

        }

        if ($customer) {
            $query->whereHas('customer', function ($query2) use ($customer) {
                $query2->where('customer_id', $customer);
            });

        }

        if ($creator != "")
        {
            $query->whereHas('users', function ($query2) use ($creator) {
                $query2->where('user_id', $creator);
            });
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        /*case 'VB_finance':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('vb_finances', 1)
                                ->whereNull('vb_operations');
                            break;*/
                        case 'VB_operation':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                /*->where('vb_finances', 1)*/
                                ->where('vb_operations', 1);
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close')
                                ->where(function ($q2) {
                                    $q2->where('vb_operations', null);
                                });
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        //$query = FinanceWork::with('quote', 'bank');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $quotes = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $array = [];

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = '<span class="badge bg-warning">Enviado</span>';
                } else {
                    $state = 'created';
                    $stateText = '<span class="badge bg-primary">Creada</span>';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = '<span class="badge bg-danger">Finalizada</span>';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){
                    if ( $quote->vb_finances == 1 && $quote->vb_operations == null )
                    {
                        $state = 'raise';
                        $stateText = '<span class="badge bg-success">Elevada</span>';
                        /*$state = 'VB_finance';
                        $stateText = '<span class="badge bg-gradient-navy text-white">V.B. Finanzas <br>'. $quote->date_vb_finances->format("d/m/Y") .' </span>';
                    */
                    } else {
                        if ( /*$quote->vb_finances == 1 &&*/ $quote->vb_operations == 1 )
                        {
                            $state = 'VB_operation';
                            $stateText = '<span class="badge bg-gradient-orange text-white">V.B. Operaciones <br> '.$quote->date_vb_operations->format("d/m/Y").'</span>';
                        } else {
                            if ( $quote->vb_operations == 0 || $quote->vb_operations == null )
                            {
                                $state = 'raise';
                                $stateText = '<span class="badge bg-success">Elevada</span>';
                            }
                        }
                    }
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  '<span class="badge bg-success">Confirmada</span>';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = '<span class="badge bg-danger">Cancelada</span>';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = '<span class="badge bg-success">Mostrar</span>';
            } else {
                $stateDecimals = '<span class="badge bg-danger">Ocultar</span>';
            }
            array_push($array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => number_format($quote->total_quote/1.18, 0),
                "total" => number_format($quote->total_quote/1, 0),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "send_state" => $quote->send_state
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

    public function raiseV2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('date_quote')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayCustomers = Customer::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
        $arrayStates = [
            ["value" => "confirm", "display" => "CONFIRMADAS"],
            ["value" => "raised", "display" => "ELEVADAS"],
            ["value" => "VB_operation", "display" => "VB OPERACIONES"],
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quote.raisev2', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function indexGeneral()
    {
        $quotes = Quote::with(['customer'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.general', compact('quotes', 'permissions'));
    }

    public function create()
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $maxId = Quote::max('id')+1;
        $length = 5;
        $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable()->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        //dd($array);

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
                'type_scrap' => $material->typeScrap,
                'stock_current' => $material->stock_current,
                'unit_price' => $material->unit_price,
                'unit' => ($material->unitMeasure == null) ? "":$material->unitMeasure->name,
                'code' => $material->code,
                'unit_measure' => $material->unitMeasure,
                'typescrap_id' => $material->typescrap_id,
                'enable_status' => $material->enable_status,
                'update_price' => $material->state_update_price

            ]);
        }

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Crear cotizacion VISTA',
            'time' => $end
        ]);
        return view('quote.create', compact('customers', 'unitMeasures', 'consumables', 'electrics', 'workforces', 'codeQuote', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'array'));
    }

    public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        //dump($request);
        //dump($request->descplanos);
        //dump($request->planos);
        //dd();
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;
            //$codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),
                'date_quote' => ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now(),
                'date_validate' => ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5),
                'way_to_pay' => ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '',
                'delivery_time' => ($request->has('delivery_time')) ? $request->get('delivery_time') : '',
                'customer_id' => ($request->has('customer_id')) ? $request->get('customer_id') : null,
                'contact_id' => ($request->has('contact_id')) ? $request->get('contact_id') : null,
                'payment_deadline_id' => ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null,
                'state' => 'created',
                //'utility' => ($request->has('utility')) ? $request->get('utility'): 0,
                //'letter' => ($request->has('letter')) ? $request->get('letter'): 0,
                //'rent' => ($request->has('taxes')) ? $request->get('taxes'): 0,
            ]);

            $codeQuote = '';
            if ( $maxId < $quote->id ){
                $codeQuote = 'COT-'.str_pad($quote->id,$length,"0", STR_PAD_LEFT);
                $quote->code = $codeQuote;
                $quote->save();
            } else {
                $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $quote->code = $codeQuote;
                $quote->save();
            }

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' =>($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                    'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                    'quantity' => $equipments[$i]->quantity,
                    'utility' => $equipments[$i]->utility,
                    'rent' => $equipments[$i]->rent,
                    'letter' => $equipments[$i]->letter,
                    'total' => $equipments[$i]->total
                ]);

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalElectric = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                $materials = $equipments[$i]->materials;

                $consumables = $equipments[$i]->consumables;

                $electrics = $equipments[$i]->electrics;

                $workforces = $equipments[$i]->workforces;

                $tornos = $equipments[$i]->tornos;

                $dias = $equipments[$i]->dias;

                for ( $j=0; $j<sizeof($materials); $j++ )
                {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float) $materials[$j]->quantity,
                        'price' => (float) $materials[$j]->material->unit_price,
                        'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                        'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                        'percentage' => (float) $materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                        'total' => (float) $materials[$j]->quantity*(float) $materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                for ( $k=0; $k<sizeof($consumables); $k++ )
                {
                    $material = Material::find($consumables[$k]->id);

                    $equipmentConsumable = EquipmentConsumable::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $consumables[$k]->id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'price' => (float) $consumables[$k]->price,
                        'total' => (float) $consumables[$k]->total,
                        'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado':'Completo',
                    ]);

                    $totalConsumable += $equipmentConsumable->total;
                }

                for ( $k=0; $k<sizeof($electrics); $k++ )
                {
                    $equipmentElectric = EquipmentElectric::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $electrics[$k]->id,
                        'quantity' => (float) $electrics[$k]->quantity,
                        'price' => (float) $electrics[$k]->price,
                        'total' => (float) $electrics[$k]->total,
                    ]);

                    $totalElectric += $equipmentElectric->total;
                }

                for ( $w=0; $w<sizeof($workforces); $w++ )
                {
                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description,
                        'price' => (float) $workforces[$w]->price,
                        'quantity' => (float) $workforces[$w]->quantity,
                        'total' => (float) $workforces[$w]->total,
                        'unit' => $workforces[$w]->unit,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                for ( $r=0; $r<sizeof($tornos); $r++ )
                {
                    $equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $equipment->id,
                        'description' => $tornos[$r]->description,
                        'price' => (float) $tornos[$r]->price,
                        'quantity' => (float) $tornos[$r]->quantity,
                        'total' => (float) $tornos[$r]->total
                    ]);

                    $totalTornos += $equipmenttornos->total;
                }

                for ( $d=0; $d<sizeof($dias); $d++ )
                {
                    $equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $equipment->id,
                        'description' => $dias[$d]->description,
                        'quantityPerson' => (float) $dias[$d]->quantity,
                        'hoursPerPerson' => (float) $dias[$d]->hours,
                        'pricePerHour' => (float) $dias[$d]->price,
                        'total' => (float) $dias[$d]->total
                    ]);

                    $totalDias += $equipmentdias->total;
                }

                // TODO: Cambio el 16/01/2024
                //$totalEquipo = (($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos) * (float)$equipment->quantity)+$totalDias;
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                $totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;

                $equipment->save();
            }

            $quote->total = $totalQuote;

            $quote->save();

            // TODO: Tratamiento de las imagenes de los planos
            $images = $request->planos;
            $descriptions = $request->descplanos;

            if ( isset($images) )
            {
                if ( count($images) != 0 && count($descriptions) )
                {
                    foreach ( $images as $key => $image )
                    {
                        $path = public_path().'/images/planos/';
                        $img = $image;

                        $filename = $quote->id .'_'. $this->generateRandomString(20). '.JPG';
                        $imgQuote = Image::make($img);
                        $imgQuote->orientate();
                        $imgQuote->save($path.$filename, 80, 'JPG');

                        ImagesQuote::create([
                            'quote_id' => $quote->id,
                            'description' => $descriptions[$key],
                            'image' => $filename,
                            'order' => $key+1
                        ]);

                    }
                }
            }


            // Crear notificacion
            $notification = Notification::create([
                'content' => $quote->code.' creada por '.Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal' , 'logistic'])->get();
            foreach ( $users as $user )
            {
                if ( $user->id != Auth::user()->id )
                {
                    foreach ( $user->roles as $role )
                    {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar cotizacion POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cotización '.$codeQuote.' guardada con éxito.'], 200);

    }

    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function show($id)
    {
        $begin = microtime(true);
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->get();
        $workforces = Workforce::with('unitMeasure')->get();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        //dump($quote);
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion VISTA',
            'time' => $end
        ]);
        return view('quote.show', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'paymentDeadlines'));
    }

    public function edit($id)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->where('description','LIKE',"%".$defaultConsumable."%")->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();
        $quote3 = Quote::where('id', $id)
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();

        if ( $quote3->state === 'created' /*&& $quote3->send_state == 0*/ )
        {

            foreach( $quote3->equipments as $equipment )
            {
                // TODO: Actualizamos los porcentages si no estan registrados
                if ( $equipment->utility == 0 && $equipment->rent && $equipment->letter == 0 )
                {
                    $equipment->utility = $quote3->utility;
                    $equipment->rent = $quote3->rent;
                    $equipment->letter = $quote3->letter;
                    $equipment->save();

                }

                // TODO: Actualizar los precios
                foreach ( $equipment->materials as $equipment_material )
                {
                    if ( $equipment_material->price !== $equipment_material->material->unit_price )
                    {
                        $equipment_material->price = $equipment_material->material->unit_price;
                        $equipment_material->total = $equipment_material->material->unit_price * $equipment_material->quantity;
                        $equipment_material->save();
                    }
                }

                foreach ( $equipment->consumables as $equipment_consumable )
                {
                    if ( $equipment_consumable->price !== $equipment_consumable->material->unit_price )
                    {
                        $equipment_consumable->price = $equipment_consumable->material->unit_price;
                        $equipment_consumable->total = $equipment_consumable->material->unit_price * $equipment_consumable->quantity;
                        $equipment_consumable->save();
                    }
                }

                foreach ( $equipment->electrics as $equipment_electric )
                {
                    if ( $equipment_electric->price !== $equipment_electric->material->unit_price )
                    {
                        $equipment_electric->price = $equipment_electric->material->unit_price;
                        $equipment_electric->total = $equipment_electric->material->unit_price * $equipment_electric->quantity;
                        $equipment_electric->save();
                    }
                }
            }

            $quote2 = Quote::where('id', $id)
                ->with(['equipments' => function ($query) {
                    $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
                }])->first();

            $new_total_quote = 0;
            foreach( $quote2->equipments as $equipment )
            {
                $new_total_material = 0;
                foreach ( $equipment->materials as $equipment_material )
                {
                    $new_total_material = $new_total_material + $equipment_material->total;
                }
                $new_total_consumable = 0;
                foreach ( $equipment->consumables as $equipment_consumable )
                {
                    $new_total_consumable = $new_total_consumable + $equipment_consumable->total;
                }
                $new_total_electric = 0;
                foreach ( $equipment->electrics as $equipment_electric )
                {
                    $new_total_electric = $new_total_electric + $equipment_electric->total;
                }
                $new_total_workforce = 0;
                foreach ( $equipment->workforces as $equipment_workforce )
                {
                    $new_total_workforce = $new_total_workforce + $equipment_workforce->total;
                }
                $new_total_turnstile = 0;
                foreach ( $equipment->turnstiles as $equipment_turnstile )
                {
                    $new_total_turnstile = $new_total_turnstile + $equipment_turnstile->total;
                }
                $new_total_workday = 0;
                foreach ( $equipment->workdays as $equipment_workday )
                {
                    $new_total_workday = $new_total_workday + $equipment_workday->total;
                }

                $totalEquipo = (($new_total_material + $new_total_consumable + $new_total_electric + $new_total_workforce + $new_total_turnstile  + $new_total_workday ) * $equipment->quantity);
                $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                $new_total_quote = $new_total_quote + $totalEquipmentR;
                $equipment->total = $totalEquipo;
                $equipment->save();
            }
            $quote2->total = $new_total_quote ;
            $quote2->save();


        }

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();

        $images = [];

        $imagenes = ImagesQuote::where('quote_id', $quote->id)->get();

        if ($imagenes->count() > 0)
        {
            $images = $imagenes;
        }

        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        //dd($array);

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
                'type_scrap' => $material->typeScrap,
                'stock_current' => $material->stock_current,
                'unit_price' => $material->unit_price,
                'unit' => $material->unitMeasure->name,
                'code' => $material->code,
                'unit_measure' => $material->unitMeasure,
                'typescrap_id' => $material->typescrap_id,
                'enable_status' => $material->enable_status,
                'update_price' => $material->state_update_price
            ]);
        }

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Editar cotizacion VISTA',
            'time' => $end
        ]);
        //dump($consumables);

        return view('quote.edit', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'images', 'array'));

    }

    public function editPlanos($id)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->first();

        $images = [];

        $imagenes = ImagesQuote::where('quote_id', $quote->id)->get();

        if ($imagenes->count() > 0)
        {
            $images = $imagenes;
        }
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver editar planos VISTA',
            'time' => $end
        ]);
        //dump($quote);
        return view('quote.editPlanos', compact('quote','permissions', 'images'));

    }

    public function updatePlanos(Request $request, $image)
    {
        $begin = microtime(true);
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            $id = $request->get('image_id');
            $description = $request->get('image_description');
            $order = $request->get('image_order');
            $height = $request->get('image_height');
            $width = $request->get('image_width');

            $image = ImagesQuote::find($id);
            $image->description = $description;
            $image->order = $order;
            $image->height = $height;
            $image->width = $width;
            $image->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Imagenes de planos modificados',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Imagen modificada con éxito'], 200);

    }

    public function deletePlanos(Request $request, $image)
    {
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            $id = $request->get('image_id');

            $imagen = ImagesQuote::find($id);

            $image_path = public_path().'/images/planos/'.$imagen->image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $imagen->delete();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Imagen eliminada con éxito'], 200);

    }

    public function savePlanos(Request $request, $quote_id)
    {
        $begin = microtime(true);
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            // TODO: Tratamiento de las imagenes de los planos

            $images = $request->planos;
            $descriptions = $request->descplanos;
            $orders = $request->orderplanos;
            $heights = $request->heights;
            $widths = $request->widths;

            if ( count($images) != 0 && count($descriptions) != 0 )
            {
                foreach ( $images as $key => $image )
                {
                    $path = public_path().'/images/planos/';
                    $img = $image;

                    $extension = $img->getClientOriginalExtension();
                    //$filename = $entry->id . '.' . $extension;
                    if ( strtoupper($extension) != "PDF" )
                    {
                        $filename = $quote_id .'_'. $this->generateRandomString(20). '.JPG';
                        $imgQuote = Image::make($img);
                        $imgQuote->orientate();
                        $imgQuote->save($path.$filename, 80, 'JPG');

                        ImagesQuote::create([
                            'quote_id' => $quote_id,
                            'description' => $descriptions[$key],
                            'image' => $filename,
                            'order' => $orders[$key],
                            'type' => 'img',
                            'height' => $heights[$key],
                            'width' => $widths[$key]
                        ]);
                    } else {
                        $filename = 'pdf'.$quote_id .'_'. $this->generateRandomString(20) . '.' .$extension;
                        $img->move($path, $filename);

                        ImagesQuote::create([
                            'quote_id' => $quote_id,
                            'description' => $descriptions[$key],
                            'image' => $filename,
                            'order' => $orders[$key],
                            'type' => 'pdf',
                            'height' => $heights[$key],
                            'width' => $widths[$key]
                        ]);
                    }

                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar planos POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Imágenes guardadas con éxito'], 200);

    }

    public function adjust($id)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('contact')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ajustar cotizaciones VISTA',
            'time' => $end
        ]);

        //dump($quote);
        return view('quote.adjust', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'permissions', 'paymentDeadlines'));

    }

    public function update(UpdateQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $quote = Quote::find($request->get('quote_id'));

            $quote->code = $request->get('code_quote');
            $quote->description_quote = $request->get('code_description');
            $quote->observations = $request->get('observations');
            $quote->date_quote = ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now();
            $quote->date_validate = ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5);
            $quote->way_to_pay = ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '';
            $quote->payment_deadline_id = ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null;
            $quote->delivery_time = ($request->has('delivery_time')) ? $request->get('delivery_time') : '';
            $quote->customer_id = ($request->has('customer_id')) ? $request->get('customer_id') : null;
            $quote->contact_id = ($request->has('contact_id')) ? $request->get('contact_id') : null;
            //$quote->utility = ($request->has('utility')) ? $request->get('utility'): 0;
            //$quote->letter = ($request->has('letter')) ? $request->get('letter'): 0;
            //$quote->rent = ($request->has('taxes')) ? $request->get('taxes'): 0;
            $quote->currency_invoice = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                if ($equipments[$i]->quote === '' )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                        'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                        'quantity' => $equipments[$i]->quantity,
                        'utility' => $equipments[$i]->utility,
                        'rent' => $equipments[$i]->rent,
                        'letter' => $equipments[$i]->letter,
                        'total' => $equipments[$i]->total
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equipments[$i]->materials;

                    $consumables = $equipments[$i]->consumables;

                    $electrics = $equipments[$i]->electrics;

                    $workforces = $equipments[$i]->workforces;

                    $tornos = $equipments[$i]->tornos;

                    $dias = $equipments[$i]->dias;

                    for ( $j=0; $j<sizeof($materials); $j++ )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $materials[$j]->material->id,
                            'quantity' => (float) $materials[$j]->quantity,
                            'price' => (float) $materials[$j]->material->unit_price,
                            'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                            'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                            'percentage' => (float) $materials[$j]->quantity,
                            'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                            'total' => (float) $materials[$j]->material->unit_price*(float) $materials[$j]->quantity,
                        ]);

                        $totalMaterial += $equipmentMaterial->total;
                    }

                    for ( $k=0; $k<sizeof($consumables); $k++ )
                    {
                        $material = Material::find($consumables[$k]->id);

                        $equipmentConsumable = EquipmentConsumable::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumables[$k]->id,
                            'quantity' => (float) $consumables[$k]->quantity,
                            'price' => (float) $consumables[$k]->price,
                            'total' => (float) $consumables[$k]->quantity*(float) $consumables[$k]->price,
                            'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado':'Completo',
                        ]);

                        $totalConsumable += $equipmentConsumable->total;
                    }

                    for ( $k=0; $k<sizeof($electrics); $k++ )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electrics[$k]->id,
                            'quantity' => (float) $electrics[$k]->quantity,
                            'price' => (float) $electrics[$k]->price,
                            'total' => (float) $electrics[$k]->quantity*(float) $electrics[$k]->price,
                        ]);

                        $totalElectric += $equipmentElectric->total;
                    }

                    for ( $w=0; $w<sizeof($workforces); $w++ )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforces[$w]->description,
                            'price' => (float) $workforces[$w]->price,
                            'quantity' => (float) $workforces[$w]->quantity,
                            'total' => (float) $workforces[$w]->price*(float) $workforces[$w]->quantity,
                            'unit' => $workforces[$w]->unit,
                        ]);

                        $totalWorkforces += $equipmentWorkforce->total;
                    }

                    for ( $r=0; $r<sizeof($tornos); $r++ )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $tornos[$r]->description,
                            'price' => (float) $tornos[$r]->price,
                            'quantity' => (float) $tornos[$r]->quantity,
                            'total' => (float) $tornos[$r]->price*(float) $tornos[$r]->quantity
                        ]);

                        $totalTornos += $equipmenttornos->total;
                    }

                    for ( $d=0; $d<sizeof($dias); $d++ )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dias[$d]->description,
                            'quantityPerson' => (float) $dias[$d]->quantity,
                            'hoursPerPerson' => (float) $dias[$d]->hours,
                            'pricePerHour' => (float) $dias[$d]->price,
                            'total' => (float) $dias[$d]->quantity*(float) $dias[$d]->hours*(float) $dias[$d]->price
                        ]);

                        $totalDias += $equipmentdias->total;
                    }

                    //$totalQuote += ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity;

                    //$equipment->total = ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias)* (float)$equipment->quantity;

                    // Cambio el 16/01/2024
                    //$totalEquipo = (($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos) * (float)$equipment->quantity) + $totalDias;
                    $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                    $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                    $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                    $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                    $totalQuote += $totalEquipmentR;

                    $equipment->total = $totalEquipo;

                    $equipment->save();
                }

            }

            $quote->total += $totalQuote;

            $quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar cotizaciones POST',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Nuevos equipos guardados con éxito.'], 200);

    }

    public function destroy(Quote $quote)
    {
        $quote->state = 'canceled';
        $quote->save();
    }

    public function confirm(Quote $quote)
    {
        $quote->state = 'confirmed';
        $quote->save();
    }

    public function send(Quote $quote)
    {
        $quote->send_state = true;
        $quote->save();
    }

    public function selectMaterials(Request $request)
    {
        /*$page = $request->get('page');

        $resultCount = 25;

        $offset = ($page - 1) * $resultCount;

        $search = $request->get('term');

        //$materials = Material::where('description', 'LIKE',  '%' . $search . '%')->orderBy('description')->skip($offset)->take($resultCount)->get(['id','description']);
        $materials = Material::skip($offset)->take($resultCount)->get()->filter(function ($item) use ($search) {
            // replace stristr with your choice of matching function
            return stripos($item->full_description, $search) === false ? false : true;

        });

        //dump($materials[0]->name_product);
        $count = Count(Material::get()->filter(function ($item) use ($search) {
            // replace stristr with your choice of matching function
            return stripos($item->full_description, $search) === false ? false : true;

        }));
        //dump($count);
        $endCount = $offset + $resultCount;
        //dump($endCount);
        $morePages = $count > $endCount;

        $results = array(
            "results" => $materials,
            "pagination" => array(
                "more" => $morePages
            )
        );
        //dump($results);
        return response()->json($results);*/
        $materials = [];

        if($request->has('q')){
            $search = $request->get('q');
            $materials = Material::get()->filter(function ($item) use ($search) {
                // replace stristr with your choice of matching function
                return false !== stristr($item->full_description, $search);

            });
        }
        return json_encode($materials);


    }

    public function getMaterials()
    {
        /*$materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('enable_status', 1)->get();*/
        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_description' => $material->full_description,
                'unit' => $material->unitMeasure->name,
                'code' => $material->code,
                'type_scrap' => $material->typeScrap,
                'unit_measure' => $material->unitMeasure
            ]);
        }

        return $array;
    }

    public function getMaterialsTypeahead()
    {
        /*$materials = Material::where('enable_status', 1)->get();*/
        $materials = Material::all();
        return $materials;
    }

    public function selectConsumables(Request $request)
    {

        /*$page = $request->get('page');
        dump($page);
        $resultCount = 25;

        $offset = ($page - 1) * $resultCount;

        $search = $request->get('term');
        $materials = Material::where('category_id', 2)->get()->filter(function ($item) use ($search) {
            // replace stristr with your choice of matching function
            return false !== stristr($item->full_description, $search);
        });
        dump($materials);
        $count = Count($materials);
        $endCount = $offset + $resultCount;
        $morePages = $count > $endCount;

        $results = array(
            "results" => $materials,
            "pagination" => array(
                "more" => $morePages
            )
        );
        dump($results);*/
        //return response()->json($results);
        $materials = [];

        if($request->has('q')){
            $search = $request->get('q');
            $materials = Material::where('category_id', 2)->get()->filter(function ($item) use ($search) {
                // replace stristr with your choice of matching function
                return false !== stristr($item->full_description, $search);
            });
        }
        return json_encode($materials);
    }

    public function getConsumables()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('category_id', 2)/*->where('enable_status', 1)*/->get();
        return $materials;
    }

    public function getAllQuotes()
    {
        $begin = microtime(true);
        $quotes = Quote::with('customer')
            ->with('deadline')
            ->with(['users' => function ($query) {
                $query->with(['user']);
            }])
            ->where('raise_status', 0)
            ->whereNotIn('state', ['canceled', 'expired'])
            ->where('state_active', 'open')
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener todas las cotizaciones VISTA',
            'time' => $end
        ]);
        return datatables($quotes)->toJson();
    }

    public function getAllQuotesGeneral()
    {
        $begin = microtime(true);
        $quotes = Quote::with('customer')
            ->with('deadline')
            ->with(['users' => function ($query) {
                $query->with(['user']);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener lista cotizaciones general',
            'time' => $end
        ]);
        return datatables($quotes)->toJson();
    }

    public function getDataQuotes(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $dateCurrent = Carbon::now('America/Lima');
            $date4MonthAgo = $dateCurrent->subMonths(6);
            $query = Quote::with('customer', 'deadline', 'users')
                /*->where('created_at', '>=', $date4MonthAgo)*/
                ->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($description_quote) {
            $query->where('description_quote', 'LIKE', '%'.$description_quote.'%');
        }

        if ($year) {
            $query->whereYear('date_quote', $year);
        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($order) {
            $query->where('code_customer', 'LIKE', '%'.$order.'%');

        }

        if ($customer) {
            $query->whereHas('customer', function ($query2) use ($customer) {
                $query2->where('customer_id', $customer);
            });

        }

        if ($creator != "")
        {
            $query->whereHas('users', function ($query2) use ($creator) {
                $query2->where('user_id', $creator);
            });
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        /*case 'VB_finance':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('vb_finances', 1)
                                ->whereNull('vb_operations');
                            break;*/
                        case 'VB_operation':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                /*->where('vb_finances', 1)*/
                                ->where('vb_operations', 1);
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close')
                                ->where(function ($q2) {
                                    $q2->where('vb_operations', null);
                                });
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        //$query = FinanceWork::with('quote', 'bank');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $quotes = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $array = [];

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = '<span class="badge bg-warning">Enviado</span>';
                } else {
                    $state = 'created';
                    $stateText = '<span class="badge bg-primary">Creada</span>';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = '<span class="badge bg-danger">Finalizada</span>';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){
                    if ( $quote->vb_finances == 1 && $quote->vb_operations == null )
                    {
                        $state = 'raise';
                        $stateText = '<span class="badge bg-success">Elevada</span>';
                        /*$state = 'VB_finance';
                        $stateText = '<span class="badge bg-gradient-navy text-white">V.B. Finanzas <br>'. $quote->date_vb_finances->format("d/m/Y") .' </span>';
                    */
                    } else {
                        if ( /*$quote->vb_finances == 1 &&*/ $quote->vb_operations == 1 )
                        {
                            $state = 'VB_operation';
                            $stateText = '<span class="badge bg-gradient-orange text-white">V.B. Operaciones <br> '.$quote->date_vb_operations->format("d/m/Y").'</span>';
                        } else {
                            if ( $quote->vb_operations == 0 || $quote->vb_operations == null )
                            {
                                $state = 'raise';
                                $stateText = '<span class="badge bg-success">Elevada</span>';
                            }
                        }
                    }
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  '<span class="badge bg-success">Confirmada</span>';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = '<span class="badge bg-danger">Cancelada</span>';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = '<span class="badge bg-success">Mostrar</span>';
            } else {
                $stateDecimals = '<span class="badge bg-danger">Ocultar</span>';
            }
            array_push($array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => number_format($quote->total_quote/1.18, 0),
                "total" => number_format($quote->total_quote/1, 0),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "send_state" => $quote->send_state
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

    public function indexV2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('date_quote')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayCustomers = Customer::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
        $arrayStates = [
            ["value" => "created", "display" => "CREADAS"],
            ["value" => "send", "display" => "ENVIADAS"],
            ["value" => "confirm", "display" => "CONFIRMADAS"],
            ["value" => "raised", "display" => "ELEVADAS"],
            /*["value" => "VB_finance", "display" => "VB FINANZAS"],*/
            ["value" => "VB_operation", "display" => "VB OPERACIONES"],
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quote.general_v2', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function exportQuotesExcel()
    {
        $start = $_GET['start'];
        $end = $_GET['end'];
        $type = $_GET['stateQuote'];
        $quotes_array = [];
        $dates = '';

        if ( $start == '' || $end == '' )
        {
            $dates = 'TOTALES';
            $quotes = [];
            switch ($type) {
                case 'all':
                    $quotes = Quote::with(['customer'])
                        //->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'raised':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'close':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','close')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $quotes as $quote )
            {
                $date_quote = Carbon::createFromFormat('Y-m-d H:i:s', $quote->date_quote)->format('d-m-Y');

                $monto_materiales = 0;
                $monto_consumibles = 0;
                $monto_servicios_varios = 0;
                $monto_servicios_adicionales = 0;
                $monto_dias_trabajo = 0;

                foreach( $quote->equipments as $equipment )
                {
                    foreach ( $equipment->materials as $material  )
                    {
                        if ( $material->original == 1 && $material->replacement == 0 )
                        {
                            $monto_materiales += (($material->price * $material->quantity)*$equipment->quantity);
                        }

                    }

                    foreach ( $equipment->consumables as $consumable  )
                    {
                        $monto_consumibles += (($consumable->price * $consumable->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workforces as $workforce  )
                    {
                        $monto_servicios_varios += (($workforce->price * $workforce->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->turnstiles as $turnstile  )
                    {
                        $monto_servicios_adicionales += (($turnstile->price * $turnstile->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workdays as $workday  )
                    {
                        $monto_dias_trabajo += (($workday->total)*$equipment->quantity);
                    }
                }

                $output_details = OutputDetail::where('quote_id', $quote->id)
                    ->get();

                $monto_materiales_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id != 2 )
                        {
                            $monto_materiales_real += ($output_detail->price);
                        }

                    }
                }

                $monto_consumibles_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id == 2 )
                        {
                            $monto_consumibles_real += ($output_detail->price);
                        }

                    }
                }

                array_push($quotes_array, [
                    'date' => $date_quote,
                    'code' => $quote->code,
                    'description' => $quote->description_quote,
                    'materials_quote' => $monto_materiales,
                    'materials_real' => $monto_materiales_real,
                    'consumables_quote' => $monto_consumibles,
                    'consumables_real' => $monto_consumibles_real,
                    'monto_servicios_varios' => $monto_servicios_varios,
                    'monto_servicios_adicionales' => $monto_servicios_adicionales,
                    'monto_dias_trabajo' => $monto_dias_trabajo,
                    'total' => $quote->total_quote,
                    'currency_invoice' => $quote->currency_invoice,
                    'state_raise' => $quote->raise_status,
                    'state_active' => $quote->state_active,
                ]);

            }


        } else {
            $date_start = Carbon::createFromFormat('d/m/Y', $start);
            $end_start = Carbon::createFromFormat('d/m/Y', $end);

            $dates = 'DEL '. $start .' AL '. $end;
            $quotes = [];
            switch ($type) {
                case 'all':
                    $quotes = Quote::with(['customer'])
                        //->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'raised':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'close':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','close')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $quotes as $quote )
            {
                $date_quote = Carbon::createFromFormat('Y-m-d H:i:s', $quote->date_quote)->format('d-m-Y');

                $monto_materiales = 0;
                $monto_consumibles = 0;
                $monto_servicios_varios = 0;
                $monto_servicios_adicionales = 0;
                $monto_dias_trabajo = 0;

                foreach( $quote->equipments as $equipment )
                {
                    foreach ( $equipment->materials as $material  )
                    {
                        if ( $material->original == 1 && $material->replacement == 0 )
                        {
                            $monto_materiales += (($material->price * $material->quantity)*$equipment->quantity);
                        }

                    }

                    foreach ( $equipment->consumables as $consumable  )
                    {
                        $monto_consumibles += (($consumable->price * $consumable->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workforces as $workforce  )
                    {
                        $monto_servicios_varios += (($workforce->price * $workforce->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->turnstiles as $turnstile  )
                    {
                        $monto_servicios_adicionales += (($turnstile->price * $turnstile->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workdays as $workday  )
                    {
                        $monto_dias_trabajo += (($workday->total)*$equipment->quantity);
                    }
                }

                $output_details = OutputDetail::where('quote_id', $quote->id)
                    ->get();

                $monto_materiales_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id != 2 )
                        {
                            $monto_materiales_real += ($output_detail->price);
                        }

                    }
                }

                $monto_consumibles_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id == 2 )
                        {
                            $monto_consumibles_real += ($output_detail->price);
                        }

                    }
                }


                array_push($quotes_array, [
                    'date' => $date_quote,
                    'code' => $quote->code,
                    'description' => $quote->description_quote,
                    'materials_quote' => $monto_materiales,
                    'materials_real' => $monto_materiales_real,
                    'consumables_quote' => $monto_consumibles,
                    'consumables_real' => $monto_consumibles_real,
                    'monto_servicios_varios' => $monto_servicios_varios,
                    'monto_servicios_adicionales' => $monto_servicios_adicionales,
                    'monto_dias_trabajo' => $monto_dias_trabajo,
                    'total' => $quote->total_quote,
                    'currency_invoice' => $quote->currency_invoice,
                    'state_raise' => $quote->raise_status,
                    'state_active' => $quote->state_active,
                ]);
            }

        }

        return (new QuotesReportExcelExport($quotes_array, $dates))->download('reporteCotizaciones.xlsx');

    }

    public function downloadQuotesExcel()
    {
        $startDate = $_GET['start'];
        $endDate = $_GET['end'];
        $stateQuote = $_GET['stateQuote'];
        $quotes_array = [];
        $dates = '';

        if ( $startDate == "" || $endDate == "" )
        {
            $dates = "REPORTE GENERAL DE COTIZACIONES";
            $dateCurrent = Carbon::now('America/Lima');
            $date4MonthAgo = $dateCurrent->subMonths(6);
            $query = Quote::with('customer', 'deadline', 'users')
                /*->where('created_at', '>=', $date4MonthAgo)*/
                ->orderBy('created_at', 'DESC');
        } else {
            $dates = "REPORTE GENERAL DE COTIZACIONES DESDE ".$startDate." AL ".$endDate;
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        /*case 'VB_finance':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('vb_finances', 1)
                                ->whereNull('vb_operations');
                            break;*/
                        case 'VB_operation':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                /*->where('vb_finances', 1)*/
                                ->where('vb_operations', 1);
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close')
                                ->where(function ($q2) {
                                    $q2->where('vb_operations', null);
                                });
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        $quotes = $query->get();

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = 'Enviado';
                } else {
                    $state = 'created';
                    $stateText = 'Creada';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = 'Finalizada';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){
                    if ( $quote->vb_finances == 1 && $quote->vb_operations == null )
                    {
                        $state = 'raise';
                        $stateText = 'Elevada';

                    } else {
                        if ( /*$quote->vb_finances == 1 &&*/ $quote->vb_operations == 1 )
                        {
                            $state = 'VB_operation';
                            $stateText = 'V.B. Operaciones - '.$quote->date_vb_operations->format("d/m/Y");
                        } else {
                            if ( $quote->vb_operations == 0 || $quote->vb_operations == null )
                            {
                                $state = 'raise';
                                $stateText = 'Elevada';
                            }
                        }
                    }
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  'Confirmada';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = 'Cancelada';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = 'Mostrar';
            } else {
                $stateDecimals = 'Ocultar';
            }

            array_push($quotes_array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => round($quote->total_quote/1.18, 0),
                "total" => round($quote->total_quote/1, 0),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "send_state" => $quote->send_state
            ]);
        }

        return (new QuotesExcelDownload($quotes_array, $dates))->download('listadoCotizaciones.xlsx');

    }

    public function printQuoteToCustomer($id)
    {
        app(\App\Services\Quotes\QuoteBranchContext::class)->assertQuote($id, true);

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('users')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'workforces', 'turnstiles']);
            }])->firstOrFail();

        $images = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'img')
            ->orderBy('order', 'ASC')->get();

        $view = view('exports.quoteCustomer', compact('quote', 'images'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

        $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

        return $this->streamQuoteWithAttachments($quote, $pdf, $name);
    }

    public function printQuoteToInternal($id)
    {
        app(\App\Services\Quotes\QuoteBranchContext::class)->assertQuote($id, true);

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('users')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'workforces', 'turnstiles']);
            }])->firstOrFail();

        $images = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'img')
            ->orderBy('order', 'ASC')->get();

        $view = view('exports.quoteInternal', compact('quote', 'images'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

        $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

        return $this->streamQuoteWithAttachments($quote, $pdf, $name);
    }

    private function streamQuoteWithAttachments(Quote $quote, $pdf, string $name)
    {
        $attachments = ImagesQuote::where('quote_id', $quote->id)->where('type', 'pdf')->get();
        if ($attachments->isEmpty()) {
            return $pdf->stream($name);
        }

        $temporary = tempnam(sys_get_temp_dir(), 'venti-quote-');
        if ($temporary === false) {
            throw new \RuntimeException('No se pudo preparar el PDF de la cotización.');
        }

        try {
            $content = $pdf->output();
            if (file_put_contents($temporary, $content) !== strlen($content)) {
                throw new \RuntimeException('No se pudo guardar el PDF temporal de la cotización.');
            }

            $merger = PDFMerger::init();
            $merger->addPDF($temporary, 'all');

            foreach ($attachments as $attachment) {
                $merger->addPDF(public_path('images/planos/'.$attachment->image), 'all');
            }

            $merger->merge();

            return response($merger->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => (new \Symfony\Component\HttpFoundation\ResponseHeaderBag())
                    ->makeDisposition('inline', $name, 'cotizacion.pdf'),
            ]);
        } finally {
            unlink($temporary);
        }
    }

    public function destroyEquipmentOfQuote($id_equipment, $id_quote)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();
        if ( !$quote_user && !$user->hasRole(['admin','principal', 'logistic']) ) {
            return response()->json(['message' => 'No puede eliminar un equipo que no es de su propiedad'], 422);
        }

        DB::beginTransaction();
        try {

            $output_details= OutputDetail::where('equipment_id', $id_equipment)->get();

            if ( count($output_details) > 0 )
            {
                return response()->json(['message' => 'No se puede eliminar el equipo porque ya tiene salidas'], 422);
            }

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            foreach( $equipment_quote->materials as $material ) {
                $material->delete();
            }
            foreach( $equipment_quote->consumables as $consumable ) {
                $consumable->delete();
            }
            foreach( $equipment_quote->electrics as $electric ) {
                $electric->delete();
            }
            foreach( $equipment_quote->workforces as $workforce ) {
                $workforce->delete();
            }
            foreach( $equipment_quote->turnstiles as $turnstile ) {
                $turnstile->delete();
            }
            foreach( $equipment_quote->workdays as $workday ) {
                $workday->delete();
            }

            $totalDeleted = $equipment_quote->total;

            $totalEquipmentU = $totalDeleted*(($equipment_quote->utility/100)+1);
            $totalEquipmentL = $totalEquipmentU*(($equipment_quote->letter/100)+1);
            $totalEquipmentR = $totalEquipmentL*(($equipment_quote->rent/100)+1);

            $quote->total = $quote->total - $totalEquipmentR;

            $quote->currency_invoice = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();

            $equipment_quote->delete();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Eliminar equipo de cotizacion',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Equipo eliminada con éxito.'], 200);

    }

    public function updateEquipmentOfQuote(Request $request, $id_equipment, $id_quote)
    {
        //dump($request);
        //dd();
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();
        /*if ( !$quote_user && !$user->hasRole(['admin','principal', 'logistic']) ) {
            return response()->json(['message' => 'No puede editar un equipo que no es de su propiedad'], 422);
        }*/

        $equipmentSent = null;

        DB::beginTransaction();
        try {
            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            $output_details= OutputDetail::where('equipment_id', $equipment_quote->id)->get();

            if ( count($output_details) == 0 )
            {
                // TODO: Si no hay outputs details que proceda como estaba planificado
                //$totalDeleted = 0;
                foreach( $equipment_quote->materials as $material ) {
                    //$totalDeleted = $totalDeleted + (float) $material->total;
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    //$totalDeleted = $totalDeleted + (float) $workforce->total;
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    //$totalDeleted = $totalDeleted + (float) $workday->total;
                    $workday->delete();
                }

                $totalDeleted = $equipment_quote->total;

                $totalEquipmentU = $totalDeleted*(($equipment_quote->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment_quote->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment_quote->rent/100)+1);

                $quote->total = $quote->total - $totalEquipmentR;
                $quote->save();

                $equipment_quote->delete();

                $equipments = $request->input('equipment');

                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'],
                        'detail' => ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'],
                        'quantity' => $equip['quantity'],
                        'utility' => $equip['utility'],
                        'rent' => $equip['rent'],
                        'letter' => $equip['letter'],
                        'total' => $equip['total']
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equip['materials'];

                    $consumables = $equip['consumables'];

                    $electrics = $equip['electrics'];

                    $workforces = $equip['workforces'];

                    $tornos = $equip['tornos'];

                    $dias = $equip['dias'];
                    //dump($materials);
                    foreach ( $materials as $material )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);

                        //$totalMaterial += $equipmentMaterial->total;
                    }

                    foreach ( $consumables as $consumable )
                    {
                        $material = Material::find((int)$consumable['id']);

                        $equipmentConsumable = EquipmentConsumable::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => (int)$consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'total' => (float) $consumable['quantity']*(float) $consumable['price'],
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $electrics as $electricd )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electricd['id'],
                            'quantity' => (float) $electricd['quantity'],
                            'price' => (float) $electricd['price'],
                            'total' => (float) $electricd['quantity']*(float) $electricd['price'],
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $workforces as $workforce )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);

                        //$totalWorkforces += $equipmentWorkforce->total;
                    }

                    foreach ( $tornos as $torno )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);

                        //$totalTornos += $equipmenttornos->total;
                    }

                    foreach ( $dias as $dia )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);

                        //$totalDias += $equipmentdias->total;
                    }

                    $totalEquipo2 = (float)$equip['total'];
                    $totalEquipmentU2 = $totalEquipo2*(($equip['utility']/100)+1);
                    $totalEquipmentL2 = $totalEquipmentU2*(($equip['letter']/100)+1);
                    $totalEquipmentR2 = $totalEquipmentL2*(($equip['rent']/100)+1);

                    $totalQuote = $totalQuote + $totalEquipmentR2;

                    $equipment->total = $totalEquipo2;

                    $equipment->save();

                    $equipmentSent = $equipment;
                }
                $quote->total = $quote->total + $totalQuote;
                $quote->currency_invoice = 'USD';
                $quote->currency_compra = null;
                $quote->currency_venta = null;
                $quote->total_soles = 0;
                $quote->save();
            } else {
                // TODO: Ya no eliminamos el equipo solo lo modificamos
                //$totalDeleted = 0;
                foreach( $equipment_quote->materials as $material ) {
                    //$totalDeleted = $totalDeleted + (float) $material->total;
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    //$totalDeleted = $totalDeleted + (float) $workforce->total;
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    //$totalDeleted = $totalDeleted + (float) $workday->total;
                    $workday->delete();
                }

                $totalDeleted = $equipment_quote->total;

                $totalEquipmentU = $totalDeleted*(($equipment_quote->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment_quote->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment_quote->rent/100)+1);

                $quote->total = $quote->total - $totalEquipmentR;
                $quote->save();

                //$equipment_quote->delete();

                $equipments = $request->input('equipment');

                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment_quote->quote_id = $quote->id;
                    $equipment_quote->description = ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'];
                    $equipment_quote->detail = ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'];
                    $equipment_quote->quantity = $equip['quantity'];
                    $equipment_quote->utility = $equip['utility'];
                    $equipment_quote->rent = $equip['rent'];
                    $equipment_quote->letter = $equip['letter'];
                    $equipment_quote->total = $equip['total'];
                    $equipment_quote->save();
                    /*$equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'],
                        'detail' => ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'],
                        'quantity' => $equip['quantity'],
                        'utility' => $equip['utility'],
                        'rent' => $equip['rent'],
                        'letter' => $equip['letter'],
                        'total' => $equip['total']
                    ]);*/

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equip['materials'];

                    $consumables = $equip['consumables'];

                    $electrics = $equip['electrics'];

                    $workforces = $equip['workforces'];

                    $tornos = $equip['tornos'];

                    $dias = $equip['dias'];
                    //dump($materials);
                    foreach ( $materials as $material )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);

                        //$totalMaterial += $equipmentMaterial->total;
                    }

                    foreach ( $consumables as $consumable )
                    {
                        $material = Material::find((int)$consumable['id']);

                        $equipmentConsumable = EquipmentConsumable::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => (int)$consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'total' => (float) $consumable['quantity']*(float) $consumable['price'],
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $electrics as $electric )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $electric['id'],
                            'quantity' => (float) $electric['quantity'],
                            'price' => (float) $electric['price'],
                            'total' => (float) $electric['quantity']*(float) $electric['price'],
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $workforces as $workforce )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);

                        //$totalWorkforces += $equipmentWorkforce->total;
                    }

                    foreach ( $tornos as $torno )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);

                        //$totalTornos += $equipmenttornos->total;
                    }

                    foreach ( $dias as $dia )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);

                        //$totalDias += $equipmentdias->total;
                    }

                    $totalEquipo2 = (float)$equip['total'];
                    $totalEquipmentU2 = $totalEquipo2*(($equip['utility']/100)+1);
                    $totalEquipmentL2 = $totalEquipmentU2*(($equip['letter']/100)+1);
                    $totalEquipmentR2 = $totalEquipmentL2*(($equip['rent']/100)+1);

                    $totalQuote = $totalQuote + $totalEquipmentR2;

                    $equipment_quote->total = $totalEquipo2;

                    $equipment_quote->save();

                    $equipment = Equipment::where('id', $equipment_quote->id)
                        ->where('quote_id',$quote->id)->first();

                    $equipmentSent = $equipment;
                }
                $quote->total = $quote->total + $totalQuote;
                $quote->currency_invoice = 'USD';
                $quote->currency_compra = null;
                $quote->currency_venta = null;
                $quote->total_soles = 0;
                $quote->save();
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage().' '.$e->getLine()], 422);
        }
        return response()->json(['message' => 'Equipo guardado con éxito.', 'equipment'=>$equipmentSent, 'quote'=>$quote], 200);

    }

    public function raise()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.raise', compact( 'permissions'));
    }

    public function raiseQuote($quote_id, $code)
    {
        $begin = microtime(true);
        $quote = Quote::find($quote_id);

        DB::beginTransaction();
        try {
            if ( !isset( $quote->order_execution ) )
            {
                $all_quotes = Quote::whereNotNull('order_execution')->get();
                $quantity = count($all_quotes) + 1;
                $length = 5;
                $codeOrderExecution = 'OE-'.str_pad($quantity,$length,"0", STR_PAD_LEFT);
                $quote->order_execution = $codeOrderExecution;
                $quote->save();
            }

            $quote->code_customer = $code;
            $quote->raise_status = true;
            $quote->save();

            // TODO: Dar el visto bueno de finanzas automático
            //$quote->order_execution = $codeOrderExecution;
            /*$quote->vb_finances = 1;
            $quote->date_vb_finances = Carbon::now('America/Lima');
            $quote->save();*/
            // TODO: Guardar el pdf interna en el sistema
            $quote = Quote::where('id', $quote->id)
                ->with('customer')
                ->with('deadline')
                ->with(['equipments' => function ($query) {
                    $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles']);
                }])->first();

            $images = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'img')
                ->orderBy('order', 'ASC')->get();

            $view = view('exports.quoteInternal', compact('quote', 'images'));

            $pdf = PDF::loadHTML($view);

            $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

            $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

            $image_path = public_path().'/pdfs/quotes/'.$name;
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $output = $pdf->output();
            file_put_contents(public_path().'/pdfs/quotes/'.$name, $output);

            $pdfPrincipal = public_path().'/pdfs/quotes/'.$name;

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($pdfPrincipal, 'all');

            $pdfs = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'pdf')->get();

            foreach ( $pdfs as $pdf )
            {
                $namePdf = public_path().'/images/planos/'.$pdf->image;
                $oMerger->addPDF($namePdf, 'all');
            }

            $oMerger->merge();
            $oMerger->setFileName($name);
            // Guarda el archivo fusionado en la misma carpeta
            $output_path = public_path('pdfs/quotes/' . $name);
            $oMerger->save($output_path);

            // TODO: Guardar los resumenes
            $resumen = ResumenQuote::create([
                'quote_id' => $quote->id,
                'code' => $quote->code,
                'description_quote' => $quote->description_quote,
                'date_quote' => $quote->date_quote,
                'customer_id' => ($quote->customer_id == null) ? null : $quote->customer_id,
                'customer' => ($quote->customer_id == null) ? "" : $quote->customer->business_name,
                'contact_id' => ($quote->contact_id == null) ? null : $quote->contact_id,
                'contact' => ($quote->contact_id == null) ? "" : $quote->contact->name,
                'total_sin_igv' => round(($quote->total_equipments)/1.18, 2),
                'total_con_igv' => round($quote->total_equipments, 2),
                'total_utilidad_sin_igv' => round(($quote->total_quote)/1.18, 2),
                'total_utilidad_con_igv' => round($quote->total_quote, 2),
                'path_pdf' => $name
            ]);

            foreach ( $quote->equipments as $equipment )
            {
                $resumenEquipment = ResumenEquipment::create([
                    'resumen_quote_id' => $resumen->id,
                    'equipment_id' => $equipment->id,
                    'description' => $equipment->description,
                    'total_materials' => $equipment->total_materials,
                    'total_consumables' => $equipment->total_consumables,
                    'total_electrics' => $equipment->total_electrics,
                    'total_workforces' => $equipment->total_workforces,
                    'total_turnstiles' => $equipment->total_turnstiles,
                    'total_workdays' => $equipment->total_workdays,
                    'quantity' => $equipment->quantity,
                    'total' => round($equipment->subtotal_percentage/1.18, 2),
                    'utility' => $equipment->utility,
                    'letter' => $equipment->letter,
                    'rent' => $equipment->rent
                ]);
            }

            $financeWork = FinanceWork::where('quote_id', $quote->id)->first();

            if ( !isset($financeWork) )
            {
                $financeWork = FinanceWork::create([
                    'quote_id' => $quote->id,
                    'raise_date' => Carbon::now('America/Lima'), // Cuando se eleva la cotizacion debe guardarse este dato
                    'date_delivery' => null,
                    'act_of_acceptance' => 'pending',
                    'state_act_of_acceptance' => null,
                    'advancement' => 'n',
                    'amount_advancement' => 0,
                    'detraction' => null,
                    'invoiced' => 'n',
                    'number_invoice' => null,
                    'month_invoice' => null,
                    'date_issue' => null,
                    'date_admission' => null,
                    'bank_id' => null,
                    'state' => 'pending',
                    'date_paid' => null,
                    'observation' => null
                ]);
            }

            // Crear notificacion
            $notification = Notification::create([
                'content' => $quote->code.' elevada por '.Auth::user()->name,
                'reason_for_creation' => 'raise_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.raise', $quote->id)
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal' , 'logistic' , 'finance'])->get();
            foreach ( $users as $user )
            {
                if ( $user->id != Auth::user()->id )
                {
                    foreach ( $user->roles as $role )
                    {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Elevar cotizacion',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización elevada.'], 200);
    }

    public function getAllQuotesConfirmed()
    {
        $begin = microtime(true);
        $quotes = Quote::with(['customer'])
            ->with('deadline')
            ->with(['users' => function ($query) {
                $query->with(['user']);
            }])
            ->where('state_active','open')
            ->where('state','confirmed')
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener todas las cotizaciones confirmadas',
            'time' => $end
        ]);
        return datatables($quotes)->toJson();
    }

    public function quoteInSoles($id)
    {
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('contact')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        //dump($quote);
        return view('quote.quoteInSoles', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'paymentDeadlines'));
    }

    public function saveQuoteInSoles( Quote $quote )
    {
        $begin = microtime(true);
        $fecha = Carbon::now('America/Lima');
        $fechaFormato = $fecha->format('Y-m-d');

        //$response = $this->getTipoDeCambio($fechaFormato);
        //dump($fechaFormato);
        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
        //dd($tipoCambioSunat->precioCompra);

        $quote->currency_invoice = 'PEN';
        //$quote->currency_compra = (float) $tipoCambioSunat->compra;
        //$quote->currency_venta = (float) $tipoCambioSunat->venta;
        //$quote->total_soles = $quote->total * (float) $tipoCambioSunat->venta;
        $quote->currency_compra = (float) $tipoCambioSunat->precioCompra;
        $quote->currency_venta = (float) $tipoCambioSunat->precioVenta;
        $quote->total_soles = $quote->total * (float) $tipoCambioSunat->precioVenta;
        $quote->save();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Cotizacion cambiada a soles',
            'time' => $end
        ]);
        return response()->json(['total' => $quote->total_soles, 'message'=>'Cotización cambiada a soles'], 200);

    }

    public function adjustQuote(Request $request)
    {
        //dump($request);
        DB::beginTransaction();
        try {
            $quote = Quote::find($request->get('quote_id'));

            $quote->utility = ($request->has('utility')) ? $request->get('utility'): 0;
            $quote->letter = ($request->has('letter')) ? $request->get('letter'): 0;
            $quote->rent = ($request->has('taxes')) ? $request->get('taxes'): 0;
            $quote->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ajuste de porcentajes realizado con éxito.'], 200);

    }

    public function deleted()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.delete', compact( 'permissions'));
    }

    public function getAllQuotesDeleted()
    {
        $begin = microtime(true);
        $quotes = Quote::with(['customer'])
            ->with('deadline')
            ->with(['users' => function ($query) {
                $query->with(['user']);
            }])
            ->whereIn('state',['canceled', 'expired'])
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener todas las cotizaciones eliminadas',
            'time' => $end
        ]);
        return datatables($quotes)->toJson();
    }

    public function getAllQuotesClosed()
    {
        $begin = microtime(true);
        $quotes = Quote::with(['customer'])
            ->with('deadline')
            ->with(['users' => function ($query) {
                $query->with(['user']);
            }])
            ->whereIn('state_active',['close'])
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener todas las cotizaciones terminadas',
            'time' => $end
        ]);
        return datatables($quotes)->toJson();
    }

    public function closeQuote($quote_id)
    {
        $begin = microtime(true);
        $quote = Quote::find($quote_id);

        $quote->state_active = 'close';
        $quote->save();

        $financeWork = FinanceWork::where('quote_id', $quote->id)->first();

        if ( isset($financeWork) )
        {
            $financeWork->date_delivery = Carbon::now('America/Lima');
            $financeWork->save();
        }

        DB::beginTransaction();
        try {

            $material_takens = MaterialTaken::where('quote_id', $quote_id)->get();

            foreach ( $material_takens as $material_taken )
            {
                $material_taken->delete();
            }
            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Finalizar cotizaciones',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización finalizada con éxito. Redireccionando ...', 'url'=>route('quote.closed')], 200);

    }

    public function closed()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.close', compact( 'permissions'));
    }

    public function renewQuote($id)
    {
        $begin = microtime(true);
        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        //dump($quote);

        DB::beginTransaction();
        try {
            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;
            //$codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $renew_quote = Quote::create([
                'code' => '',
                'description_quote' => $quote->description_quote,
                'observations' => $quote->observations,
                'date_quote' => Carbon::now(),
                'date_validate' => Carbon::now()->addDays(5),
                'way_to_pay' => $quote->way_to_pay,
                'delivery_time' => $quote->delivery_time,
                'customer_id' => $quote->customer_id,
                'state' => 'created',
                'utility' => $quote->utility,
                'letter' => $quote->letter,
                'rent' => $quote->rent,
            ]);

            $codeQuote = '';
            if ( $maxId < $renew_quote->id ){
                $codeQuote = 'COT-'.str_pad($renew_quote->id,$length,"0", STR_PAD_LEFT);
                $renew_quote->code = $codeQuote;
                $renew_quote->save();
            } else {
                $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $renew_quote->code = $codeQuote;
                $renew_quote->save();
            }

            QuoteUser::create([
                'quote_id' => $renew_quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $totalQuote = 0;

            foreach ( $quote->equipments as $equipment )
            {
                $renew_equipment = Equipment::create([
                    'quote_id' => $renew_quote->id,
                    'description' => $equipment->description,
                    'detail' => $equipment->detail,
                    'quantity' => $equipment->quantity,
                    'utility' => $equipment->utility,
                    'rent' => $equipment->rent,
                    'letter' => $equipment->letter,
                ]);

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalElectric = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                foreach ( $equipment->materials as $material )
                {
                    if ( $material->replacement == 0 && $material->original == 1 )
                    {
                        $renew_equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $renew_equipment->id,
                            'material_id' => $material->material->id,
                            'quantity' => (float) $material->quantity,
                            'price' => (float) $material->material->unit_price,
                            'length' => (float) $material->length,
                            'width' => (float) $material->width,
                            'percentage' => (float) $material->percentage,
                            'state' => ($material->quantity > $material->material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ($material->quantity > $material->material->stock_current) ? 'Agotado':'Completo',
                            'total' => (float) $material->quantity*(float) $material->material->unit_price,
                        ]);

                        $totalMaterial += $renew_equipmentMaterial->total;
                    }

                }

                foreach ( $equipment->consumables as $consumable )
                {
                    $material = Material::find($consumable->material_id);

                    $renew_equipmentConsumable = EquipmentConsumable::create([
                        'equipment_id' => $renew_equipment->id,
                        'material_id' => $material->id,
                        'quantity' => (float) $consumable->quantity,
                        'price' => (float) $material->unit_price,
                        'total' => (float) $consumable->quantity*$material->unit_price,
                        'state' => ((float) $consumable->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ((float) $consumable->quantity > $material->stock_current) ? 'Agotado':'Completo',
                    ]);

                    $totalConsumable += $renew_equipmentConsumable->total;
                }

                foreach ( $equipment->electrics as $electric )
                {
                    $renew_equipmentElectric = EquipmentElectric::create([
                        'equipment_id' => $renew_equipment->id,
                        'material_id' => $electric->material_id,
                        'quantity' => (float) $electric->quantity,
                        'price' => (float) $electric->unit_price,
                        'total' => (float) $electric->quantity*$electric->unit_price,
                    ]);

                    $totalElectric += $renew_equipmentElectric->total;
                }

                foreach ( $equipment->workforces as $workforce )
                {
                    $renew_equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $renew_equipment->id,
                        'description' => $workforce->description,
                        'price' => (float) $workforce->price,
                        'quantity' => (float) $workforce->quantity,
                        'total' => (float) $workforce->total,
                        'unit' => $workforce->unit,
                    ]);

                    $totalWorkforces += $renew_equipmentWorkforce->total;
                }

                foreach ( $equipment->turnstiles as $turnstile )
                {
                    $renew_equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $renew_equipment->id,
                        'description' => $turnstile->description,
                        'price' => (float) $turnstile->price,
                        'quantity' => (float) $turnstile->quantity,
                        'total' => (float) $turnstile->total
                    ]);

                    $totalTornos += $renew_equipmenttornos->total;
                }

                foreach ( $equipment->workdays as $workday )
                {
                    $renew_equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $renew_equipment->id,
                        'description' => $workday->description,
                        'quantityPerson' => (float) $workday->quantityPerson,
                        'hoursPerPerson' => (float) $workday->hoursPerPerson,
                        'pricePerHour' => (float) $workday->pricePerHour,
                        'total' => (float) $workday->total
                    ]);

                    $totalDias += $renew_equipmentdias->total;
                }

                $totalQuote += (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos) * (float)$renew_equipment->quantity)+ $totalDias;

                $renew_equipment->total = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos)* (float)$renew_equipment->quantity) + $totalDias;

                $renew_equipment->save();
            }

            $renew_quote->total = $totalQuote;

            $renew_quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Renovar cotizaciones',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización renovada con éxito. Redireccionando ...', 'url'=>route('quote.edit', $renew_quote->id)], 200);

    }

    public function getContactsByCustomer($customer_id)
    {
        $contacts = ContactName::where('customer_id', $customer_id)->get();
        $array = [];
        foreach ( $contacts as $contact )
        {
            array_push($array, ['id'=> $contact->id, 'contact' => $contact->name]);
        }

        //dd($array);
        return $array;
    }

    // Reemplazar materiales en cotizaciones
    public function replacement( $id )
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        //dump($quote);
        return view('quote.replacement', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'paymentDeadlines', 'permissions'));

    }

    public function saveEquipmentMaterialReplacement( $quote, $equipment, $equipmentMaterial )
    {
        DB::beginTransaction();
        try {
            $em = EquipmentMaterial::find($equipmentMaterial);
            $em->replacement = true;
            $em->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'El material ha sido quitado'], 200);
    }

    public function saveEquipmentMaterialNotReplacement( $quote, $equipment, $equipmentMaterial )
    {
        DB::beginTransaction();
        try {
            $em = EquipmentMaterial::find($equipmentMaterial);
            $em->replacement = false;
            $em->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'El reemplazo ha sido anulado'], 200);
    }

    public function changePercentagesEquipment( Request $request , $id_equipment, $id_quote )
    {
        DB::beginTransaction();
        try {
            $equipment = Equipment::find($id_equipment);
            $equipment->utility = $request->input('utility');
            $equipment->rent = $request->input('rent');
            $equipment->letter = $request->input('letter');
            $equipment->save();

            // TODO: Actualizar la cotizacion

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Porcentages actualizados'], 200);

    }

    public function adjustPercentagesEquipment( Request $request , $id_equipment, $id_quote )
    {
        DB::beginTransaction();
        try {
            $quote = Quote::find($id_quote);

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$id_quote)->first();

            $totalDeleted = $equipment_quote->total;

            $totalEquipmentU = $totalDeleted*(($equipment_quote->utility/100)+1);
            $totalEquipmentL = $totalEquipmentU*(($equipment_quote->letter/100)+1);
            $totalEquipmentR = $totalEquipmentL*(($equipment_quote->rent/100)+1);

            $quote->total = $quote->total - $totalEquipmentR;
            $quote->save();

            $utility = (float) $request->input('utility');
            $rent = (float) $request->input('rent');
            $letter = (float) $request->input('letter');

            $totalNew = $equipment_quote->total;

            $totalEquipmentUNew = $totalNew*(($utility/100)+1);
            $totalEquipmentLNew = $totalEquipmentUNew*(($rent/100)+1);
            $totalEquipmentRNew = $totalEquipmentLNew*(($letter/100)+1);

            $quote->total = $quote->total + $totalEquipmentRNew;
            $quote->total_soles = ($quote->total_soles != null || $quote->total_soles != 0) ? $quote->total*$quote->currency_venta:0;
            $quote->save();

            $equipment_quote->utility = $request->input('utility');
            $equipment_quote->rent = $request->input('rent');
            $equipment_quote->letter = $request->input('letter');
            $equipment_quote->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Porcentages actualizados'], 200);

    }

    public function finishEquipmentsQuote( $id )
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();
        //dump($quote);
        return view('quote.finish_equipment', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'paymentDeadlines', 'permissions'));

    }

    public function saveFinishEquipmentsQuote($id_equipment, $id_quote)
    {
        DB::beginTransaction();
        try {
            $e = Equipment::find($id_equipment);
            $e->finished = true;
            $e->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'El equipo se ha finalizado con éxito'], 200);

    }

    public function saveEnableEquipmentsQuote($id_equipment, $id_quote)
    {
        DB::beginTransaction();
        try {
            $e = Equipment::find($id_equipment);
            $e->finished = false;
            $e->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'El equipo se ha habilitado con éxito'], 200);

    }

    public function getAllQuoteLost()
    {
        $begin = microtime(true);
        $quotes = Quote::pluck('code')->toArray();
        //dump($orders);
        $ids = [];
        for ($i=0; $i< count($quotes); $i++)
        {
            $id = (int) substr( $quotes[$i], 4 );
            array_push($ids, $id);
        }
        //dump($ids);
        $lost = [];
        $iterator = 1;
        for ( $j=0; $j< count($ids); $j++ )
        {
            while( $iterator < $ids[$j] )
            {
                $codeQuote = 'COT-'.str_pad($iterator,5,"0", STR_PAD_LEFT);
                array_push($lost, ['code'=>$codeQuote]);
                $iterator++;
            }
            $iterator++;
        }
        //dd($lost);
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener cotizaciones perdidas',
            'time' => $end
        ]);

        return datatables($lost)->toJson();
    }

    public function indexQuoteLost()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('quote.indexLost', compact('permissions'));
    }

    public function saveMaterialsReplacementToEquipment(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);
        DB::beginTransaction();
        try {
            $equipments = json_decode($request->get('equipments'));

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {

                $materials = $equipments[$i]->materials;

                //$consumables = $equipments[$i]->consumables;

                //$workforces = $equipments[$i]->workforces;

                //$tornos = $equipments[$i]->tornos;

                //$dias = $equipments[$i]->dias;

                for ( $j=0; $j<sizeof($materials); $j++ )
                {
                    if ( $materials[$j]->replacement === 'replacement' )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipments[$i]->id,
                            'material_id' => $materials[$j]->material->id,
                            'quantity' => (float)$materials[$j]->quantity,
                            'price' => (float)$materials[$j]->material->unit_price,
                            'length' => (float)($materials[$j]->length == '') ? 0 : $materials[$j]->length,
                            'width' => (float)($materials[$j]->width == '') ? 0 : $materials[$j]->width,
                            'percentage' => (float)$materials[$j]->quantity,
                            'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar' : 'En compra',
                            'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado' : 'Completo',
                            'total' => (float)$materials[$j]->quantity * (float)$materials[$j]->material->unit_price,
                            'original' => false
                        ]);
                    }

                }

                /*for ( $k=0; $k<sizeof($consumables); $k++ )
                {
                    $material = Material::find($consumables[$k]->id);

                    $equipmentConsumable = EquipmentConsumable::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $consumables[$k]->id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'price' => (float) $consumables[$k]->price,
                        'total' => (float) $consumables[$k]->total,
                        'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado':'Completo',
                    ]);

                    $totalConsumable += $equipmentConsumable->total;
                }

                for ( $w=0; $w<sizeof($workforces); $w++ )
                {
                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description,
                        'price' => (float) $workforces[$w]->price,
                        'quantity' => (float) $workforces[$w]->quantity,
                        'total' => (float) $workforces[$w]->total,
                        'unit' => $workforces[$w]->unit,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                for ( $r=0; $r<sizeof($tornos); $r++ )
                {
                    $equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $equipment->id,
                        'description' => $tornos[$r]->description,
                        'price' => (float) $tornos[$r]->price,
                        'quantity' => (float) $tornos[$r]->quantity,
                        'total' => (float) $tornos[$r]->total
                    ]);

                    $totalTornos += $equipmenttornos->total;
                }

                for ( $d=0; $d<sizeof($dias); $d++ )
                {
                    $equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $equipment->id,
                        'description' => $dias[$d]->description,
                        'quantityPerson' => (float) $dias[$d]->quantity,
                        'hoursPerPerson' => (float) $dias[$d]->hours,
                        'pricePerHour' => (float) $dias[$d]->price,
                        'total' => (float) $dias[$d]->total
                    ]);

                    $totalDias += $equipmentdias->total;
                }*/

            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar Materiales Reemplazados',
                'time' => $end
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'El material se ha guardado correctamente'], 200);

    }

    public function activeQuote($id)
    {
        DB::beginTransaction();
        try {
            $quote = Quote::find($id);

            $quote->state_active = 'open';
            $quote->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización activada con éxito.'], 200);

    }

    public function deselevarQuote($id)
    {
        DB::beginTransaction();
        try {
            $quote = Quote::find($id);

            //$quote->order_execution = $codeOrderExecution;
            $quote->code_customer = '';
            $quote->state = 'created';
            $quote->raise_status = false;

            // TODO: Acciones extras para que funcione en otros estados
            $quote->vb_finances = null;
            $quote->date_vb_finances = null;
            $quote->vb_operations = null;
            $quote->date_vb_operations = null;

            $quote->save();

            // TODO: Acciones para borrar los resumenes
            $resumen = ResumenQuote::where('quote_id', $quote->id)->first();
            if ( isset($resumen) )
            {
                foreach ( $resumen->details as $resumenEquipment )
                {
                    $resumenEquipment->delete();
                }
                $resumen->delete();
            }

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización regresada a enviado con éxito.'], 200);

    }

    public function getDetractionQuote($quote_id)
    {
        $financeWork = FinanceWork::where('quote_id', $quote_id)->first();

        if (isset($financeWork))
        {
            $detraction = $financeWork->detraction;
        } else {
            $detraction = 'nn';
        }

        return response()->json(["detraction" => $detraction]);
    }

    public function changeDetractionQuote(Request $request)
    {
        $quote_id = $request->input('quote_id');
        DB::beginTransaction();
        try {
            $financeWork = FinanceWork::where('quote_id', $quote_id)->first();
            $financeWork->detraction = ($request->input('detraction') == 'nn' || $request->input('detraction') == '') ? null: $request->input('detraction');
            $financeWork->save();

            // TODO: Actualizar la cotizacion

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Guardado con éxito'], 200);
    }

    public function getDecimalsQuote($quote_id)
    {
        $quote = Quote::find($quote_id);

        $decimals = ($quote->state_decimals == 1) ? 1 : 0;

        return response()->json(["decimals" => $decimals]);
    }

    public function changeDecimalsQuote(Request $request)
    {
        $quote_id = $request->input('quote_id');
        DB::beginTransaction();
        try {
            $quote = Quote::find($quote_id);
            $quote->state_decimals = ($request->input('decimals') == 0 || $request->input('decimals') == '') ? 0: 1;
            $quote->save();

            // TODO: Actualizar la cotizacion

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Guardado con éxito'], 200);
    }

    public function vistoBuenoFinancesQuote($quote_id)
    {
        DB::beginTransaction();
        try {
            $quote = Quote::find($quote_id);

            //$quote->order_execution = $codeOrderExecution;
            $quote->vb_finances = 1;
            $quote->date_vb_finances = Carbon::now('America/Lima');
            $quote->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Visto bueno de finanzas guardado.'], 200);

    }

    public function vistoBuenoOperationsQuote($quote_id)
    {
        DB::beginTransaction();
        try {
            $quote = Quote::find($quote_id);

            //$quote->order_execution = $codeOrderExecution;
            $quote->vb_operations = 1;
            $quote->date_vb_operations = Carbon::now('America/Lima');
            $quote->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Visto bueno de operaciones guardado.'], 200);

    }

    public function modificarListaMateriales($quote_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();
        $quote = Quote::where('id', $quote_id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Quote::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($quote_id)->value('payment_deadline_id'))->get();
        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        //dd($array);

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
                'type_scrap' => $material->typeScrap,
                'stock_current' => $material->stock_current,
                'unit_price' => $material->unit_price,
                'unit' => $material->unitMeasure->name,
                'code' => $material->code,
                'unit_measure' => $material->unitMeasure,
                'typescrap_id' => $material->typescrap_id
            ]);
        }
        //dump($quote);
        return view('quote.editList', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'paymentDeadlines', 'permissions', 'utility', 'rent', 'letter', 'array'));

    }

    public function updateListEquipmentOfQuote(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);

        $equipmentSent = null;

        DB::beginTransaction();
        try {
            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            // TODO: Ya no eliminamos el equipo solo lo modificamos
            //$totalDeleted = 0;
            foreach( $equipment_quote->materials as $material ) {
                //$totalDeleted = $totalDeleted + (float) $material->total;
                $material->delete();
            }
            foreach( $equipment_quote->consumables as $consumable ) {
                //$totalDeleted = $totalDeleted + (float) $consumable->total;
                $consumable->delete();
            }
            foreach( $equipment_quote->electrics as $electric ) {
                //$totalDeleted = $totalDeleted + (float) $consumable->total;
                $electric->delete();
            }
            foreach( $equipment_quote->workforces as $workforce ) {
                //$totalDeleted = $totalDeleted + (float) $workforce->total;
                $workforce->delete();
            }
            foreach( $equipment_quote->turnstiles as $turnstile ) {
                //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                $turnstile->delete();
            }
            foreach( $equipment_quote->workdays as $workday ) {
                //$totalDeleted = $totalDeleted + (float) $workday->total;
                $workday->delete();
            }

            $equipments = $request->input('equipment');

            $totalQuote = 0;

            foreach ( $equipments as $equip )
            {
                $equipment_quote->quote_id = $quote->id;
                $equipment_quote->description = ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'];
                $equipment_quote->detail = ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'];
                $equipment_quote->quantity = $equip['quantity'];
                //$equipment_quote->utility = $equip['utility'];
                //$equipment_quote->rent = $equip['rent'];
                //$equipment_quote->letter = $equip['letter'];
                //$equipment_quote->total = $equip['total'];
                $equipment_quote->save();

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                $materials = $equip['materials'];

                $consumables = $equip['consumables'];

                $electrics = $equip['electrics'];

                $workforces = $equip['workforces'];

                $tornos = $equip['tornos'];

                $dias = $equip['dias'];
                //dump($materials);
                foreach ( $materials as $material )
                {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment_quote->id,
                        'material_id' => (int)$material['material']['id'],
                        'quantity' => (float) $material['quantity'],
                        'price' => (float) $material['material']['unit_price'],
                        'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                        'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                        'percentage' => (float) $material['quantity'],
                        'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                        'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                        'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                    ]);

                    //$totalMaterial += $equipmentMaterial->total;
                }

                foreach ( $consumables as $consumable )
                {
                    $material = Material::find((int)$consumable['id']);

                    $equipmentConsumable = EquipmentConsumable::create([
                        'equipment_id' => $equipment_quote->id,
                        'material_id' => (int)$consumable['id'],
                        'quantity' => (float) $consumable['quantity'],
                        'price' => (float) $consumable['price'],
                        'total' => (float) $consumable['quantity']*(float) $consumable['price'],
                        'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                    ]);

                    //$totalConsumable += $equipmentConsumable->total;
                }

                foreach ( $electrics as $electric )
                {
                    $equipmentElectric = EquipmentElectric::create([
                        'equipment_id' => $equipment_quote->id,
                        'material_id' => (int)$electric['id'],
                        'quantity' => (float) $electric['quantity'],
                        'price' => (float) $electric['price'],
                        'total' => (float) $electric['quantity']*(float) $electric['price'],
                    ]);

                    //$totalConsumable += $equipmentConsumable->total;
                }

                foreach ( $workforces as $workforce )
                {
                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment_quote->id,
                        'description' => $workforce['description'],
                        'price' => (float) $workforce['price'],
                        'quantity' => (float) $workforce['quantity'],
                        'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                        'unit' => $workforce['unit'],
                    ]);

                    //$totalWorkforces += $equipmentWorkforce->total;
                }

                foreach ( $tornos as $torno )
                {
                    $equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $equipment_quote->id,
                        'description' => $torno['description'],
                        'price' => (float) $torno['price'],
                        'quantity' => (float) $torno['quantity'],
                        'total' => (float) $torno['price']*(float) $torno['quantity']
                    ]);

                    //$totalTornos += $equipmenttornos->total;
                }

                foreach ( $dias as $dia )
                {
                    $equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $equipment_quote->id,
                        'description' => $dia['description'],
                        'quantityPerson' => (float) $dia['quantity'],
                        'hoursPerPerson' => (float) $dia['hours'],
                        'pricePerHour' => (float) $dia['price'],
                        'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                    ]);

                    //$totalDias += $equipmentdias->total;
                }

                //$totalEquipo2 = (float)$equip['total'];
                //$totalEquipmentU2 = $totalEquipo2*(($equip['utility']/100)+1);
                //$totalEquipmentL2 = $totalEquipmentU2*(($equip['letter']/100)+1);
                //$totalEquipmentR2 = $totalEquipmentL2*(($equip['rent']/100)+1);

                //$totalQuote = $totalQuote + $totalEquipmentR2;

                //$equipment_quote->total = $totalEquipo2;

                //$equipment_quote->save();

                $equipment = Equipment::where('id', $equipment_quote->id)
                    ->where('quote_id',$quote->id)->first();

                $equipmentSent = $equipment;
            }
            //$quote->total = $quote->total + $totalQuote;
            //$quote->currency_invoice = 'USD';
            //$quote->currency_compra = null;
            //$quote->currency_venta = null;
            //$quote->total_soles = 0;
            //$quote->save();


            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar lista de materiales de equipo de cotizacion',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Equipo guardado con éxito.', 'equipment'=>$equipmentSent, 'quote'=>$quote], 200);

    }

    public function destroyListEquipmentOfQuote($id_equipment, $id_quote)
    {
        $begin = microtime(true);
        $quote = Quote::find($id_quote);

        DB::beginTransaction();
        try {

            $output_details= OutputDetail::where('equipment_id', $id_equipment)->get();

            if ( count($output_details) > 0 )
            {
                return response()->json(['message' => 'No se puede eliminar el equipo porque ya tiene salidas'], 422);
            }

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            foreach( $equipment_quote->materials as $material ) {
                $material->delete();
            }
            foreach( $equipment_quote->consumables as $consumable ) {
                $consumable->delete();
            }
            foreach( $equipment_quote->electrics as $electric ) {
                $electric->delete();
            }
            foreach( $equipment_quote->workforces as $workforce ) {
                $workforce->delete();
            }
            foreach( $equipment_quote->turnstiles as $turnstile ) {
                $turnstile->delete();
            }
            foreach( $equipment_quote->workdays as $workday ) {
                $workday->delete();
            }

            /*$totalDeleted = $equipment_quote->total;

            $totalEquipmentU = $totalDeleted*(($equipment_quote->utility/100)+1);
            $totalEquipmentL = $totalEquipmentU*(($equipment_quote->letter/100)+1);
            $totalEquipmentR = $totalEquipmentL*(($equipment_quote->rent/100)+1);

            $quote->total = $quote->total - $totalEquipmentR;

            $quote->currency_invoice = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();*/

            //$equipment_quote->delete();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Eliminar equipo de cotizacion',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Equipo eliminado con éxito.'], 200);

    }

    public function updateList(UpdateQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $quote = Quote::find($request->get('quote_id'));

            $quote->code = $request->get('code_quote');
            $quote->description_quote = $request->get('code_description');
            $quote->date_quote = ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now();
            $quote->date_validate = ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5);
            $quote->way_to_pay = ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '';
            $quote->payment_deadline_id = ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null;
            $quote->delivery_time = ($request->has('delivery_time')) ? $request->get('delivery_time') : '';
            $quote->customer_id = ($request->has('customer_id')) ? $request->get('customer_id') : null;
            $quote->contact_id = ($request->has('contact_id')) ? $request->get('contact_id') : null;
            //$quote->utility = ($request->has('utility')) ? $request->get('utility'): 0;
            //$quote->letter = ($request->has('letter')) ? $request->get('letter'): 0;
            //$quote->rent = ($request->has('taxes')) ? $request->get('taxes'): 0;
            //$quote->currency_invoice = 'USD';
            //$quote->currency_compra = null;
            //$quote->currency_venta = null;
            //$quote->total_soles = 0;
            $quote->save();

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                if ($equipments[$i]->quote === '' )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                        'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                        'quantity' => $equipments[$i]->quantity,
                        'utility' => $equipments[$i]->utility,
                        'rent' => $equipments[$i]->rent,
                        'letter' => $equipments[$i]->letter,
                        'total' => $equipments[$i]->total
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equipments[$i]->materials;

                    $consumables = $equipments[$i]->consumables;

                    $electrics = $equipments[$i]->electrics;

                    $workforces = $equipments[$i]->workforces;

                    $tornos = $equipments[$i]->tornos;

                    $dias = $equipments[$i]->dias;

                    for ( $j=0; $j<sizeof($materials); $j++ )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $materials[$j]->material->id,
                            'quantity' => (float) $materials[$j]->quantity,
                            'price' => (float) $materials[$j]->material->unit_price,
                            'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                            'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                            'percentage' => (float) $materials[$j]->quantity,
                            'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                            'total' => (float) $materials[$j]->material->unit_price*(float) $materials[$j]->quantity,
                        ]);

                        $totalMaterial += $equipmentMaterial->total;
                    }

                    for ( $k=0; $k<sizeof($consumables); $k++ )
                    {
                        $material = Material::find($consumables[$k]->id);

                        $equipmentConsumable = EquipmentConsumable::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumables[$k]->id,
                            'quantity' => (float) $consumables[$k]->quantity,
                            'price' => (float) $consumables[$k]->price,
                            'total' => (float) $consumables[$k]->quantity*(float) $consumables[$k]->price,
                            'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado':'Completo',
                        ]);

                        $totalConsumable += $equipmentConsumable->total;
                    }

                    for ( $e=0; $e<sizeof($electrics); $e++ )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electrics[$e]->id,
                            'quantity' => (float) $electrics[$e]->quantity,
                            'price' => (float) $electrics[$e]->price,
                            'total' => (float) $electrics[$e]->quantity*(float) $electrics[$e]->price,
                        ]);

                        $totalElectric += $equipmentElectric->total;
                    }

                    for ( $w=0; $w<sizeof($workforces); $w++ )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforces[$w]->description,
                            'price' => (float) $workforces[$w]->price,
                            'quantity' => (float) $workforces[$w]->quantity,
                            'total' => (float) $workforces[$w]->price*(float) $workforces[$w]->quantity,
                            'unit' => $workforces[$w]->unit,
                        ]);

                        $totalWorkforces += $equipmentWorkforce->total;
                    }

                    for ( $r=0; $r<sizeof($tornos); $r++ )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $tornos[$r]->description,
                            'price' => (float) $tornos[$r]->price,
                            'quantity' => (float) $tornos[$r]->quantity,
                            'total' => (float) $tornos[$r]->price*(float) $tornos[$r]->quantity
                        ]);

                        $totalTornos += $equipmenttornos->total;
                    }

                    for ( $d=0; $d<sizeof($dias); $d++ )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dias[$d]->description,
                            'quantityPerson' => (float) $dias[$d]->quantity,
                            'hoursPerPerson' => (float) $dias[$d]->hours,
                            'pricePerHour' => (float) $dias[$d]->price,
                            'total' => (float) $dias[$d]->quantity*(float) $dias[$d]->hours*(float) $dias[$d]->price
                        ]);

                        $totalDias += $equipmentdias->total;
                    }

                    //$totalQuote += ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity;

                    //$equipment->total = ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias)* (float)$equipment->quantity;

                    $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                    $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                    $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                    $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                    $totalQuote += $totalEquipmentR;

                    $equipment->total = $totalEquipo;

                    $equipment->save();
                }

            }

            $quote->total += $totalQuote;

            $quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar cotizaciones POST',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Nuevos equipos guardados con éxito.'], 200);

    }

    public function resumenQuote()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('request_date')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayQuotes = Quote::select('id', 'code', 'description_quote')
            ->where('state', 'confirmed')
            ->where('raise_status', 1)
            /*->where('state_active', '<>','close')*/
            ->orderBy('created_at', 'desc')
            ->get()->toArray();

        return view('quote.resumen_quote_v2', compact('permissions', 'arrayYears', 'arrayQuotes'));

    }

    public function getResumenQuote(Request $request)
    {
        $quote_id = $request->input('quote');

        $quote = Quote::find($quote_id);
        $resumen = ResumenQuote::where('quote_id', $quote_id)->first();

        $equipmentsOfQuote = [];
        $resumenEquipments = [];
        $totalQuote = [];

        if ( isset($resumen) )
        {
            foreach ( $resumen->details as $resumenEquipment )
            {
                $total = $resumenEquipment->total_materials+$resumenEquipment->total_consumables+$resumenEquipment->total_workforces+$resumenEquipment->total_turnstiles+$resumenEquipment->total_workdays;
                $subtotal_sin_igv = round($total/1.18, 2);
                array_push($equipmentsOfQuote, [
                    "equipo" => $resumenEquipment->description,
                    "cantidad" => $resumenEquipment->quantity,
                    //"subtotal_sin_igv" => round(($resumenEquipment->total/$resumenEquipment->quantity)/1.18, 2),
                    "subtotal_sin_igv" => $subtotal_sin_igv,
                    "utilidad" => $resumenEquipment->utility,
                    "gastos_varios" => $resumenEquipment->rent + $resumenEquipment->letter,
                    "precio_unit_sin_igv" => round(($resumenEquipment->total)/$resumenEquipment->quantity, 2),
                    "total_sin_igv" => round($resumenEquipment->total, 2)
                ]);

                array_push($resumenEquipments, [
                    "equipment" => $resumenEquipment->description,
                    "total_materials" => ($resumenEquipment->total_materials == null) ? 0:$resumenEquipment->total_materials,
                    "total_consumables" => ($resumenEquipment->total_consumables == null) ? 0:$resumenEquipment->total_consumables,
                    "total_electrics" => ($resumenEquipment->total_electrics == null) ? 0:$resumenEquipment->total_electrics,
                    "total_workforces" => ($resumenEquipment->total_workforces == null) ? 0:$resumenEquipment->total_workforces,
                    "total_tornos" => ($resumenEquipment->total_turnstiles == null) ? 0:$resumenEquipment->total_turnstiles,
                    "total_dias" => ($resumenEquipment->total_workdays == null) ? 0:$resumenEquipment->total_workdays
                ]);

            }

            $totalQuote = [
                "total_sin_igv" => $quote->currency_invoice." ". $resumen->total_sin_igv,
                "total_con_igv" => $quote->currency_invoice." ". $resumen->total_con_igv,
                "total_utilidad_sin_igv" => $quote->currency_invoice." ". $resumen->total_utilidad_sin_igv,
                "total_utilidad_con_igv" => $quote->currency_invoice." ". $resumen->total_utilidad_con_igv
            ];
        }

        return [
            'equipmentsOfQuote' => $equipmentsOfQuote,
            'resumenEquipments' => $resumenEquipments,
            'totalQuote' => $totalQuote
        ];
    }

    public function getInfoResumenQuote($quote_id)
    {
        $resumen = ResumenQuote::where('quote_id', $quote_id)->first();

        $havePDF = 0;
        if (isset($resumen))
        {
            $pdf = $resumen->path_pdf;
            if ( $pdf == "" || $pdf == null )
            {
                $havePDF = 0;
            } else {
                $havePDF = 1;
            }
        } else {
            $havePDF = 0;
        }

        return response()->json(['havePDF' => $havePDF], 200);
    }

    public function exportPDFMaterialesCotizaciones()
    {
        $quote_id = $_GET['quote_id'];

        $resumen = ResumenQuote::where('quote_id', $quote_id)->first();

        $path_pdf = $resumen->path_pdf;

        $pathComplete = public_path().'/pdfs/quotes/'.$path_pdf;

        if (!file_exists($pathComplete)) {
            // Manejo del caso en que el archivo PDF no existe
            return response()->json(['error' => 'El archivo PDF no existe en la ruta especificada'], 404);
        }

        // Descargar el PDF
        return response()->download($pathComplete, $path_pdf);
    }

    public function getTipoDeCambio($fechaFormato)
    {
        // Datos
        $token = 'apis-token-8672.3WJd-pOe5ZnukRBJR1Sah0sbvPwsijik';
        $fecha = $fechaFormato;

        // Iniciar llamada a API
        /*$curl = curl_init();

        curl_setopt_array($curl, array(
            // para usar la api versión 2
            CURLOPT_URL => 'https://api.apis.net.pe/v2/sbs/tipo-cambio?date=' . $fecha,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Referer: https://apis.net.pe/api-tipo-cambio-sbs.html',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            // Error en la ejecución de cURL
            $errorNo = curl_errno($curl);
            $errorMsg = curl_error($curl);
            curl_close($curl);

            // Manejar el error
            //echo "cURL Error #{$errorNo}: {$errorMsg}";
        } else {
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode >= 200 && $httpCode < 300) {
                // La solicitud fue exitosa
                $data = json_decode($response, true);
                // Manejar la respuesta exitosa
                //echo "Respuesta exitosa: ";
                //print_r($data);
            } else {
                // La solicitud no fue exitosa
                // Decodificar el mensaje de error si la respuesta está en formato JSON
                $errorData = json_decode($response, true);
                //echo "Error en la solicitud: ";
                ///print_r($errorData);
                $response = [
                    "precioCompra"=> 3.738,
                    "precioVenta"=> 3.746,
                    "moneda"=> "USD",
                    "fecha"=> "2024-05-24"
                ];
            }
        }*/
        $response = [
            "precioCompra"=> 3.730,
            "precioVenta"=> 3.739,
            "moneda"=> "USD",
            "fecha"=> "2024-05-24"
        ];

        //curl_close($curl);
        // Datos listos para usar
        $tipoCambioSbs = json_encode($response);
        //var_dump($tipoCambioSbs);
        return $tipoCambioSbs;
    }

    public function obtenerTipoCambio($fechaFormato)
    {
        $tipoCambio = $this->tipoCambioService->obtenerPorFecha($fechaFormato);
        //dump($tipoCambio);
        return $tipoCambio;
    }
}
