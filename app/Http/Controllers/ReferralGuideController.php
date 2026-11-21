<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Exports\GuidesExcelDownload;
use App\Material;
use App\Quote;
use App\ReasonTransfer;
use App\ReferralGuide;
use App\ReferralGuideDetail;
use App\ShippingManager;
use App\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;

class ReferralGuideController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            return app(RetiredReferralGuideController::class)->unavailable($request);
        });
    }

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $customers = Customer::all();
        $suppliers = Supplier::all();

        $registros = ReferralGuide::all();

        $arrayYears = $registros->pluck('date_transfer')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayStates = [
            ["value" => "active", "display" => "ACTIVA"],
            ["value" => "inactive", "display" => "ANULADA"]
        ];

        $responsibles = ShippingManager::with('user')->get();

        $reasons = ReasonTransfer::all();

        return view('referralGuide.index', compact('permissions', 'arrayYears','reasons','responsibles','arrayStates','suppliers', 'customers'));

    }

    public function getDataGuides(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $code = $request->input('code');
        $year = $request->input('year');
        $reason = $request->input('reason');
        $shippingManager = $request->input('responsible');
        $state = $request->input('state');
        $customer = $request->input('customer');
        $supplier = $request->input('supplier');
        $receiver = $request->input('receiver');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $query = ReferralGuide::with('reason', 'customer', 'supplier', 'responsible.user')->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = ReferralGuide::with('reason', 'customer', 'supplier', 'responsible')
                ->whereDate('date_transfer', '>=', $fechaInicio)
                ->whereDate('date_transfer', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($year != "") {
            $query->whereYear('date_transfer', $year);

        }

        if ($reason != "") {
            $query->where('reason_transfer_id', $reason);

        }

        if ($shippingManager != "") {
            $query->where('shipping_manager_id', $shippingManager);

        }

        if ($customer != "") {
            $query->where('customer_id', $customer);

        }

        if ($supplier != "") {
            $query->where('supplier_id', $supplier);

        }

        if ($state != "") {
            if ( $state == 'active' )
            {
                $query->where('enabled_status', 1);
            } elseif ( $state == 'inactive' ) {
                $query->where('enabled_status', 0);
            }
        }

        if ( $receiver != "" ) {
            $query->where('receiver', 'LIKE', '%'.$receiver.'%');
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $referralGuides = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $arrayGuides = [];

        foreach ( $referralGuides as $referralGuide )
        {
            $enabled_status = "";
            if ( $referralGuide->enabled_status == 1 )
            {
                $enabled_status = '<span class="badge bg-primary">Activa</span>';
            } elseif ( $referralGuide->enabled_status == 0 ) {
                $enabled_status = '<span class="badge bg-danger text-white">Anulada</span>';
            }

            $destinatario = "No tiene";
            $documento = "No tiene";
            $punto_llegada = "No tiene";

            if ( $referralGuide->customer_id == null && $referralGuide->supplier_id == null )
            {
                $destinatario = $referralGuide->receiver;
                $documento = $referralGuide->document;
                $punto_llegada = $referralGuide->arrival_point;
            } elseif ( $referralGuide->customer_id != null ) {
                $customer = Customer::find($referralGuide->customer_id);
                $destinatario = $customer->business_name;
                $documento = $customer->RUC;
                $punto_llegada = $customer->address;
            } elseif ( $referralGuide->supplier_id != null ) {
                $supplier = Supplier::find($referralGuide->supplier_id);
                $destinatario = $supplier->business_name;
                $documento = $supplier->RUC;
                $punto_llegada = $supplier->address;
            }

            if ( $referralGuide->reason_transfer_id != null )
            {
                $reasonID = ReasonTransfer::find($referralGuide->reason_transfer_id);
                $reason = $reasonID->description;
            } else {
                $reason = "No tiene";
            }

            if ( $referralGuide->shipping_manager_id != null )
            {
                $responsibleID = ShippingManager::find($referralGuide->shipping_manager_id);
                $responsible = $responsibleID->user->name;
            } else {
                $responsible = "No tiene";
            }

            array_push($arrayGuides, [
                "id" => $referralGuide->id,
                "code" => $referralGuide->code,
                "date_transfer" => ($referralGuide->date_transfer != null) ? $referralGuide->date_transfer->format('d/m/Y') : "",
                "reason" => $reason,
                "destinatario" => $destinatario,
                "documento" => $documento,
                "punto_llegada" => $punto_llegada,
                "vehiculo" => ($referralGuide->placa != null) ? $referralGuide->placa : "No tiene",
                "driver" => ($referralGuide->driver == null) ? 'No tiene': $referralGuide->driver,
                "driver_licence" => ($referralGuide->driver_licence == null) ? 'No tiene': $referralGuide->driver_licence,
                "responsible" => $responsible,
                "enabled_status" => $enabled_status,
                "state" => $referralGuide->enabled_status,
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

        return ['data' => $arrayGuides, 'pagination' => $pagination];
    }

    public function create()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $maxId = ReferralGuide::count('id')+1;
        $length = 5;
        $code = 'GR-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $customers = Customer::all();
        $suppliers = Supplier::all();
        $reasons = ReasonTransfer::all();
        $shippingManagers = ShippingManager::all();

        $materials = Material::where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)->get();
        $quotes = Quote::where('state', 'confirmed')
            ->where('raise_status', 1)
            ->where('state', '<>','canceled')
            ->where('state_active', '<>','close')
            ->orderBy('created_at', 'DESC')->get();
        //dd($quotes->pluck('code'));

        return view('referralGuide.create', compact('suppliers','customers','code', 'permissions', 'reasons', 'shippingManagers', 'materials', 'quotes'));

    }

    public function store(Request $request)
    {
        //$validated = $request->validated();

        //dd($request);
        DB::beginTransaction();
        try {

            // Obtener los datos generales
            $date_transfer = $request->input('date_transfer');
            $reason_id = $request->input('reason_id');
            $destination = $request->input('destination');
            $customer_id = $request->input('customer_id');
            $supplier_id = $request->input('supplier_id');
            $receiver = $request->input('receiver');
            $puntoLlegada = $request->input('puntoLlegada');
            $document = $request->input('document');
            $placa = $request->input('placa');
            $driver = $request->input('driver');
            $driver_licence = $request->input('driver_licence');
            $responsible = $request->input('responsible');

            $maxId = ReferralGuide::count('id')+1;
            $length = 5;
            $code = 'GR-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            // Decodificar los datos JSON
            $rows = json_decode($request->input('rows'), true);

            // Guardar los datos en la base de datos (esto es solo un ejemplo, ajusta según tu estructura)
            $referralGuide = new ReferralGuide();
            $referralGuide->code = $code;
            $referralGuide->date_transfer = ($date_transfer != "" || $date_transfer != null) ? Carbon::createFromFormat('d/m/Y', $date_transfer) : Carbon::now();
            $referralGuide->reason_transfer_id = $reason_id;

            if ($destination == 'Cliente') {
                $referralGuide->customer_id = $customer_id;
            } elseif ( $destination == 'Proveedor' ) {
                $referralGuide->supplier_id = $supplier_id;
            } elseif ( $destination == 'Otros' ) {
                $referralGuide->receiver = $receiver;
                $referralGuide->document = $document;
                $referralGuide->arrival_point = $puntoLlegada;
            }

            $referralGuide->placa = $placa;
            $referralGuide->driver = $driver;
            $referralGuide->driver_licence = $driver_licence;
            $referralGuide->shipping_manager_id = $responsible;
            $referralGuide->enabled_status = 1;
            $referralGuide->save();

            foreach ($rows as $key => $row) {
                if ( $row['type'] == 'material' ) {
                    $eeferralGuideDetail = new ReferralGuideDetail();
                    $eeferralGuideDetail->referral_guide_id = $referralGuide->id;
                    $eeferralGuideDetail->material_id = $row['id'];
                    $eeferralGuideDetail->quantity = $row['quantity'];
                    $eeferralGuideDetail->save();

                } elseif ( $row['type'] == 'quote' ) {
                    $eeferralGuideDetail = new ReferralGuideDetail();
                    $eeferralGuideDetail->referral_guide_id = $referralGuide->id;
                    $eeferralGuideDetail->quote_id = $row['id'];
                    $eeferralGuideDetail->quantity = $row['quantity'];
                    $eeferralGuideDetail->save();

                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Guía de remisión guardada con éxito.'], 200);
    }

    public function show($guide_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $guide = ReferralGuide::find($guide_id);
        $enabled_status = "";
        if ( $guide->enabled_status == 1 )
        {
            $enabled_status = '<span class="badge bg-primary">Activa</span>';
        } elseif ( $guide->enabled_status == 0 ) {
            $enabled_status = '<span class="badge bg-danger text-white">Anulada</span>';
        }

        $destinatario = "No tiene";
        $documento = "No tiene";
        $punto_llegada = "No tiene";

        if ( $guide->customer_id == null && $guide->supplier_id == null )
        {
            $destinatario = $guide->receiver;
            $documento = $guide->document;
            $punto_llegada = $guide->arrival_point;
        } elseif ( $guide->customer_id != null ) {
            $customer = Customer::find($guide->customer_id);
            $destinatario = $customer->business_name;
            $documento = $customer->RUC;
            $punto_llegada = $customer->address;
        } elseif ( $guide->supplier_id != null ) {
            $supplier = Supplier::find($guide->supplier_id);
            $destinatario = $supplier->business_name;
            $documento = $supplier->RUC;
            $punto_llegada = $supplier->address;
        }

        if ( $guide->reason_transfer_id != null )
        {
            $reasonID = ReasonTransfer::find($guide->reason_transfer_id);
            $reason = $reasonID->description;
        } else {
            $reason = "No tiene";
        }

        if ( $guide->shipping_manager_id != null )
        {
            $responsibleID = ShippingManager::find($guide->shipping_manager_id);
            $responsible = $responsibleID->user->name;
        } else {
            $responsible = "No tiene";
        }

        $arrayDetails = [];
        $arrayGuide = [];

        foreach ( $guide->details as $detail )
        {
            $code = "";
            $description = "";
            $unit = "";
            $quantity = "";

            if ( $detail->material_id != null ) {
                $material = Material::find($detail->material_id);
                $code = $material->code;
                $description = $material->full_name;
                $unit = $material->unitMeasure->description;
                $quantity = $detail->quantity;
            } elseif ( $detail->quote_id != null ) {
                $quote = Quote::find($detail->quote_id);
                $code = $quote->code;
                $description = $quote->description_quote;
                $unit = 'UNIDAD';
                $quantity = $detail->quantity;
            }

            array_push($arrayDetails, [
                "code" => $code,
                "description" => $description,
                "unit" => $unit,
                "quantity" => $quantity,
            ]);
        }

        array_push($arrayGuide, [
            "id" => $guide->id,
            "code" => $guide->code,
            "date_transfer" => ($guide->date_transfer != null) ? $guide->date_transfer->format('d/m/Y') : "",
            "reason" => $reason,
            "destinatario" => $destinatario,
            "documento" => $documento,
            "punto_llegada" => $punto_llegada,
            "vehiculo" => ($guide->placa != null) ? $guide->placa : "No tiene",
            "driver" => ($guide->driver == null) ? 'No tiene': $guide->driver,
            "driver_licence" => ($guide->driver_licence == null) ? 'No tiene': $guide->driver_licence,
            "responsible" => $responsible,
            "enabled_status" => $enabled_status,
            "state" => $guide->enabled_status,
            "details" => $arrayDetails
        ]);

        return view('referralGuide.show', compact('permissions','arrayGuide'));

    }

    public function edit($guide_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $guide = ReferralGuide::find($guide_id);
        $enabled_status = "";
        $destination = "";
        if ( $guide->customer_id == null && $guide->supplier_id == null )
        {
            $documento = $guide->document;
            $punto_llegada = $guide->arrival_point;
            $destination = "Otros";
        } elseif ( $guide->customer_id != null ) {
            $customer = Customer::find($guide->customer_id);
            $documento = $customer->RUC;
            $punto_llegada = $customer->address;
            $destination = "Cliente";
        } elseif ( $guide->supplier_id != null ) {
            $supplier = Supplier::find($guide->supplier_id);
            $documento = $supplier->RUC;
            $punto_llegada = $supplier->address;
            $destination = "Proveedor";
        }

        $arrayDetails = [];
        $arrayGuide = [];

        foreach ( $guide->details as $detail )
        {
            $code = "";
            $description = "";
            $unit = "";
            $quantity = "";
            $id = "";
            $type = "";

            if ( $detail->material_id != null ) {
                $material = Material::find($detail->material_id);
                $code = $material->code;
                $description = $material->full_name;
                $unit = $material->unitMeasure->description;
                $quantity = $detail->quantity;
                $id = $material->id;
                $type = "material";
            } elseif ( $detail->quote_id != null ) {
                $quote = Quote::find($detail->quote_id);
                $code = $quote->code;
                $description = $quote->description_quote;
                $unit = 'UNIDAD';
                $quantity = $detail->quantity;
                $id = $quote->id;
                $type = "quote";
            }

            array_push($arrayDetails, [
                "code" => $code,
                "description" => $description,
                "unit" => $unit,
                "quantity" => $quantity,
                "id" => $id,
                "type" => $type,
            ]);
        }

        array_push($arrayGuide, [
            "id" => $guide->id,
            "code" => $guide->code,
            "date_transfer" => ($guide->date_transfer != null) ? $guide->date_transfer->format('d/m/Y') : "",
            "reason_transfer_id" => $guide->reason_transfer_id,
            "destination" => $destination,
            "receiver" => $guide->receiver,
            "customer_id" => $guide->customer_id,
            "supplier_id" => $guide->supplier_id,
            "documento" => $documento,
            "punto_llegada" => $punto_llegada,
            "vehiculo" => ($guide->placa != null) ? $guide->placa : "No tiene",
            "driver" => ($guide->driver == null) ? 'No tiene': $guide->driver,
            "driver_licence" => ($guide->driver_licence == null) ? 'No tiene': $guide->driver_licence,
            "responsible" => $guide->shipping_manager_id,
            "enabled_status" => $guide->enabled_status,
            "details" => $arrayDetails
        ]);

        $customers = Customer::all();
        $suppliers = Supplier::all();
        $reasons = ReasonTransfer::all();
        $shippingManagers = ShippingManager::all();

        $materials = Material::where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)->get();
        $quotes = Quote::where('state', 'confirmed')
            ->where('raise_status', 1)
            ->where('state', '<>','canceled')
            ->where('state_active', '<>','close')
            ->orderBy('created_at', 'DESC')->get();

        return view('referralGuide.edit', compact('permissions', 'quotes', 'materials', 'shippingManagers', 'reasons', 'suppliers','arrayGuide', 'customers'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            // Obtener los datos generales
            $guide_id = $request->input('guide_id');
            $date_transfer = $request->input('date_transfer');
            $reason_id = $request->input('reason_id');
            $destination = $request->input('destination');
            $customer_id = $request->input('customer_id');
            $supplier_id = $request->input('supplier_id');
            $receiver = $request->input('receiver');
            $puntoLlegada = $request->input('puntoLlegada');
            $document = $request->input('document');
            $placa = $request->input('placa');
            $driver = $request->input('driver');
            $driver_licence = $request->input('driver_licence');
            $responsible = $request->input('responsible');

            // Decodificar los datos JSON
            $rows = json_decode($request->input('rows'), true);

            // Guardar los datos en la base de datos (esto es solo un ejemplo, ajusta según tu estructura)
            $referralGuide = ReferralGuide::find($guide_id);
            $referralGuide->date_transfer = ($date_transfer != "" || $date_transfer != null) ? Carbon::createFromFormat('d/m/Y', $date_transfer) : Carbon::now();
            $referralGuide->reason_transfer_id = $reason_id;

            if ($destination == 'Cliente') {
                $referralGuide->customer_id = $customer_id;
            } elseif ( $destination == 'Proveedor' ) {
                $referralGuide->supplier_id = $supplier_id;
            } elseif ( $destination == 'Otros' ) {
                $referralGuide->receiver = $receiver;
                $referralGuide->document = $document;
                $referralGuide->arrival_point = $puntoLlegada;
            }

            $referralGuide->placa = $placa;
            $referralGuide->driver = $driver;
            $referralGuide->driver_licence = $driver_licence;
            $referralGuide->shipping_manager_id = $responsible;
            $referralGuide->enabled_status = 1;
            $referralGuide->save();

            // TODO: Eliminamos los detalles
            foreach ( $referralGuide->details as $detail )
            {
                $detail->delete();
            }

            foreach ($rows as $key => $row) {
                if ( $row['type'] == 'material' ) {
                    $eeferralGuideDetail = new ReferralGuideDetail();
                    $eeferralGuideDetail->referral_guide_id = $referralGuide->id;
                    $eeferralGuideDetail->material_id = $row['id'];
                    $eeferralGuideDetail->quantity = $row['quantity'];
                    $eeferralGuideDetail->save();

                } elseif ( $row['type'] == 'quote' ) {
                    $eeferralGuideDetail = new ReferralGuideDetail();
                    $eeferralGuideDetail->referral_guide_id = $referralGuide->id;
                    $eeferralGuideDetail->quote_id = $row['id'];
                    $eeferralGuideDetail->quantity = $row['quantity'];
                    $eeferralGuideDetail->save();

                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Guía de remisión guardada con éxito.'], 200);
    }

    public function destroy($guide_id)
    {
        DB::beginTransaction();
        try {

            $guide = ReferralGuide::find($guide_id);
            $guide->enabled_status = 0;
            $guide->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Guía de remisión anulada con éxito'], 200);

    }

    public function printReferralGuide($guide_id)
    {
        $purchase_order = null;
        $guide = ReferralGuide::find($guide_id);

        $enabled_status = "";
        if ( $guide->enabled_status == 1 )
        {
            $enabled_status = '<span class="badge bg-primary">Activa</span>';
        } elseif ( $guide->enabled_status == 0 ) {
            $enabled_status = '<span class="badge bg-danger text-white">Anulada</span>';
        }

        $destinatario = "No tiene";
        $documento = "No tiene";
        $punto_llegada = "No tiene";

        if ( $guide->customer_id == null && $guide->supplier_id == null )
        {
            $destinatario = $guide->receiver;
            $documento = $guide->document;
            $punto_llegada = $guide->arrival_point;
        } elseif ( $guide->customer_id != null ) {
            $customer = Customer::find($guide->customer_id);
            $destinatario = $customer->business_name;
            $documento = $customer->RUC;
            $punto_llegada = $customer->address;
        } elseif ( $guide->supplier_id != null ) {
            $supplier = Supplier::find($guide->supplier_id);
            $destinatario = $supplier->business_name;
            $documento = $supplier->RUC;
            $punto_llegada = $supplier->address;
        }

        if ( $guide->reason_transfer_id != null )
        {
            $reasonID = ReasonTransfer::find($guide->reason_transfer_id);
            $reason = $reasonID->description;
        } else {
            $reason = "No tiene";
        }

        if ( $guide->shipping_manager_id != null )
        {
            $responsibleID = ShippingManager::find($guide->shipping_manager_id);
            $responsible = $responsibleID->user->name;
        } else {
            $responsible = "No tiene";
        }

        $arrayDetails = [];
        $arrayGuide = [];

        foreach ( $guide->details as $detail )
        {
            $code = "";
            $description = "";
            $unit = "";
            $quantity = "";

            if ( $detail->material_id != null ) {
                $material = Material::find($detail->material_id);
                $code = $material->code;
                $description = $material->full_name;
                $unit = $material->unitMeasure->description;
                $quantity = $detail->quantity;
            } elseif ( $detail->quote_id != null ) {
                $quote = Quote::find($detail->quote_id);
                $code = $quote->code;
                $description = $quote->description_quote;
                $unit = 'UNIDAD';
                $quantity = $detail->quantity;
            }

            array_push($arrayDetails, [
                "code" => $code,
                "description" => $description,
                "unit" => $unit,
                "quantity" => $quantity,
            ]);
        }

        array_push($arrayGuide, [
            "id" => $guide->id,
            "code" => $guide->code,
            "date_transfer" => ($guide->date_transfer != null) ? $guide->date_transfer->format('d/m/Y') : "",
            "reason" => $reason,
            "destinatario" => $destinatario,
            "documento" => $documento,
            "punto_llegada" => $punto_llegada,
            "vehiculo" => ($guide->placa != null) ? $guide->placa : "No tiene",
            "driver" => ($guide->driver == null) ? 'No tiene': $guide->driver,
            "driver_licence" => ($guide->driver_licence == null) ? 'No tiene': $guide->driver_licence,
            "responsible" => $responsible,
            "enabled_status" => $enabled_status,
            "state" => $guide->enabled_status,
            "details" => $arrayDetails
        ]);

        $view = view('referralGuide.printGuideV2', compact('arrayGuide'));

        $pdf = PDF::loadHTML($view);

        $name = 'Guia_de_remision_ ' . $guide->code . '.pdf';

        return $pdf->stream($name);
    }

    public function exportReferralGuides()
    {
        $startDate = $_GET['start'];
        $endDate = $_GET['end'];
        $dates = '';

        if ( $startDate == "" || $endDate == "" )
        {
            $dates = "REPORTE GENERAL DE GUIAS DE REMISIÓN";
            $query = ReferralGuide::with('reason', 'customer', 'supplier', 'responsible.user')->orderBy('created_at', 'DESC');

        } else {
            $dates = "REPORTE GENERAL DE GUIAS DE REMISIÓN ".$startDate." AL ".$endDate;
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = ReferralGuide::with('reason', 'customer', 'supplier', 'responsible')
                ->whereDate('date_transfer', '>=', $fechaInicio)
                ->whereDate('date_transfer', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        $referralGuides = $query->get();

        $arrayGuides = [];

        foreach ( $referralGuides as $referralGuide )
        {
            $enabled_status = "";
            if ( $referralGuide->enabled_status == 1 )
            {
                $enabled_status = 'Activa';
            } elseif ( $referralGuide->enabled_status == 0 ) {
                $enabled_status = 'Anulada';
            }

            $destinatario = "No tiene";
            $documento = "No tiene";
            $punto_llegada = "No tiene";

            if ( $referralGuide->customer_id == null && $referralGuide->supplier_id == null )
            {
                $destinatario = $referralGuide->receiver;
                $documento = $referralGuide->document;
                $punto_llegada = $referralGuide->arrival_point;
            } elseif ( $referralGuide->customer_id != null ) {
                $customer = Customer::find($referralGuide->customer_id);
                $destinatario = $customer->business_name;
                $documento = $customer->RUC;
                $punto_llegada = $customer->address;
            } elseif ( $referralGuide->supplier_id != null ) {
                $supplier = Supplier::find($referralGuide->supplier_id);
                $destinatario = $supplier->business_name;
                $documento = $supplier->RUC;
                $punto_llegada = $supplier->address;
            }

            if ( $referralGuide->reason_transfer_id != null )
            {
                $reasonID = ReasonTransfer::find($referralGuide->reason_transfer_id);
                $reason = $reasonID->description;
            } else {
                $reason = "No tiene";
            }

            if ( $referralGuide->shipping_manager_id != null )
            {
                $responsibleID = ShippingManager::find($referralGuide->shipping_manager_id);
                $responsible = $responsibleID->user->name;
            } else {
                $responsible = "No tiene";
            }

            array_push($arrayGuides, [
                "id" => $referralGuide->id,
                "code" => $referralGuide->code,
                "date_transfer" => ($referralGuide->date_transfer != null) ? $referralGuide->date_transfer->format('d/m/Y') : "",
                "reason" => $reason,
                "destinatario" => $destinatario,
                "documento" => $documento,
                "punto_llegada" => $punto_llegada,
                "vehiculo" => ($referralGuide->placa != null) ? $referralGuide->placa : "No tiene",
                "driver" => ($referralGuide->driver == null) ? 'No tiene': $referralGuide->driver,
                "driver_licence" => ($referralGuide->driver_licence == null) ? 'No tiene': $referralGuide->driver_licence,
                "responsible" => $responsible,
                "enabled_status" => $enabled_status,
                "state" => $referralGuide->enabled_status,
            ]);
        }

        return (new GuidesExcelDownload($arrayGuides, $dates))->download('guiasDeRemision.xlsx');

    }
}
