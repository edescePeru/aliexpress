<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CategoryEquipment;
use App\Customer;
use App\DefaultEquipment;
use App\DefaultEquipmentConsumable;
use App\DefaultEquipmentMaterial;
use App\DefaultEquipmentTurnstile;
use App\DefaultEquipmentWorkForce;
use App\Equipment;
use App\EquipmentConsumable;
use App\EquipmentElectric;
use App\EquipmentMaterial;
use App\EquipmentProforma;
use App\EquipmentProformaConsumable;
use App\EquipmentProformaElectric;
use App\EquipmentProformaMaterial;
use App\EquipmentProformaTurnstiles;
use App\EquipmentProformaWorkdays;
use App\EquipmentProformaWorkforces;
use App\EquipmentTurnstile;
use App\EquipmentWorkday;
use App\EquipmentWorkforce;
use App\Http\Requests\ProformaEditRequest;
use App\Http\Requests\ProformaStoreRequest;
use App\Http\Requests\UpdateEquipmentProformaRequest;
use App\Material;
use App\Notification;
use App\NotificationUser;
use App\PaymentDeadline;
use App\Proforma;
use App\Quote;
use App\QuoteUser;
use App\UnitMeasure;
use App\User;
use App\Workforce;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;

class ProformaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable()->get();
        $customers = Customer::all();

        return view('proforma.index', compact('permissions', 'paymentDeadlines', 'customers'));

    }

    public function getDataProformas(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $deadline = $request->input('deadline');
        $customer = $request->input('customer');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $dateCurrent = Carbon::now('America/Lima');
            $date4MonthAgo = $dateCurrent->subMonths(4);
            $query = Proforma::where('created_at', '>=', $date4MonthAgo)
                ->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Proforma::whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($description) {
            $query->where('description_quote', 'LIKE', '%'.$description.'%');

        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($deadline) {
            $query->where('payment_deadline_id', $deadline);

        }

        if ($customer) {
            $query->where('customer_id', $customer);

        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $proformas = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $arrayProformas = [];

        foreach ( $proformas as $proforma )
        {
            if ( $proforma->state == 'created' )
            {
                $state = '<span class="badge bg-primary">Creada</span>';
            } elseif ( $proforma->state == 'confirmed' ) {
                $state = '<span class="badge bg-gradient-navy text-white">V.B. '. $proforma->date_vb_proforma->format('d/m/Y') .' - <br>'. $proforma->user_vb->name.'</span>';
            } elseif ( $proforma->state == 'destroy' ) {
                $state = '<span class="badge bg-danger">Cancelada</span>';
            } elseif ( $proforma->state == 'expired' ) {
                $state = '<span class="badge bg-warning">Expiró</span>';
            } else {
                $state = '<span class="badge bg-secondary">Sin Estado</span>';
            }

            array_push($arrayProformas, [
                "id" => $proforma->id,
                "code" => $proforma->code,
                "description" => $proforma->description_quote,
                "date_quote" => ($proforma->date_quote == null) ? '': $proforma->date_quote->format('d/m/Y'),
                "date_validate" => ($proforma->date_validate == null) ? '': $proforma->date_validate->format('d/m/Y'),
                "deadline" => ($proforma->payment_deadline_id == null) ? '': $proforma->deadline->description,
                "delivery_time" => $proforma->time_delivery,
                //"delivery_time" => $proforma->delivery_time,
                "customer" => ($proforma->customer_id == null) ? '': $proforma->customer->business_name,
                "total_sin_igv" => round(($proforma->total_proforma)/1.18, 0),
                "total_con_igv" => round($proforma->total_proforma, 0),
                "currency" => $proforma->currency,
                "state" => $state,
                "estado" => $proforma->state,
                "created_at" => $proforma->created_at->format('d/m/Y'),
                "creator" => ($proforma->user_creator == null) ? '': $proforma->creator->name
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

        return ['data' => $arrayProformas, 'pagination' => $pagination];
    }

    public function create()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $customers = Customer::all();
        $maxId = Proforma::max('id')+1;
        $length = 5;
        $codeQuote = 'PCOT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable()->get();
        $categories = CategoryEquipment::all();

        // TODO: Creamos la pre cotización vacía
        /*$proforma = Proforma::create([
            'code' => $codeQuote,
            'total' => 0,
            'state' => 'created',
            'currency' => 'USD',
            'user_creator' => Auth::id()
        ]);*/

        return view('proforma.create', compact('categories','customers','codeQuote', 'permissions', 'paymentDeadlines'));

    }

    public function getDataEquipments(Request $request)
    {
        $category = $request->input('category');
        $nameEquipment = $request->input('nameEquipment');
        $large = $request->input('length');
        $width = $request->input('width');
        $high = $request->input('high');

        $query = DefaultEquipment::where('category_equipment_id', $category)
            ->orderBy('created_at', 'DESC');

        // Aplicar filtros si se proporcionan
        if ($nameEquipment) {
            $query->where('description', 'like', '%'.$nameEquipment.'%');
        }

        if ($large) {
            $query->where('large', $large);
        }

        if ($width) {
            $query->where('width', $width);
        }

        if ($high) {
            $query->where('high', $high);
        }



        $equipments = $query->get();

        //dd($proformas);

        $arrayEquipments = [];

        foreach ( $equipments as $equipment )
        {
            array_push($arrayEquipments, [
                "id" => $equipment->id,
                "description" => $equipment->description,
                "large" => $equipment->large,
                "width" => $equipment->width,
                "high" => $equipment->high,
            ]);
        }

        return ['equipments' => $arrayEquipments];
    }

    public function getDataEquipmentDefault($equipment_id)
    {
        $equipment = DefaultEquipment::find($equipment_id);
        // TODO: Actualizar los precios
        $flagChange = false;
        foreach ( $equipment->materials as $equipment_material )
        {
            if ( $equipment_material->unit_price !== $equipment_material->material->unit_price )
            {
                $flagChange = true;
                $equipment_material->unit_price = $equipment_material->material->unit_price;
                $equipment_material->total_price = $equipment_material->material->unit_price * $equipment_material->quantity;
                $equipment_material->save();
            }
        }

        foreach ( $equipment->consumables as $equipment_consumable )
        {
            if ( $equipment_consumable->unit_price !== $equipment_consumable->material->unit_price )
            {
                $flagChange = true;
                $equipment_consumable->unit_price = $equipment_consumable->material->unit_price;
                $equipment_consumable->total_price = $equipment_consumable->material->unit_price * $equipment_consumable->quantity;
                $equipment_consumable->save();
            }
        }

        foreach ( $equipment->electrics as $equipment_electric )
        {
            if ( $equipment_electric->price !== $equipment_electric->material->unit_price )
            {
                $flagChange = true;
                $equipment_electric->price = $equipment_electric->material->unit_price;
                $equipment_electric->total = $equipment_electric->material->unit_price * $equipment_electric->quantity;
                $equipment_electric->save();
            }
        }

        return response()->json([
            "change" => $flagChange,
            "id" => $equipment->id,
            "nEquipment" => $equipment->description,
            "qEquipment" => 1,
            "pEquipment" => round(($equipment->total_equipment/1)/1.18, 2),
            "uEquipment" => $equipment->utility,
            "rlEquipment" => round($equipment->rent + $equipment->letter, 2),
            "uPEquipment" => round(($equipment->total_equipment_utility/1.18)/1, 2),
            "tEquipment" => round($equipment->total_equipment_utility/1.18, 2)
        ]);
    }

    public function addDataDefaultEquipmentProforma($proforma_id, $equipment_id)
    {
        $proforma = Proforma::find($proforma_id);

        $defaultEquipment = DefaultEquipment::find($equipment_id);

        // TODO: Actualizar los precios
        $flagChange = false;

        foreach ( $defaultEquipment->materials as $equipment_material )
        {
            if ( $equipment_material->unit_price !== $equipment_material->material->unit_price )
            {
                $flagChange = true;
                $equipment_material->unit_price = $equipment_material->material->unit_price;
                $equipment_material->total_price = $equipment_material->material->unit_price * $equipment_material->quantity;
                $equipment_material->save();
            }
        }

        foreach ( $defaultEquipment->consumables as $equipment_consumable )
        {
            if ( $equipment_consumable->unit_price !== $equipment_consumable->material->unit_price )
            {
                $flagChange = true;
                $equipment_consumable->unit_price = $equipment_consumable->material->unit_price;
                $equipment_consumable->total_price = $equipment_consumable->material->unit_price * $equipment_consumable->quantity;
                $equipment_consumable->save();
            }
        }

        foreach ( $defaultEquipment->electrics as $equipment_electric )
        {
            if ( $equipment_electric->price !== $equipment_electric->material->price )
            {
                $flagChange = true;
                $equipment_electric->price = $equipment_electric->material->unit_price;
                $equipment_electric->total = $equipment_electric->material->unit_price * $equipment_electric->quantity;
                $equipment_electric->save();
            }
        }

        $totalQuote = $proforma->total_proforma;

        $equipment = EquipmentProforma::create([
            'proforma_id' => $proforma->id,
            'default_equipment_id' => $defaultEquipment->id,
            'description' =>($defaultEquipment->description == "" || $defaultEquipment->description == null) ? '':$defaultEquipment->description,
            'detail' => ($defaultEquipment->details == "" || $defaultEquipment->details == null) ? '':$defaultEquipment->details,
            'quantity' => 1,
            'utility' => $defaultEquipment->utility,
            'rent' => $defaultEquipment->rent,
            'letter' => $defaultEquipment->letter,
            'total' => $defaultEquipment->total*1.18
        ]);

        $totalMaterial = 0;

        $totalConsumable = 0;

        $totalElectric = 0;

        $totalWorkforces = 0;

        $totalTornos = 0;

        $totalDias = 0;

        $materials = $defaultEquipment->materials;

        $consumables = $defaultEquipment->consumables;

        $electrics = $defaultEquipment->electrics;

        $workforces = $defaultEquipment->workforces;

        $tornos = $defaultEquipment->turnstiles;

        $dias = $defaultEquipment->workdays;

        for ( $j=0; $j<sizeof($materials); $j++ )
        {
            $equipmentMaterial = EquipmentProformaMaterial::create([
                'equipment_proforma_id' => $equipment->id,
                'material_id' => $materials[$j]->material_id,
                'quantity' => (float) $materials[$j]->quantity,
                'unit_price' => (float) $materials[$j]->unit_price,
                'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                'percentage' => (float) $materials[$j]->percentage,
                'total_price' => (float) $materials[$j]->total_price,
            ]);

            $totalMaterial += $equipmentMaterial->total_price;
        }

        for ( $k=0; $k<sizeof($consumables); $k++ )
        {
            $equipmentConsumable = EquipmentProformaConsumable::create([
                'equipment_proforma_id' => $equipment->id,
                'material_id' => $consumables[$k]->material_id,
                'quantity' => (float) $consumables[$k]->quantity,
                'unit_price' => (float) $consumables[$k]->unit_price,
                'total_price' => (float) $consumables[$k]->total_price,
            ]);

            $totalConsumable += $equipmentConsumable->total_price;
        }

        for ( $e=0; $e<sizeof($electrics); $e++ )
        {
            $equipmentElectric = EquipmentProformaElectric::create([
                'equipment_proforma_id' => $equipment->id,
                'material_id' => $electrics[$e]->material_id,
                'quantity' => (float) $electrics[$e]->quantity,
                'price' => (float) $electrics[$e]->price,
                'total' => (float) $electrics[$e]->total,
            ]);

            $totalElectric += $equipmentElectric->total;
        }

        for ( $w=0; $w<sizeof($workforces); $w++ )
        {
            $equipmentWorkforce = EquipmentProformaWorkforces::create([
                'equipment_proforma_id' => $equipment->id,
                'description' => $workforces[$w]->description,
                'unit_price' => (float) $workforces[$w]->unit_price,
                'quantity' => (float) $workforces[$w]->quantity,
                'total_price' => (float) $workforces[$w]->total_price,
                'unit' => $workforces[$w]->unit,
            ]);

            $totalWorkforces += $equipmentWorkforce->total_price;
        }

        for ( $r=0; $r<sizeof($tornos); $r++ )
        {
            $equipmenttornos = EquipmentProformaTurnstiles::create([
                'equipment_proforma_id' => $equipment->id,
                'description' => $tornos[$r]->description,
                'unit_price' => (float) $tornos[$r]->unit_price,
                'quantity' => (float) $tornos[$r]->quantity,
                'total_price' => (float) $tornos[$r]->total_price
            ]);

            $totalTornos += $equipmenttornos->total_price;
        }

        for ( $d=0; $d<sizeof($dias); $d++ )
        {
            $equipmentdias = EquipmentProformaWorkdays::create([
                'equipment_proforma_id' => $equipment->id,
                'description' => $dias[$d]->description,
                'quantityPerson' => (float) $dias[$d]->quantityPerson,
                'hoursPerPerson' => (float) $dias[$d]->hoursPerPerson,
                'pricePerHour' => (float) $dias[$d]->pricePerHour,
                'total_price' => (float) $dias[$d]->total_price
            ]);

            $totalDias += $equipmentdias->total_price;
        }

        $totalEquipo = (($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalElectric) )+$totalDias;
        $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
        $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
        $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

        $totalQuote += $totalEquipmentR;

        $equipment->total = $totalEquipo;

        $equipment->save();

        $proforma->total = $totalQuote;

        $proforma->save();

        $proforma1 = Proforma::find($proforma_id);

        return response()->json([
            "change" => $flagChange,
            "id" => $equipment->id,
            "nEquipment" => $equipment->description,
            "qEquipment" => 1,
            "pEquipment" => round(($equipment->total/1)/1.18, 2),
            "uEquipment" => $equipment->utility,
            "rlEquipment" => round($equipment->rent + $equipment->letter, 2),
            "uPEquipment" => round(($equipment->subtotal_percentage/1.18)/1, 2),
            "tEquipment" => round($equipment->subtotal_percentage/1.18, 2),
            "utility" => $equipment->utility,
            "rent" => $equipment->rent,
            "letter" => $equipment->letter,
            "total_equipments" => $proforma1->total_equipments,
            "total_proforma" => $proforma1->total_proforma
        ]);
    }

    public function editEquipmentProforma($equipment_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $equipment = EquipmentProforma::with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays'])
            ->find($equipment_id);

        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->whereConsumable('description',$defaultConsumable)->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();

        $unitMeasures = UnitMeasure::all();

        $workforces = Workforce::with('unitMeasure')->get();

        $utility = $equipment->utility;
        $rent = $equipment->rent;
        $letter = $equipment->letter;

        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        //dd($equipment);

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
                'unit_measure' => $material->unitMeasure
            ]);
        }

        return view('proforma.editEquipment', compact('permissions', 'consumables', 'electrics' ,'unitMeasures' ,'workforces', 'utility', 'rent', 'letter', 'equipment', 'array'));

    }

    public function updateEquipmentProforma(UpdateEquipmentProformaRequest $request, $equipment_id)
    {
        $begin = microtime(true);

        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $equipment = EquipmentProforma::find($equipment_id);

            $equipments = json_decode($request->get('equipments'));

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                $equipment->description = $equipments[$i]->description;
                $equipment->detail = ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail;
                $equipment->quantity = $equipments[$i]->quantity;
                $equipment->utility = $equipments[$i]->utility;
                $equipment->letter = $equipments[$i]->letter;
                $equipment->rent = $equipments[$i]->rent;

                $materials = $equipments[$i]->materials;

                $consumables = $equipments[$i]->consumables;

                $electrics = $equipments[$i]->electrics;

                $workforces = $equipments[$i]->workforces;

                $tornos = $equipments[$i]->tornos;

                $dias = $equipments[$i]->dias;

                // TODO: Eliminamos los datos anteriores
                foreach( $equipment->materials as $material ) {
                    //$totalDeleted = $totalDeleted + (float) $material->total;
                    $material->delete();
                }
                foreach( $equipment->consumables as $consumable ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $consumable->delete();
                }
                foreach( $equipment->electrics as $electric ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $electric->delete();
                }
                foreach( $equipment->workforces as $workforce ) {
                    //$totalDeleted = $totalDeleted + (float) $workforce->total;
                    $workforce->delete();
                }
                foreach( $equipment->turnstiles as $turnstile ) {
                    //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                    $turnstile->delete();
                }
                foreach( $equipment->workdays as $workday ) {
                    //$totalDeleted = $totalDeleted + (float) $workday->total;
                    $workday->delete();
                }

                for ( $j=0; $j<sizeof($materials); $j++ )
                {
                    $equipmentMaterial = EquipmentProformaMaterial::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float) $materials[$j]->quantity,
                        'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                        'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                        'percentage' => (float) $materials[$j]->quantity,
                        'unit_price' => (float) $materials[$j]->material->unit_price,
                        'total_price' => (float) $materials[$j]->quantity*(float) $materials[$j]->material->unit_price,
                    ]);

                }

                for ( $k=0; $k<sizeof($consumables); $k++ )
                {
                    $material = Material::find($consumables[$k]->id);

                    $equipmentConsumable = EquipmentProformaConsumable::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $consumables[$k]->id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'unit_price' => (float) $consumables[$k]->price,
                        'total_price' => (float) $consumables[$k]->total,
                    ]);

                }

                for ( $e=0; $e<sizeof($electrics); $e++ )
                {
                    $equipmentElectric = EquipmentProformaElectric::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $electrics[$e]->id,
                        'quantity' => (float) $electrics[$e]->quantity,
                        'price' => (float) $electrics[$e]->price,
                        'total' => (float) $electrics[$e]->total,
                    ]);

                }

                for ( $w=0; $w<sizeof($workforces); $w++ )
                {
                    $equipmentWorkforce = EquipmentProformaWorkforces::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $workforces[$w]->description,
                        'quantity' => (float) $workforces[$w]->quantity,
                        'unit_price' => (float) $workforces[$w]->price,
                        'total_price' => (float) $workforces[$w]->total,
                        'unit' => $workforces[$w]->unit,
                    ]);
                }

                for ( $r=0; $r<sizeof($tornos); $r++ )
                {
                    $equipmenttornos = EquipmentProformaTurnstiles::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $tornos[$r]->description,
                        'quantity' => (float) $tornos[$r]->quantity,
                        'unit_price' => (float) $tornos[$r]->price,
                        'total_price' => (float) $tornos[$r]->total
                    ]);

                }

                for ( $d=0; $d<sizeof($dias); $d++ )
                {
                    $equipmentdias = EquipmentProformaWorkdays::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $dias[$d]->description,
                        'quantityPerson' => (float) $dias[$d]->quantity,
                        'hoursPerPerson' => (float) $dias[$d]->hours,
                        'pricePerHour' => (float) $dias[$d]->price,
                        'total_price' => (float) $dias[$d]->total
                    ]);
                }

                $equipment->save();
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar equipo proforma.',
                'time' => $end
            ]);

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Equipo de pre cotización guardado con éxito.'], 200);

    }

    public function destroyEquipmentProforma($proforma_id, $equipment_id)
    {
        $proforma = Proforma::find($proforma_id);

        DB::beginTransaction();
        try {
            $equipment_proforma = EquipmentProforma::where('id', $equipment_id)
                ->where('proforma_id',$proforma->id)->first();

            foreach( $equipment_proforma->materials as $material ) {
                $material->delete();
            }
            foreach( $equipment_proforma->consumables as $consumable ) {
                $consumable->delete();
            }
            foreach( $equipment_proforma->workforces as $workforce ) {
                $workforce->delete();
            }
            foreach( $equipment_proforma->turnstiles as $turnstile ) {
                $turnstile->delete();
            }
            foreach( $equipment_proforma->workdays as $workday ) {
                $workday->delete();
            }

            $totalDeleted = $equipment_proforma->total;

            $totalEquipmentU = $totalDeleted*(($equipment_proforma->utility/100)+1);
            $totalEquipmentL = $totalEquipmentU*(($equipment_proforma->letter/100)+1);
            $totalEquipmentR = $totalEquipmentL*(($equipment_proforma->rent/100)+1);

            $proforma->total = $proforma->total - $totalEquipmentR;

            $proforma->currency = 'USD';
            $proforma->currency_compra = null;
            $proforma->currency_venta = null;
            $proforma->total_soles = 0;
            $proforma->save();

            $equipment_proforma->delete();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Equipo eliminada con éxito.',
            "total_equipments" => $proforma->total_equipments,
            "total_proforma" => $proforma->total_proforma
        ], 200);

    }

    public function changePercentagesEquipment(Request $request, $id_equipment, $id_proforma)
    {
        DB::beginTransaction();
        try {
            $equipment = EquipmentProforma::find($id_equipment);
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

    public function store(ProformaStoreRequest $request)
    {
        $begin = microtime(true);
//        dump($request);
//        dd();
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $maxCode = Proforma::max('id');
            $maxId = $maxCode + 1;
            $length = 5;
            //$codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $proforma = Proforma::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'date_quote' => ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now(),
                'date_validate' => ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5),
                'delivery_time' => ($request->has('delivery_time')) ? $request->get('delivery_time') : '',
                'customer_id' => ($request->has('customer_id')) ? $request->get('customer_id') : null,
                'contact_id' => ($request->has('contact_id')) ? $request->get('contact_id') : null,
                'payment_deadline_id' => ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null,
                'state' => 'created',
                'currency' => 'USD',
                'user_creator' => Auth::id(),
                'observations' => $request->get('observations'),
            ]);

            $codeQuote = '';
            if ( $maxId < $proforma->id ){
                $codeQuote = 'PCOT-'.str_pad($proforma->id,$length,"0", STR_PAD_LEFT);
                $proforma->code = $codeQuote;
                $proforma->save();
            } else {
                $codeQuote = 'PCOT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $proforma->code = $codeQuote;
                $proforma->save();
            }

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                $defaultEquipment = DefaultEquipment::find($equipments[$i]->id);
                $equipment = EquipmentProforma::create([
                    'proforma_id' => $proforma->id,
                    'default_equipment_id' => $defaultEquipment->id,
                    'description' =>($defaultEquipment->description == "" || $defaultEquipment->description == null) ? '':$defaultEquipment->description,
                    'detail' => ($defaultEquipment->details == "" || $defaultEquipment->details == null) ? '':$defaultEquipment->details,
                    'quantity' => 1,
                    'utility' => $defaultEquipment->utility,
                    'rent' => $defaultEquipment->rent,
                    'letter' => $defaultEquipment->letter,
                    'total' => $defaultEquipment->total*1.18
                ]);

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalElectric = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                $materials = $defaultEquipment->materials;

                $consumables = $defaultEquipment->consumables;

                $electrics = $defaultEquipment->electrics;

                $workforces = $defaultEquipment->workforces;

                $tornos = $defaultEquipment->turnstiles;

                $dias = $defaultEquipment->workdays;

                for ( $j=0; $j<sizeof($materials); $j++ )
                {
                    $equipmentMaterial = EquipmentProformaMaterial::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $materials[$j]->material_id,
                        'quantity' => (float) $materials[$j]->quantity,
                        'unit_price' => (float) $materials[$j]->unit_price,
                        'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                        'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                        'percentage' => (float) $materials[$j]->percentage,
                        'total_price' => (float) $materials[$j]->total_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total_price;
                }

                for ( $k=0; $k<sizeof($consumables); $k++ )
                {
                    $equipmentConsumable = EquipmentProformaConsumable::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $consumables[$k]->material_id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'unit_price' => (float) $consumables[$k]->unit_price,
                        'total_price' => (float) $consumables[$k]->total_price,
                    ]);

                    $totalConsumable += $equipmentConsumable->total_price;
                }

                for ( $e=0; $e<sizeof($electrics); $e++ )
                {
                    $equipmentElectric = EquipmentProformaElectric::create([
                        'equipment_proforma_id' => $equipment->id,
                        'material_id' => $electrics[$e]->material_id,
                        'quantity' => (float) $electrics[$e]->quantity,
                        'price' => (float) $electrics[$e]->price,
                        'total' => (float) $electrics[$e]->total,
                    ]);

                    $totalElectric += $equipmentElectric->total;
                }

                for ( $w=0; $w<sizeof($workforces); $w++ )
                {
                    $equipmentWorkforce = EquipmentProformaWorkforces::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $workforces[$w]->description,
                        'unit_price' => (float) $workforces[$w]->unit_price,
                        'quantity' => (float) $workforces[$w]->quantity,
                        'total_price' => (float) $workforces[$w]->total_price,
                        'unit' => $workforces[$w]->unit,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total_price;
                }

                for ( $r=0; $r<sizeof($tornos); $r++ )
                {
                    $equipmenttornos = EquipmentProformaTurnstiles::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $tornos[$r]->description,
                        'unit_price' => (float) $tornos[$r]->unit_price,
                        'quantity' => (float) $tornos[$r]->quantity,
                        'total_price' => (float) $tornos[$r]->total_price
                    ]);

                    $totalTornos += $equipmenttornos->total_price;
                }

                for ( $d=0; $d<sizeof($dias); $d++ )
                {
                    $equipmentdias = EquipmentProformaWorkdays::create([
                        'equipment_proforma_id' => $equipment->id,
                        'description' => $dias[$d]->description,
                        'quantityPerson' => (float) $dias[$d]->quantityPerson,
                        'hoursPerPerson' => (float) $dias[$d]->hoursPerPerson,
                        'pricePerHour' => (float) $dias[$d]->pricePerHour,
                        'total_price' => (float) $dias[$d]->total_price
                    ]);

                    $totalDias += $equipmentdias->total_price;
                }

                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos) )+$totalDias;
                $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                $totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;

                $equipment->save();
            }

            $proforma->total = $totalQuote;

            $proforma->save();

            // Crear notificacion
            $notification = Notification::create([
                'content' => $proforma->code.' creada por '.Auth::user()->name,
                'reason_for_creation' => 'create_prequote',
                'user_id' => Auth::user()->id,
                'url_go' => ""
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
                'action' => 'Guardar pre cotizacion POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Pre Cotización '.$codeQuote.' guardada con éxito.'], 200);

    }

    public function show($proforma_id)
    {
        $proforma = Proforma::where('id', $proforma_id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments'])->first();
        //dump($quote);

        /*Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion VISTA',
            'time' => $end
        ]);*/
        return view('proforma.show', compact('proforma'));

    }

    public function edit($proforma_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $customers = Customer::all();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->selectable(Proforma::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($proforma_id)->value('payment_deadline_id'))->get();
        $categories = CategoryEquipment::all();
        $proforma = Proforma::where('id', $proforma_id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments'])->first();
        //dump($quote);
        $equipments = [];

        if ($proforma) {

            foreach ($proforma->equipments as $equipment) {
                array_push($equipments, ['id' => $equipment->default_equipment_id]);
            }
        }

        /*Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion VISTA',
            'time' => $end
        ]);*/
        return view('proforma.edit', compact('proforma', 'permissions', 'customers', 'paymentDeadlines', 'categories', 'equipments'));
    }

    public function update(ProformaEditRequest $request)
    {
        //dd($request);
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $quote = Proforma::find($request->get('proforma_id'));

            $quote->description_quote = $request->get('code_description');
            $quote->date_quote = ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now();
            $quote->date_validate = ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5);
            $quote->payment_deadline_id = ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null;
            $quote->delivery_time = ($request->has('delivery_time')) ? $request->get('delivery_time') : '';
            $quote->customer_id = ($request->has('customer_id')) ? $request->get('customer_id') : null;
            $quote->contact_id = ($request->has('contact_id')) ? $request->get('contact_id') : null;
            $quote->currency = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar pre cotizaciones POST',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Pre cotización guardada con éxito.'], 200);

    }

    public function destroy($proforma_id)
    {
        DB::beginTransaction();
        try {
                $proforma = Proforma::find($proforma_id);
                $proforma->state = 'destroy';
                $proforma->save();
                DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Pre Cotización '.$proforma->code.' eliminada con éxito.'], 200);
    }

    public function printProformaToCustomer($id)
    {
        // Eliminamos elos archivos

        $proforma = Proforma::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments'])->first();

        $view = view('proforma.proformaCustomer', compact('proforma'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$proforma->description_quote);

        $name = $proforma->code . ' '. ltrim(rtrim($description)) . '.pdf';
        return $pdf->stream($name);

        //return $pdf->stream($name);
    }

    public function vistoBuenoProforma($id)
    {
        DB::beginTransaction();
        try {
            $proforma = Proforma::find($id);

            //$quote->order_execution = $codeOrderExecution;
            $proforma->vb_proforma = 1;
            $proforma->date_vb_proforma= Carbon::now('America/Lima');
            $proforma->user_vb_proforma= Auth::id();
            $proforma->state = 'confirmed';
            $proforma->save();

            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $proforma->description_quote,
                'observations' => $proforma->observations,
                'date_quote' => ($proforma->date_quote != null) ? $proforma->date_quote : Carbon::now(),
                'date_validate' => ($proforma->date_validate != null) ? $proforma->date_validate : Carbon::now()->addDays(5),
                'delivery_time' => $proforma->delivery_time,
                'customer_id' => ($proforma->customer_id != null) ? $proforma->customer_id : null,
                'contact_id' => ($proforma->contact_id != null) ? $proforma->contact_id : null,
                'payment_deadline_id' => ($proforma->payment_deadline_id != null) ? $proforma->payment_deadline_id : null,
                'state' => 'created',
                'proforma_id' => $proforma->id
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

            $equipments = $proforma->equipments;

            $totalQuote = 0;

            foreach ( $equipments as $equipo )
            {
                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' =>($equipo->description == "" || $equipo->description == null) ? '':$equipo->description,
                    'detail' => ($equipo->detail == "" || $equipo->detail == null) ? '':$equipo->detail,
                    'quantity' => $equipo->quantity,
                    'utility' => $equipo->utility,
                    'rent' => $equipo->rent,
                    'letter' => $equipo->letter,
                    'total' => $equipo->total_equipment
                ]);

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalElectric = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                $materials = $equipo->materials;

                $consumables = $equipo->consumables;

                $electrics = $equipo->electrics;

                $workforces = $equipo->workforces;

                $tornos = $equipo->turnstiles;

                $dias = $equipo->workdays;

                foreach ( $materials as $material)
                {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $material->material_id,
                        'quantity' => (float) $material->quantity,
                        'price' => (float) $material->unit_price,
                        'length' => (float) ($material->length == '') ? 0: $material->length,
                        'width' => (float) ($material->width == '') ? 0: $material->width,
                        'percentage' => (float) $material->percentage,
                        'state' => ($material->quantity > $material->material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ($material->quantity > $material->material->stock_current) ? 'Agotado':'Completo',
                        'total' => (float) $material->total_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                foreach ( $consumables as $consumable )
                {
                    $material = Material::find($consumable->material_id);

                    $equipmentConsumable = EquipmentConsumable::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $consumable->material_id,
                        'quantity' => (float) $consumable->quantity,
                        'price' => (float) $consumable->unit_price,
                        'total' => (float) $consumable->total_price,
                        'state' => ((float) $consumable->quantity > $material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ((float) $consumable->quantity > $material->stock_current) ? 'Agotado':'Completo',
                    ]);

                    $totalConsumable += $equipmentConsumable->total;
                }

                foreach ( $electrics as $electric )
                {
                    $equipmentElectric = EquipmentElectric::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $electric->material_id,
                        'quantity' => (float) $electric->quantity,
                        'price' => (float) $electric->price,
                        'total' => (float) $electric->total,
                    ]);

                    $totalElectric += $equipmentElectric->total;
                }

                foreach ( $workforces as $workforce )
                {
                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforce->description,
                        'price' => (float) $workforce->unit_price,
                        'quantity' => (float) $workforce->quantity,
                        'total' => (float) $workforce->total_price,
                        'unit' => $workforce->unit,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                foreach ( $tornos as $torno )
                {
                    $equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $equipment->id,
                        'description' => $torno->description,
                        'price' => (float) $torno->unit_price,
                        'quantity' => (float) $torno->quantity,
                        'total' => (float) $torno->total_price
                    ]);

                    $totalTornos += $equipmenttornos->total;
                }

                foreach ( $dias as $dia )
                {
                    $equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $equipment->id,
                        'description' => $dia->description,
                        'quantityPerson' => (float) $dia->quantityPerson,
                        'hoursPerPerson' => (float) $dia->hoursPerPerson,
                        'pricePerHour' => (float) $dia->pricePerHour,
                        'total' => (float) $dia->total_price
                    ]);

                    $totalDias += $equipmentdias->total;
                }

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

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Visto bueno guardado.'], 200);

    }
}
