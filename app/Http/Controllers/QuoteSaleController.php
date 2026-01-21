<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashMovement;
use App\CashRegister;
use App\Customer;
use App\DataGeneral;
use App\Equipment;
use App\EquipmentConsumable;
use App\EquipmentElectric;
use App\EquipmentMaterial;
use App\EquipmentTurnstile;
use App\EquipmentWorkday;
use App\EquipmentWorkforce;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\ImagesQuote;
use App\Item;
use App\Mail\StockLowNotificationMail;
use App\Material;
use App\MaterialPresentation;
use App\Notification;
use App\NotificationUser;
use App\Output;
use App\OutputDetail;
use App\PaymentDeadline;
use App\PorcentageQuote;
use App\PromotionLimit;
use App\PromotionUsage;
use App\Quote;
use App\QuoteMaterialReservation;
use App\QuoteUser;
use App\ResumenEquipment;
use App\ResumenQuote;
use App\Sale;
use App\SaleDetail;
use App\Services\InventoryCostService;
use App\TipoPago;
use App\UnitMeasure;
use App\User;
use App\Worker;
use App\Workforce;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use Intervention\Image\Facades\Image;


class QuoteSaleController extends Controller
{
    use NubefactTrait;

    /** @var InventoryCostService */
    private $inventoryCostService;

    public function __construct(InventoryCostService $inventoryCostService)
    {
        $this->inventoryCostService = $inventoryCostService;
    }

    public function index()
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
            /*["value" => "VB_operation", "display" => "VB OPERACIONES"],*/
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quoteSale.general', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function indexFacturadas()
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
            /*["value" => "VB_operation", "display" => "VB OPERACIONES"],*/
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quoteSale.facturadas', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function getMaterials(Request $request)
    {
        $materials = [];

        if ($request->has('q')) {
            $search = $request->get('q');

            $materials = Material::where('enable_status', 1) // 👈 aquí filtras en la BD
            ->get()
                ->filter(function ($item) use ($search) {
                    return stripos($item->full_description, $search) !== false;
                });
        }

        return json_encode($materials);
    }

    public function getMaterialTotals()
    {
        $materials = Material::with('unitMeasure','typeScrap')
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_description' => $material->full_description,
                'unit' => ($material->unitMeasure == null) ? "":$material->unitMeasure->name,
                'code' => $material->code,
                'type_scrap' => $material->typeScrap,
                'unit_measure' => $material->unitMeasure,
                'list_price' => $material->list_price,
                'enable_status' => $material->enable_status,
                'stock_current' => $material->stock_current,
                'state_update_price' => $material->state_update_price
            ]);
        }

        return $array;
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
        $consumables = Material::with('unitMeasure')->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $maxId = Quote::max('id')+1;
        $length = 5;
        $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        //dd($array);

        $array = [];

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Crear cotizacion VISTA',
            'time' => $end
        ]);


        return view('quoteSale.create', compact('currency', 'customers', 'unitMeasures', 'consumables', 'electrics', 'workforces', 'codeQuote', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'array', 'igv'));
    }

    /*public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

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
                // Guardamos los totales
                'descuento' => ($request->has('descuento')) ? $request->get('descuento') : null,
                'gravada' => ($request->has('gravada')) ? $request->get('gravada') : null,
                'igv_total' => ($request->has('igv_total')) ? $request->get('igv_total') : null,
                'total_importe' => ($request->has('total_importe')) ? $request->get('total_importe') : null
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

                for ($k = 0; $k < sizeof($consumables); $k++) {
                    $material = Material::find($consumables[$k]->id);

                    if (!$material) {
                        throw new \Exception("El material con ID {$consumables[$k]->id} no existe.");
                    }

                    $requestedQty = (float) $consumables[$k]->quantity;

                    // Stock disponible = lo que hay en almacén - lo que ya está reservado en otras cotizaciones
                    $available = (float) $material->stock_current - (float) $material->stock_reserved;

                    if ($requestedQty > $available) {
                        // No hay suficiente stock disponible para esta cotización
                        throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                    }

                    // 🟢 REGISTRAR EQUIPMENT CONSUMABLE
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado' : 'Completo',
                        'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar' : 'En compra',
                        'equipment_id' => $equipment->id,
                        'material_id' => $consumables[$k]->id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'price' => (float) $consumables[$k]->price,
                        'valor_unitario' => (float) $consumables[$k]->valor,
                        'discount' => (float) $consumables[$k]->discount,
                        'total' => (float) $consumables[$k]->importe,
                        'type_promo' => $consumables[$k]->type_promo,
                    ]);

                    $totalConsumable += $equipmentConsumable->total;

                    // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                    // si ya había reserva de este material en esta misma cotización, la acumulamos
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $material->id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity += $requestedQty;
                        $reservation->save();
                    } else {
                        $reservation = QuoteMaterialReservation::create([
                            'quote_id'   => $quote->id,
                            'material_id'=> $material->id,
                            'quantity'   => $requestedQty,
                        ]);
                    }

                    // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                    $material->stock_reserved += $requestedQty;
                    $material->save();

                    // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                    if ($consumables[$k]->type_promo == "limit") {
                        $promotion = PromotionLimit::where('material_id', $consumables[$k]->id)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            // buscar uso
                            $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                            if ($promotion->applies_to == 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            if (!$usage) {
                                $usage = PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                    'used_quantity'        => (float) $consumables[$k]->quantity,
                                    'equipment_id'         => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id'             => $equipment->quote_id,
                                ]);
                            }

                            $requestedQty = (float) $consumables[$k]->quantity;
                            $remaining = $promotion->limit_quantity - $usage->used_quantity;

                            if ($remaining < $requestedQty) {
                                throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                            }

                            // actualizar consumo
                            //$usage->increment('used_quantity', $requestedQty);
                        }
                    }


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
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                $totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;

                $equipment->save();
            }

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

    }*/

    /**
     * Store Quote
     *
     * IMPORTANTE (según cambios recientes):
     * - Consumables pueden venir como:
     *   - Unitario: quantity = unidades, presentation_id = null, units_equivalent = quantity (opcional)
     *   - Presentación: quantity = packs, presentation_id != null, units_per_pack viene del front,
     *                   pero el backend recalcula units_per_pack desde BD y units_equivalent = packs * units_per_pack
     *
     * - Para mantener coherencia con reservas/stock:
     *   - Reservamos y descontamos stock_reserved en UNIDADES EQUIVALENTES (units_equivalent).
     *
     * - Workforces (servicios adicionales):
     *   - Puede venir billable (1/0). Si billable = 0, igual lo guardamos, pero en facturación se excluirá.
     */
    public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {

            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),

                'date_quote' => ($request->filled('date_quote'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                    : Carbon::now(),

                'date_validate' => ($request->filled('date_validate'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                    : Carbon::now()->addDays(5),

                'way_to_pay' => $request->get('way_to_pay', ''),
                'delivery_time' => $request->get('delivery_time', ''),

                'customer_id' => $request->get('customer_id'),
                'contact_id' => $request->get('contact_id'),
                'payment_deadline_id' => $request->get('payment_deadline'),

                'state' => 'created',
                'currency_invoice' => $currency,

                // Totales (front)
                //'descuento' => $request->get('descuento'),
                'descuento' => $request->get('descuentoReal'),
                'discount_type' => $request->get('discount_type'),                 // amount|percent|null
                'discount_input_mode' => $request->get('discount_input_mode'),     // with_igv|without_igv|null
                'discount_input_value' => $request->get('discount_input_value'),   // decimal|null

                /*'gravada' => $request->get('gravada'),
                'igv_total' => $request->get('igv_total'),
                'total_importe' => $request->get('total_importe'),*/
                'gravada' => $request->get('gravadaReal'),
                'igv_total' => $request->get('igvReal'),
                'total_importe' => $request->get('totalReal'),
            ]);

            // Código
            $codeQuote = ($maxId < $quote->id)
                ? 'COT-' . str_pad($quote->id, $length, "0", STR_PAD_LEFT)
                : 'COT-' . str_pad($maxId, $length, "0", STR_PAD_LEFT);

            $quote->code = $codeQuote;
            $quote->save();

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments')) ?? [];

            // IGV: si tienes una variable/config global, reemplaza
            $igvPct = 18.0;
            $igvFactor = 1 + ($igvPct / 100);

            for ($i = 0; $i < sizeof($equipments); $i++) {

                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' => ($equipments[$i]->description ?? '') ?: '',
                    'detail' => ($equipments[$i]->detail ?? '') ?: '',
                    'quantity' => $equipments[$i]->quantity ?? 1,
                    'utility' => $equipments[$i]->utility ?? 0,
                    'rent' => $equipments[$i]->rent ?? 0,
                    'letter' => $equipments[$i]->letter ?? 0,
                    'total' => $equipments[$i]->total ?? 0,
                ]);

                $totalMaterial = 0;
                $totalConsumable = 0;
                $totalElectric = 0;
                $totalWorkforces = 0;
                $totalTornos = 0;
                $totalDias = 0;

                $materials = $equipments[$i]->materials ?? [];
                $consumables = $equipments[$i]->consumables ?? [];
                $electrics = $equipments[$i]->electrics ?? [];
                $workforces = $equipments[$i]->workforces ?? [];
                $tornos = $equipments[$i]->tornos ?? [];
                $dias = $equipments[$i]->dias ?? [];

                // MATERIALS (sin cambios)
                for ($j = 0; $j < sizeof($materials); $j++) {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float)$materials[$j]->quantity,
                        'price' => (float)$materials[$j]->material->unit_price,
                        'length' => (float)(($materials[$j]->length ?? '') == '' ? 0 : $materials[$j]->length),
                        'width' => (float)(($materials[$j]->width ?? '') == '' ? 0 : $materials[$j]->width),
                        'percentage' => (float)$materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar' : 'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado' : 'Completo',
                        'total' => (float)$materials[$j]->quantity * (float)$materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                // CONSUMABLES (con presentaciones)
                // Recomendado para precisión decimal (si tienes bcmath)
                @bcscale(10);

                $totalConsumable = '0';

                for ($k = 0; $k < sizeof($consumables); $k++) {

                    $c = $consumables[$k];

                    $materialId = (int)($c->id ?? 0);
                    $material = Material::find($materialId);

                    if (!$material) {
                        throw new \Exception("El material con ID {$materialId} no existe.");
                    }

                    // ============================
                    // 1) Inputs del front (reales)
                    // ============================
                    // quantity: packs si hay presentación, unidades si no hay presentación
                    $frontQty = (float)($c->quantity ?? 0);
                    if ($frontQty <= 0) {
                        throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                    }

                    $presentationId = $c->presentation_id ?? null;

                    // ✅ Estos 3 son los que mandas y NO debemos recalcular:
                    $valorUnitarioReal = (string)($c->valorReal ?? $c->valor ?? '0'); // sin IGV (pack o unidad)
                    $priceReal         = (string)($c->priceReal ?? $c->price ?? '0'); // con IGV (pack o unidad)
                    $importeReal       = (string)($c->importe ?? '0');                // total con IGV (ya calculado)

                    if ((float)$valorUnitarioReal < 0 || (float)$priceReal < 0 || (float)$importeReal < 0) {
                        throw new \Exception("Valores inválidos (precio/valor/total) para {$material->full_name}.");
                    }

                    // ============================
                    // 2) Presentación SOLO para stock (unidades equivalentes)
                    // ============================
                    $packs = null;
                    $unitsPerPack = null;

                    if (!empty($presentationId)) {

                        $packs = (int)$frontQty;
                        if ($packs < 1) {
                            throw new \Exception("Cantidad de paquetes inválida para material {$material->full_name}.");
                        }

                        $presentation = MaterialPresentation::where('id', $presentationId)
                            ->where('material_id', $materialId)
                            ->where('active', 1)
                            ->first();

                        if (!$presentation) {
                            throw new \Exception("La presentación seleccionada no es válida o no pertenece al material {$material->full_name}.");
                        }

                        $unitsPerPack = (int)$presentation->quantity;
                        if ($unitsPerPack < 1) {
                            throw new \Exception("La presentación tiene cantidad inválida para {$material->full_name}.");
                        }

                        $requestedUnits = (float)($packs * $unitsPerPack);

                    } else {
                        // Unitario: units_equivalent debe ser unidades (si no viene, usa frontQty)
                        $requestedUnits = (float)($c->units_equivalent ?? $frontQty);

                        if ($requestedUnits <= 0) {
                            throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                        }
                    }

                    // ============================
                    // 3) Stock disponible
                    // ============================
                    $available = (float)$material->stock_current - (float)$material->stock_reserved;

                    if ($requestedUnits > $available) {
                        throw new \Exception(
                            "El material {$material->full_name} no cuenta con stock suficiente. " .
                            "Requerido: {$requestedUnits}. Disponible: {$available}."
                        );
                    }

                    $availability = ($requestedUnits > (float)$material->stock_current) ? 'Agotado' : 'Completo';
                    $state        = ($requestedUnits > (float)$material->stock_current) ? 'Falta comprar' : 'En compra';

                    // ============================
                    // 4) Guardar consumable (decimales reales)
                    // ============================
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => $availability,
                        'state' => $state,
                        'equipment_id' => $equipment->id,
                        'material_id' => $materialId,

                        // ✅ SIEMPRE stock en unidades equivalentes
                        'quantity' => $requestedUnits,

                        // ✅ Guardar EXACTO lo real que viene del front (decimal 20,10)
                        'price' => $priceReal,                    // con IGV (pack o unidad)
                        'valor_unitario' => $valorUnitarioReal,   // sin IGV (pack o unidad)
                        'total' => $importeReal,                  // total con IGV (ya calculado)

                        'discount' => (string)($c->discount ?? '0'),
                        'type_promo' => $c->type_promo ?? null,

                        'material_presentation_id' => $presentationId,
                        'packs' => $packs,
                        'units_per_pack' => $unitsPerPack,
                    ]);

                    // ============================
                    // 5) Total consumables con precisión
                    // ============================
                    // Ideal: sumar el mismo string que guardas (importeReal)
                    $totalConsumable = bcadd($totalConsumable, $importeReal, 10);

                    // ============================
                    // 6) Reservas (unidades equivalentes)
                    // ============================
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $materialId)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity += $requestedUnits;
                        $reservation->save();
                    } else {
                        QuoteMaterialReservation::create([
                            'quote_id' => $quote->id,
                            'material_id' => $materialId,
                            'quantity' => $requestedUnits,
                        ]);
                    }

                    $material->stock_reserved += $requestedUnits;
                    $material->save();

                    // ============================
                    // 7) Promo limit (valida y registra uso) - usa unidades equivalentes
                    // ============================
                    if (($c->type_promo ?? null) === "limit") {

                        $promotion = PromotionLimit::where('material_id', $materialId)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                            if ($promotion->applies_to === 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            // ✅ Validar antes de sumar
                            $alreadyUsed = $usage ? (float)$usage->used_quantity : 0.0;
                            $remaining = (float)$promotion->limit_quantity - $alreadyUsed;

                            if ($remaining < $requestedUnits) {
                                throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                            }

                            if (!$usage) {
                                PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to === 'worker' ? auth()->id() : null,
                                    'used_quantity' => $requestedUnits,
                                    'equipment_id' => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id' => $equipment->quote_id,
                                ]);
                            } else {
                                $usage->used_quantity += $requestedUnits;
                                $usage->equipment_id = $equipment->id;
                                $usage->equipment_consumable_id = $equipmentConsumable->id;
                                $usage->quote_id = $equipment->quote_id;
                                $usage->save();
                            }
                        }
                    }
                }

                // WORKFORCES (servicios adicionales)
                // - Guarda billable
                // - SIEMPRE suma al total del equipo/cotización
                for ($w = 0; $w < sizeof($workforces); $w++) {

                    $billable = isset($workforces[$w]->billable)
                        ? (int)$workforces[$w]->billable
                        : 1;

                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description ?? '',
                        'price' => (float)($workforces[$w]->price ?? 0),
                        'quantity' => (float)($workforces[$w]->quantity ?? 0),
                        'total' => (float)($workforces[$w]->importe ?? ($workforces[$w]->total ?? 0)),
                        'unit' => $workforces[$w]->unit ?? '',
                        'billable' => $billable,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                // Totales del equipo (sin cambios)
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo * ((($equipment->utility / 100) + 1));
                $totalEquipmentL = $totalEquipmentU * ((($equipment->letter / 100) + 1));
                $totalEquipmentR = $totalEquipmentL * ((($equipment->rent / 100) + 1));

                //$totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;
                $equipment->save();
            }

            $quote->save();

            $notification = Notification::create([
                'content' => $quote->code . ' creada por ' . Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
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

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }

        return response()->json(['message' => 'Cotización ' . $codeQuote . ' guardada con éxito.'], 200);
    }

    public function updateDatosGeneral(Request $request)
    {
        $quote = Quote::findOrFail($request->get('quote_id'));

        try {
            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote'   => $request->get('descriptionQuote'),
                'code'                => $request->get('codeQuote'),
                'date_quote'          => $dateQuote,
                'date_validate'       => $dateValidate,
                'way_to_pay'          => $request->get('way_to_pay') ?? '',
                'delivery_time'       => $request->get('delivery_time') ?? '',
                'customer_id'         => $request->get('customer_id') ?? null,
                'contact_id'          => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations'        => $request->get('observations') ?? '',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Datos generales actualizados correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
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
            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereDoesntHave('sales')
                ->orderBy('created_at', 'DESC');

        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->whereDoesntHave('sales')
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
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close');
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

                    $state = 'raise';
                    $stateText = '<span class="badge bg-success">Elevada</span>';
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

            $total_workforce  = 0;
            foreach($quote->equipments as $equipment)
            {
                foreach($equipment->workforces as $workforce)
                {
                    if ( $workforce->billable == false )
                    {
                        $total_workforce = $total_workforce + $workforce->total;
                    }

                }
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
                "total_sunat" => number_format($quote->total_importe, 2),
                "total_cliente" => number_format($quote->total_importe+$total_workforce, 2),
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

    public function getDataQuotesSalesIndex(Request $request, $pageNumber = 1)
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
            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereHas('sales')
                ->orderBy('created_at', 'DESC');

        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->whereHas('sales')
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
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close');
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

                    $state = 'raise';
                    $stateText = '<span class="badge bg-success">Elevada</span>';
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
                "total_igv" => number_format($quote->total_importe/1.18, 2),
                "total" => number_format($quote->total_importe, 2),
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
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables.presentation', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();

        $images = [];

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

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Editar cotizacion VISTA',
            'time' => $end
        ]);


        /*dump($quote->total_equipments);
        dd();*/

        return view('quoteSale.edit', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'images', 'array', 'currency', 'igv'));

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

                    for ($k = 0; $k < sizeof($consumables); $k++) {
                        $material = Material::find($consumables[$k]->id);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumables[$k]->type_promo == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumables[$k]->id)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumables[$k]->quantity;
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        // 🟢 REGISTRAR EQUIPMENT CONSUMABLE
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado' : 'Completo',
                            'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar' : 'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumables[$k]->id,
                            'quantity' => (float) $consumables[$k]->quantity,
                            'price' => (float) $consumables[$k]->price,
                            'valor_unitario' => (float) $consumables[$k]->valor,
                            'discount' => (float) $consumables[$k]->discount,
                            'total' => (float) $consumables[$k]->importe,
                            'type_promo' => $consumables[$k]->type_promo,
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
        DB::transaction(function () use ($quote) {

            /*
             * 1) LIBERAR RESERVAS DE MATERIALES (QuoteMaterialReservation)
             *    y AJUSTAR stock_reserved EN Material
             */
            $reservations = QuoteMaterialReservation::where('quote_id', $quote->id)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $material = Material::lockForUpdate()->find($reservation->material_id);

                if ($material) {
                    // Restar la cantidad reservada y evitar negativos
                    $material->stock_reserved = max(
                        0,
                        (float) $material->stock_reserved - (float) $reservation->quantity
                    );
                    $material->save();
                }

                // Borramos la reserva de esta cotización
                $reservation->delete();
            }

            /*
             * 2) LIMPIAR EQUIPOS Y CONSUMIBLES LIGADOS A LA COTIZACIÓN
             *    - Eliminar PromotionUsage por consumible
             *    - Eliminar consumibles
             *    - Eliminar equipos
             */
            $equipments = Equipment::where('quote_id', $quote->id)
                ->with('consumables')   // relación: equipment -> consumables
                ->get();

            foreach ($equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {

                    // Borrar usos de promociones ligados a este consumible
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();

                    // Borrar el consumible
                    //$consumable->delete();
                }

                // Borrar el equipo
                //$equipment->delete();
            }

            /*
             * 3) MARCAR LA COTIZACIÓN COMO ANULADA
             */
            $quote->state = 'canceled';
            $quote->save();
        });

        return response()->json(['ok' => true]);
    }

    public function updateEquipmentOfQuoteOriginal(Request $request, $id_equipment, $id_quote)
    {
        //dump($request);
        //dd();
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();

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

                    /*for ($k = 0; $k < sizeof($consumables); $k++) {
                        $material = Material::find($consumables[$k]->id);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumables[$k]->type_promo == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumables[$k]->id)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumables[$k]->quantity;
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        // 🟢 REGISTRAR EQUIPMENT CONSUMABLE
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado' : 'Completo',
                            'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar' : 'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumables[$k]->id,
                            'quantity' => (float) $consumables[$k]->quantity,
                            'price' => (float) $consumables[$k]->price,
                            'valor_unitario' => (float) $consumables[$k]->valor,
                            'discount' => (float) $consumables[$k]->discount,
                            'total' => (float) $consumables[$k]->importe,
                            'type_promo' => $consumables[$k]->type_promo,
                        ]);

                        $totalConsumable += $equipmentConsumable->total;
                    }*/

                    foreach ( $consumables as $consumable )
                    {
                        $material = Material::find((int)$consumable['id']);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumables['type_promo'],
                        ]);
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

                // Guardamos los totales
                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;

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

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumables['type_promo'],
                        ]);
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
                }

                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;

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

    public function updateEquipmentOfQuote2(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();

        $equipmentSent = null;

        DB::beginTransaction();
        try {
            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            $output_details= OutputDetail::where('equipment_id', $equipment_quote->id)->get();

            if ( count($output_details) == 0 )
            {
                // TODO: Si no hay outputs details que proceda como estaba planificado
                foreach( $equipment_quote->materials as $material ) {
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    // 🧮 devolver reserva de este consumible
                    $material = Material::lockForUpdate()->find($consumable->material_id);

                    if ($material) {
                        // buscar la reserva total de este material para esta cotización
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $consumable->material_id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity -= (float) $consumable->quantity;

                            if ($reservation->quantity <= 0) {
                                $reservation->delete();
                            } else {
                                $reservation->save();
                            }
                        }

                        // restar del stock_reserved del material
                        $material->stock_reserved -= (float) $consumable->quantity;
                        if ($material->stock_reserved < 0) {
                            $material->stock_reserved = 0; // por seguridad
                        }
                        $material->save();
                    }
                    // 🟢 eliminar usages ligados
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();
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

                    $materials = $equip['materials'];
                    $consumables = $equip['consumables'];
                    $electrics = $equip['electrics'];
                    $workforces = $equip['workforces'];
                    $tornos = $equip['tornos'];
                    $dias = $equip['dias'];

                    foreach ( $materials as $material )
                    {
                        EquipmentMaterial::create([
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
                    }

                    foreach ( $consumables as $consumable )
                    {
                        // 🔒 bloquear material para evitar race conditions
                        $material = Material::lockForUpdate()->find((int)$consumable['id']);

                        if (!$material) {
                            throw new \Exception("El material con ID {$consumable['id']} no existe.");
                        }

                        $requestedQty = (float) $consumable['quantity'];
                        $available = (float) $material->stock_current - (float) $material->stock_reserved;

                        if ($requestedQty > $available) {
                            throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                        }

                        // 🟢 PRIMERO crear consumible
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumable['type_promo'],
                        ]);

                        // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $material->id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity += $requestedQty;
                            $reservation->save();
                        } else {
                            $reservation = QuoteMaterialReservation::create([
                                'quote_id'    => $quote->id,
                                'material_id' => $material->id,
                                'quantity'    => $requestedQty,
                            ]);
                        }

                        // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                        $material->stock_reserved += $requestedQty;
                        $material->save();

                        // 🟢 Luego validar promoción
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id)
                                    ->where('quote_id', $quote->id)
                                    ->where('equipment_id', $equipment->id)
                                    ->where('equipment_consumable_id', $equipmentConsumable->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'quote_id' => $quote->id,
                                        'equipment_id' => $equipment->id,
                                        'equipment_consumable_id' => $equipmentConsumable->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }
                    }

                    foreach ( $electrics as $electricd )
                    {
                        EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electricd['id'],
                            'quantity' => (float) $electricd['quantity'],
                            'price' => (float) $electricd['price'],
                            'total' => (float) $electricd['quantity']*(float) $electricd['price'],
                        ]);
                    }

                    foreach ( $workforces as $workforce )
                    {
                        EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);
                    }

                    foreach ( $tornos as $torno )
                    {
                        EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);
                    }

                    foreach ( $dias as $dia )
                    {
                        EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);
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

                // Guardamos los totales
                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;
                $quote->save();

            } else {
                // 🟢 Ya no eliminamos el equipo, solo lo modificamos
                foreach( $equipment_quote->materials as $material ) {
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    // 🧮 devolver reserva de este consumible
                    $material = Material::lockForUpdate()->find($consumable->material_id);

                    if ($material) {
                        // buscar la reserva total de este material para esta cotización
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $consumable->material_id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity -= (float) $consumable->quantity;

                            if ($reservation->quantity <= 0) {
                                $reservation->delete();
                            } else {
                                $reservation->save();
                            }
                        }

                        // restar del stock_reserved del material
                        $material->stock_reserved -= (float) $consumable->quantity;
                        if ($material->stock_reserved < 0) {
                            $material->stock_reserved = 0; // por seguridad
                        }
                        $material->save();
                    }
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();
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

                $quote->save();

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

                    $materials = $equip['materials'];
                    $consumables = $equip['consumables'];
                    $electrics = $equip['electrics'];
                    $workforces = $equip['workforces'];
                    $tornos = $equip['tornos'];
                    $dias = $equip['dias'];

                    foreach ( $materials as $material )
                    {
                        EquipmentMaterial::create([
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
                    }

                    foreach ( $consumables as $consumable )
                    {
                        // 🔒 bloquear material para evitar race conditions
                        $material = Material::lockForUpdate()->find((int)$consumable['id']);

                        if (!$material) {
                            throw new \Exception("El material con ID {$consumable['id']} no existe.");
                        }

                        $requestedQty = (float) $consumable['quantity'];
                        $available = (float) $material->stock_current - (float) $material->stock_reserved;

                        if ($requestedQty > $available) {
                            throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumable['type_promo'],
                        ]);

                        // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $material->id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity += $requestedQty;
                            $reservation->save();
                        } else {
                            $reservation = QuoteMaterialReservation::create([
                                'quote_id'    => $quote->id,
                                'material_id' => $material->id,
                                'quantity'    => $requestedQty,
                            ]);
                        }

                        // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                        $material->stock_reserved += $requestedQty;
                        $material->save();

                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id)
                                    ->where('quote_id', $quote->id)
                                    ->where('equipment_id', $equipment_quote->id)
                                    ->where('equipment_consumable_id', $equipmentConsumable->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'quote_id' => $quote->id,
                                        'equipment_id' => $equipment_quote->id,
                                        'equipment_consumable_id' => $equipmentConsumable->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }
                    }

                    foreach ( $electrics as $electric )
                    {
                        EquipmentElectric::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $electric['id'],
                            'quantity' => (float) $electric['quantity'],
                            'price' => (float) $electric['price'],
                            'total' => (float) $electric['quantity']*(float) $electric['price'],
                        ]);
                    }

                    foreach ( $workforces as $workforce )
                    {
                        EquipmentWorkforce::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);
                    }

                    foreach ( $tornos as $torno )
                    {
                        EquipmentTurnstile::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);
                    }

                    foreach ( $dias as $dia )
                    {
                        EquipmentWorkday::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);
                    }
                }

                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;
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

    public function updateEquipmentOfQuote(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);

        $quote = Quote::findOrFail($id_quote);

        DB::beginTransaction();
        try {

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id', $quote->id)
                ->firstOrFail();

            /**
             * 1) Guardar totales de Quote + metadata del descuento
             * AHORA vienen del front como:
             * - descuento, gravada, igv_total, total_importe (reales 10 dec)
             */
            $quote->descuento      = (string)($request->input('descuento', '0'));
            $quote->gravada        = (string)($request->input('gravada', '0'));
            $quote->igv_total      = (string)($request->input('igv_total', '0'));
            $quote->total_importe  = (string)($request->input('total_importe', '0'));

            $quote->discount_type       = $request->input('discount_type');        // amount|percent
            $quote->discount_input_mode = $request->input('discount_input_mode');  // with_igv|without_igv
            $quote->discount_input_value= $request->input('discount_input_value'); // numeric
            $quote->save();

            /**
             * 2) Eliminar consumables antiguos y devolver reservas
             * quantity en BD = unidades equivalentes (stock)
             */
            foreach ($equipment_quote->consumables as $consumable) {

                $material = Material::lockForUpdate()->find($consumable->material_id);

                if ($material) {
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $consumable->material_id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity -= (float)$consumable->quantity;
                        if ($reservation->quantity <= 0) $reservation->delete();
                        else $reservation->save();
                    }

                    $material->stock_reserved -= (float)$consumable->quantity;
                    if ($material->stock_reserved < 0) $material->stock_reserved = 0;
                    $material->save();
                }

                $consumable->delete();
            }

            // Limpiar relaciones restantes (siempre recreamos)
            foreach ($equipment_quote->workforces as $workforce) $workforce->delete();
            foreach ($equipment_quote->materials as $m) $m->delete();
            foreach ($equipment_quote->electrics as $e) $e->delete();
            foreach ($equipment_quote->turnstiles as $t) $t->delete();
            foreach ($equipment_quote->workdays as $w) $w->delete();

            /**
             * 3) Payload (array con 1 equipo)
             */
            $equipments = $request->input('equipment');
            if (!is_array($equipments) || count($equipments) === 0) {
                throw new \Exception('No se recibieron equipos.');
            }
            $equip = $equipments[0];

            /**
             * 4) Actualizar equipo principal
             */
            $equipment_quote->description = $equip['description'] ?? '';
            $equipment_quote->detail      = $equip['detail'] ?? '';
            $equipment_quote->quantity    = $equip['quantity'] ?? 1;
            $equipment_quote->utility     = $equip['utility'] ?? 0;
            $equipment_quote->rent        = $equip['rent'] ?? 0;
            $equipment_quote->letter      = $equip['letter'] ?? 0;

            // total del equipo con IGV (real 10 dec)
            $equipment_quote->total       = (string)($equip['total'] ?? '0');
            $equipment_quote->save();

            /**
             * 5) Re-crear consumables
             * - En BD: quantity SIEMPRE unidades equivalentes (stock)
             * - price / valor_unitario / total: VIENEN DEL FRONT EN REAL (10 dec)
             */
            $consumables = $equip['consumables'] ?? [];

            // Para sumar con precisión si lo necesitas
            @bcscale(10);

            foreach ($consumables as $c) {

                $materialId = (int)($c['id'] ?? 0);
                $material = Material::lockForUpdate()->find($materialId);

                if (!$material) {
                    throw new \Exception("El material con ID {$materialId} no existe.");
                }

                // --- presentación solo para stock/reserva ---
                $presentationId = $c['presentation_id'] ?? null;
                $packs = null;
                $unitsPerPack = null;

                // units equivalentes a reservar
                $requestedUnits = 0;

                if (!empty($presentationId)) {

                    // En UI quantity = packs
                    $packs = (int)($c['quantity'] ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Packs inválidos para {$material->full_name}.");
                    }

                    // Validar presentación real
                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("Presentación inválida para {$material->full_name}.");
                    }

                    $unitsPerPack = (int)$presentation->quantity;
                    if ($unitsPerPack < 1) {
                        throw new \Exception("Units_per_pack inválido para {$material->full_name}.");
                    }

                    $requestedUnits = (float)($packs * $unitsPerPack);

                } else {

                    // Unitario: units_equivalent debe ser unidades (si no viene, usa quantity)
                    $requestedUnits = (float)($c['units_equivalent'] ?? ($c['quantity'] ?? 0));
                    if ($requestedUnits <= 0) {
                        throw new \Exception("Cantidad inválida para {$material->full_name}.");
                    }
                }

                // --- Dinero (NO recalcular): guardar lo real del front ---
                $valorUnitarioReal = (string)($c['valorReal'] ?? $c['valor'] ?? '0'); // sin IGV
                $priceReal         = (string)($c['priceReal'] ?? $c['price'] ?? '0'); // con IGV
                $importeReal       = (string)($c['importe'] ?? '0');                  // total con IGV

                if ((float)$valorUnitarioReal < 0 || (float)$priceReal < 0 || (float)$importeReal < 0) {
                    throw new \Exception("Valores inválidos (precio/valor/total) para {$material->full_name}.");
                }

                // validar stock disponible (considera reservas)
                $available = (float)$material->stock_current - (float)$material->stock_reserved;
                if ($requestedUnits > $available) {
                    throw new \Exception("Stock insuficiente para {$material->full_name}. Requerido: {$requestedUnits}. Disponible: {$available}.");
                }

                $availability = ($requestedUnits > (float)$material->stock_current) ? 'Agotado' : 'Completo';
                $state        = ($requestedUnits > (float)$material->stock_current) ? 'Falta comprar' : 'En compra';

                // crear consumable (BD guarda unidades equivalentes)
                $equipmentConsumable = EquipmentConsumable::create([
                    'equipment_id' => $equipment_quote->id,
                    'material_id' => $materialId,

                    'material_presentation_id' => $presentationId,
                    'packs' => $packs,
                    'units_per_pack' => $unitsPerPack,

                    // ✅ stock (unidades equivalentes)
                    'quantity' => (float)$requestedUnits,

                    // ✅ dinero real (decimal 20,10)
                    'price' => $priceReal,
                    'valor_unitario' => $valorUnitarioReal,
                    'total' => $importeReal,

                    'discount' => (string)($c['discount'] ?? '0'),
                    'type_promo' => $c['type_promo'] ?? null,
                    'availability' => $availability,
                    'state' => $state,
                ]);

                // crear/actualizar reserva
                $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                    ->where('material_id', $materialId)
                    ->lockForUpdate()
                    ->first();

                if ($reservation) {
                    $reservation->quantity += $requestedUnits;
                    $reservation->save();
                } else {
                    QuoteMaterialReservation::create([
                        'quote_id' => $quote->id,
                        'material_id' => $materialId,
                        'quantity' => $requestedUnits,
                    ]);
                }

                // actualizar stock_reserved
                $material->stock_reserved += $requestedUnits;
                $material->save();
            }

            /**
             * 6) Re-crear workforces
             * (si tus workforces también tienen reales, aplícalo igual)
             */
            $workforces = $equip['workforces'] ?? [];

            foreach ($workforces as $w) {
                EquipmentWorkforce::create([
                    'equipment_id' => $equipment_quote->id,
                    'description' => $w['description'] ?? '',
                    'price' => (string)($w['price'] ?? '0'),        // con IGV
                    'quantity' => (float)($w['quantity'] ?? 0),
                    'total' => (string)($w['importe'] ?? '0'),      // con IGV
                    'unit' => $w['unit'] ?? '',
                    'billable' => isset($w['billable']) ? (int)$w['billable'] : 1,
                ]);
            }

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion (EDIT)',
                'time' => microtime(true) - $begin
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Equipo guardado con éxito.',
                'equipment' => $equipment_quote,
                'quote' => $quote
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function showRegistrarComprobante($typeComprobante)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $tipoPagos = TipoPago::all();

        return view('quoteSale.registrarComprobante', compact('typeComprobante', 'permissions', 'currency', 'igv', 'tipoPagos'));

    }

    public function buscar(Request $request)
    {
        $query = Quote::query();

        if ($request->has('code') && !empty($request->code)) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->has('name') && !empty($request->name)) {
            $query->where('description_quote', 'LIKE', '%' . $request->name . '%');
        }

        $quotes = $query->where('state', 'confirmed')
            ->whereDoesntHave('sales') // 👈 solo las que no tienen ventas
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                $quote->customer_name = $quote->customer_id
                    ? $quote->customer->business_name
                    : "";
                $quote->date_quote_format = $quote->date_quote
                    ? $quote->date_quote->format('d/m/Y')
                    : "";
                return $quote;
            });

        return response()->json($quotes);
    }

    public function getDataIndividual($id)
    {
        $quote = Quote::with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'consumables.material',
                    'workforces' // ✅ nuevo
                ]);
            }])->findOrFail($id);

        // Si quieres formatear la fecha:
        $quote->date_quote_format = $quote->date_quote ? $quote->date_quote->format('d/m/Y') : "";
        $quote->date_validate_format = $quote->date_validate ? $quote->date_validate->format('d/m/Y') : "";
        $quote->deadline_format = $quote->deadline ? $quote->deadline->description : "";
        $quote->customer_format = $quote->customer_id ? $quote->customer->business_name : "";
        $quote->contact_format = $quote->contact_id ? $quote->contact->name : "";

        return response()->json($quote);
    }

    public function storeFromQuoteO(Request $request)
    {
        $begin = microtime(true);
        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            // Validaciones mínimas
            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // Construcción de la venta
            $sale = Sale::create([
                // Si viene fechaDocumento la usamos, si no usamos la actual
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(), // método tuyo que genera serie
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD':'PEN',

                // Totales (tomados de la cotización)
                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                // Aquí usamos lo que envías
                'tipo_pago_id' => $request->tipoPago ?? 4,  // si no llega, por defecto efectivo

                // Datos de cliente
                'type_document' => $request->type_document,  // "01" factura, "03" boleta/ticket
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente, // "1" DNI, "6" RUC
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                // Campos SUNAT se llenarán luego
                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'fecha_emision' => null,
            ]);

            // ✅ 1) Crear Output (salida) asociada a la ejecución/orden de la cotización
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION':$quote->order_execution, // o $quote->execution_order si tu campo fuera otro
                'request_date'      => $sale->date_sale,        // o Carbon::now()
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),              // si luego lo asignas a almacenero, lo puedes cambiar
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // Ahora los detalles
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'material_id' => $consumable->material_id,
                        'price' => $consumable->price,
                        'quantity' => $consumable->quantity,
                        'percentage_tax' => 18, // Asumiendo IGV fijo, ajusta si es dinámico
                        'total' => $consumable->total,
                        'discount' => $consumable->discount,
                    ]);

                    // Descontar stock del material directamente
                    $material = $consumable->material;
                    if ($material) {
                        $material->stock_current = max(0, $material->stock_current - $consumable->quantity);
                        $material->stock_current = max(0, $material->stock_reserved - $consumable->quantity);
                        $material->save();
                    }

                    /*$this->manageNotifications($material);*/

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    // ⚠️ por ahora solo soportamos cantidades enteras (material normal sin retazos)
                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ( $material->tipo_venta_id == 3 )
                    {
                        // ✅ 2.1) Obtener items disponibles con lock (evita carreras)
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            // si usas usage para filtrar disponibles:
                            // ->where('usage', '<>', 'finished')
                            ->orderBy('id', 'asc') // FIFO simple
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        // ✅ 2.2) Validar stock suficiente
                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        // ✅ 2.3) Crear OutputDetails + marcar items como exited
                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'    => $output->id,
                                'item_id'      => $item->id,
                                'material_id'  => $materialId,
                                'quote_id'     => $quote->id,
                                'custom'       => 0,
                                'percentage'   => 1,           // material normal => 1
                                'price'        => $item->price,        // opcional: puedes guardar el costo o precio de salida si quieres
                                'length'       => $item->length,
                                'width'        => $item->width,
                                'equipment_id' => $equipment->id ?? null,
                                'activo'       => null,        // no usar para lógica de salida (según me dijiste)
                            ]);

                            $item->state_item = 'exited';
                            // opcional si manejas usage:
                            // $item->usage = 'finished';
                            $item->save();
                        }
                    }
                }
            }
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->total_sales += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                CashMovement::create([
                    'cash_register_id' => $vueltoCashRegister->id,
                    'type' => 'expense', // Tipo de movimiento: egreso
                    'amount' => $vuelto,
                    'description' => 'Vuelto entregado de la venta',
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo de la caja del vuelto
                $vueltoCashRegister->current_balance -= $vuelto;
                $vueltoCashRegister->total_expenses += $vuelto;
                $vueltoCashRegister->save();
            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar venta desde cotización',
                'time' => $end
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Venta creada con éxito desde cotización',
                'sale_id' => $sale->id,
                'url_print' => route('puntoVenta.print', $sale->id)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuote(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // 1) Crear venta
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD' : 'PEN',

                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                'tipo_pago_id' => $request->tipoPago ?? 4,

                'type_document' => $request->type_document,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente,
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'pdf_path' => null,
                'fecha_emision' => null,
            ]);

            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            // recolectar materialIds
            $materialIds = [];
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    $materialIds[] = (int)$consumable->material_id;
                }
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material hasta la fecha de emisión
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // 2) Crear Output
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // 3) Detalles + stock + output details
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {

                    $materialId = (int)$consumable->material_id;
                    $qty = (float)$consumable->quantity; // OJO: confirmaste que quantity siempre es real a descontar

                    $unitCost = (float)($avgCosts[$materialId] ?? 0.0);
                    $totalCost = $qty * $unitCost;

                    $saleDetail = SaleDetail::create([
                        'sale_id'                   => $sale->id,
                        'material_id'               => $consumable->material_id,
                        'material_presentation_id'  => $consumable->material_presentation_id,
                        'valor_unitario'            => $consumable->valor_unitario,
                        'price'                     => $consumable->price,
                        'quantity'                  => $consumable->quantity,
                        'packs'                     => $consumable->packs,           // ✅ si hay presentación
                        'units_per_pack'            => $consumable->units_per_pack,    // ✅ si hay presentación
                        'percentage_tax'            => 18,
                        'total'                     => $consumable->total,
                        'discount'                  => $consumable->discount,

                        // ✅ snapshot de costos para utilidad histórica
                        'unit_cost'                 => $unitCost,
                        'total_cost'                => $totalCost,
                    ]);

                    $material = $consumable->material;

                    if ($material) {
                        $material->stock_current  = max(0, (float)$material->stock_current  - (float)$consumable->quantity);
                        $material->stock_reserved = max(0, (float)$material->stock_reserved - (float)$consumable->quantity);
                        $material->save();
                    }

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ($material && (int)$material->tipo_venta_id === 3) {
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id, // ✅ enlace a la línea de venta
                                'item_id'        => $item->id,
                                'material_id'    => $materialId,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => 1,
                                'price'          => $item->price,
                                'length'         => $item->length,
                                'width'          => $item->width,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                // ✅ costo promedio aplicado (misma base)
                                'unit_cost'      => $unitCost,
                                'total_cost'     => $unitCost,
                            ]);

                            $item->state_item = 'exited';
                            $item->save();
                        }
                    }

                    if ($material && (int)$material->tipo_venta_id !== 3) {
                        // ✅ NO ITEMEABLE: 1 OutputDetail por línea, con item_id NULL
                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => null,
                            'material_id'    => $materialId,
                            'quote_id'       => $quote->id,
                            'custom'         => 0,

                            // tu VIEW usa percentage como "cantidad real"
                            'percentage'     => $qty,

                            // opcional, si tu tabla los necesita
                            'price'          => $consumable->price,
                            'length'         => null,
                            'width'          => null,
                            'equipment_id'   => $equipment->id ?? null,
                            'activo'         => null,

                            // (Opcional pero recomendado para auditoría)
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $totalCost,
                        ]);
                    }
                }
            }

            // 4) Caja + notificaciones + auditoría (tu misma lógica tal cual)
            // ... (aquí pega tu bloque tal como lo tienes)
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += $quote->total_importe;
                $cashRegister->total_sales += $quote->total_importe;
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                if ( $vuelto > 0)
                {
                    CashMovement::create([
                        'cash_register_id' => $vueltoCashRegister->id,
                        'type' => 'expense', // Tipo de movimiento: egreso
                        'amount' => $vuelto,
                        'description' => 'Vuelto entregado de la venta',
                        'sale_id' => $sale->id
                    ]);

                    // Actualizar el saldo de la caja del vuelto
                    $vueltoCashRegister->current_balance -= $vuelto;
                    $vueltoCashRegister->total_expenses += $vuelto;
                    $vueltoCashRegister->save();
                }

            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action'  => 'Guardar venta desde cotización',
                'time'    => $end
            ]);

            DB::commit();

            // ✅ 5) Generar comprobante Nubefact (FUERA de la transacción)
            $nubefactResult = null;
            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);
                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);

                    // Guardar archivos y actualizar campos (reusamos lógica similar a tu NubefactController)
                    $filename = 'ORD' . $sale->id;
                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        if (!file_exists(public_path("comprobantes/$folder"))) {
                            mkdir(public_path("comprobantes/$folder"), 0777, true);
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        $pdfContent = Http::get($nubefactResult['enlace_del_pdf'])->body();
                        file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
                    }
                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        $xmlContent = Http::get($nubefactResult['enlace_del_xml'])->body();
                        file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
                    }
                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        $cdrContent = Http::get($nubefactResult['enlace_del_cdr'])->body();
                        file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
                    }

                    $sale->update([
                        'serie_sunat' => $nubefactResult['serie'] ?? null,
                        'numero' => $nubefactResult['numero'] ?? null,
                        'sunat_ticket' => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status' => $nubefactResult['sunat_description'] ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'xml_path' => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                        'cdr_path' => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                        'pdf_path' => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                        'fecha_emision' => now()->toDateString(),
                    ]);

                } catch (\Throwable $e) {
                    // Si Nubefact falla, NO tumbamos la venta: devolvemos warning
                    $sale->update([
                        'sunat_status' => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            // Por defecto: ticket interno
            $urlPrint = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            // Si es boleta/factura, preferimos PDF SUNAT (si existe)
            if (in_array($sale->type_document, ['01', '03'])) {

                // Caso A: tenemos link directo de nubefact en la respuesta
                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }

                // Caso B (recomendado): usar el PDF guardado localmente (si existe)
                if (!empty($sale->pdf_path)) {
                    // Prioridad 1: PDF guardado localmente
                    if (!empty($sale->pdf_path)) {
                        $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                        if (file_exists($localPath)) {
                            $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                            $printType = 'sunat_pdf';
                        }
                    }
                    // Prioridad 2: enlace directo de Nubefact
                    elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }
            return response()->json([
                'message' => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id' => $sale->id,
                'nubefact' => $nubefactResult,
                'url_print' => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
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

    public function manageNotifications(Material $material)
    {
        $dataGeneralTypeNotificationPopUp = DataGeneral::where('name', 'send_notification_store_pop_up')->first();
        $dataGeneralTypeNotificationCampana = DataGeneral::where('name', 'send_notification_store_campana')->first();
        $dataGeneralTypeNotificationTelegram = DataGeneral::where('name', 'send_notification_store_email')->first();
        $dataGeneralTypeNotificationEmail = DataGeneral::where('name', 'send_notification_store_telegram')->first();

        // Texto base
        $content = 'El producto '.$material->full_name.' está por agotarse.';

        $nameMaterial = $material->full_name;

        // Obtener usuarios con roles específicos (excepto el actual)
        $users = User::role(['admin', 'principal', 'logistic'])->where('id', '!=', Auth::id())->get();

        if ($dataGeneralTypeNotificationCampana && $dataGeneralTypeNotificationCampana->valueText === 's')
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
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

        if ($dataGeneralTypeNotificationPopUp && $dataGeneralTypeNotificationPopUp->valueText === 's')
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock_pop_up',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
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

        if ($dataGeneralTypeNotificationEmail && $dataGeneralTypeNotificationEmail->valueText === 's')
        {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new StockLowNotificationMail($nameMaterial));
            }
        }

        // Si deseas dejar el código preparado para Telegram:
        if ($dataGeneralTypeNotificationTelegram && $dataGeneralTypeNotificationTelegram->valueText === 's')
        {
            $telegram = new TelegramController();

            // Enviar al canal de procesos
            $telegram->sendNotification('📦 El producto XYZ está por agotarse.', 'process');
        }


    }

    public function raiseQuote(Request $request, $quote_id)
    {
        $begin = microtime(true);
        $quote = Quote::find($quote_id);
        // Leer el código enviado desde el popup
        $code = trim($request->input('code_customer', ''));

        if ($code === '') {
            return response()->json(['message' => 'El código de cliente es obligatorio.'], 422);
        }

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

            // TODO: Actualizar los stocks
            $equipment_materials = $quote->equipments;
            foreach ( $equipment_materials as $equipment )
            {
                $quote_materials = $equipment->consumables;
                foreach ( $quote_materials as $consumable )
                {
                    $mat = Material::find($consumable->material_id);
                    $quantity = $consumable->quantity;
                    $mat->stock_current = $mat->stock_current - $quantity;
                    $mat->save();
                }
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

    public function printQuoteToCustomer($id)
    {
        // Eliminamos elos archivos
        $files = glob(public_path().'/pdfs/*');
        foreach($files as $file){
            if(is_file($file))
                unlink($file);
        }

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('users')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables.material', 'workforces', 'turnstiles']);
            }])->first();


        $images = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'img')
            ->orderBy('order', 'ASC')->get();

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $monedaTexto = $quote->currency_invoice === 'USD'
            ? 'DÓLARES'
            : 'SOLES';

        $total_workforce  = 0;
        foreach($quote->equipments as $equipment)
        {
            foreach($equipment->workforces as $workforce)
            {
                if ( $workforce->billable == false )
                {
                    $total_workforce = $total_workforce + $workforce->total;
                }

            }
        }

        $montoEnLetras = numeroALetras($quote->total_importe+$total_workforce, $monedaTexto);

        $dataNombreEmpresa = DataGeneral::where('name', 'empresa')->first();
        $nombreEmpresa = $dataNombreEmpresa->valueText;
        $dataDireccionEmpresa = DataGeneral::where('name', 'address')->first();
        $direccionEmpresa = $dataDireccionEmpresa->valueText;
        $dataTelefonoEmpresa = DataGeneral::where('name', 'telefono')->first();
        $telefonoEmpresa = $dataTelefonoEmpresa->valueText;
        $dataEmailEmpresa = DataGeneral::where('name', 'email')->first();
        $emailEmpresa = $dataEmailEmpresa->valueText;
        $dataWebEmpresa = DataGeneral::where('name', 'web')->first();
        $webEmpresa = $dataWebEmpresa->valueText;
        $dataRucEmpresa = DataGeneral::where('name', 'ruc')->first();
        $rucEmpresa = $dataRucEmpresa->valueText;
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;

        $dataVersiculoEmpresa = DataGeneral::where('name', 'versiculo')->first();
        $versiculoEmpresa = $dataVersiculoEmpresa->valueText;
        $dataCitaBiblicaEmpresa = DataGeneral::where('name', 'cita_biblica')->first();
        $citaBiblicaEmpresa = $dataCitaBiblicaEmpresa->valueText;

        // Cuenta1
        $dataTitleCuenta1Empresa = DataGeneral::where('name', 'title_cuenta_1')->first();
        $titleCuenta1Empresa = $dataTitleCuenta1Empresa->valueText;
        $dataNroCuenta1Empresa = DataGeneral::where('name', 'nro_cuenta_1')->first();
        $nroCuenta1Empresa = $dataNroCuenta1Empresa->valueText;
        $dataCciCuenta1Empresa = DataGeneral::where('name', 'cci_cuenta_1')->first();
        $cciCuenta1Empresa = $dataCciCuenta1Empresa->valueText;
        $dataImgCuenta1Empresa = DataGeneral::where('name', 'img_cuenta_1')->first();
        $imgCuenta1Empresa = $dataImgCuenta1Empresa->valueText;
        $dataOwnerCuenta1Empresa = DataGeneral::where('name', 'owner_cuenta_1')->first();
        $ownerCuenta1Empresa = $dataOwnerCuenta1Empresa->valueText;

        // Cuenta2
        $dataTitleCuenta2Empresa = DataGeneral::where('name', 'title_cuenta_2')->first();
        $titleCuenta2Empresa = $dataTitleCuenta2Empresa->valueText;
        $dataNroCuenta2Empresa = DataGeneral::where('name', 'nro_cuenta_2')->first();
        $nroCuenta2Empresa = $dataNroCuenta2Empresa->valueText;
        $dataCciCuenta2Empresa = DataGeneral::where('name', 'cci_cuenta_2')->first();
        $cciCuenta2Empresa = $dataCciCuenta2Empresa->valueText;
        $dataImgCuenta2Empresa = DataGeneral::where('name', 'img_cuenta_2')->first();
        $imgCuenta2Empresa = $dataImgCuenta2Empresa->valueText;
        $dataOwnerCuenta2Empresa = DataGeneral::where('name', 'owner_cuenta_2')->first();
        $ownerCuenta2Empresa = $dataOwnerCuenta2Empresa->valueText;

        $tieneCuentas = false;
        if ( $nroCuenta2Empresa != "" || $nroCuenta1Empresa != "" )
        {
            $tieneCuentas = true;
        }

        $view = view('exports.quoteSaleCustomerV2', compact(
            'logotipoEmpresa',
            'versiculoEmpresa',
            'citaBiblicaEmpresa',
            'titleCuenta1Empresa',
            'nroCuenta1Empresa',
            'cciCuenta1Empresa',
            'imgCuenta1Empresa',
            'ownerCuenta1Empresa',
            'titleCuenta2Empresa',
            'nroCuenta2Empresa',
            'cciCuenta2Empresa',
            'imgCuenta2Empresa',
            'ownerCuenta2Empresa',
            'tieneCuentas',
            'rucEmpresa','webEmpresa','emailEmpresa','telefonoEmpresa','direccionEmpresa','nombreEmpresa', 'quote', 'images', 'igv', 'montoEnLetras'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

        $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

        $image_path = public_path().'/pdfs/'.$name;
        //$image_path = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // = $pdf->output();

        //file_put_contents(public_path().'/pdfs/'.$name, $output);
        //file_put_contents('C:/wamp64/www/construction/public/pdfs/'.$name, $output);
        $pdfPrincipal = public_path().'/pdfs/'.$name;
        //$pdfPrincipal = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        //$oMerger = PDFMerger::init();

        //$oMerger->addPDF($pdfPrincipal, 'all');

        /*$pdfs = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'pdf')->get();

        foreach ( $pdfs as $pdf )
        {
            $namePdf = public_path().'/images/planos/'.$pdf->image;
            //$namePdf ='C:/wamp64/www/construction/public/images/planos/'.$pdf->image;
            $oMerger->addPDF($namePdf, 'all');
        }*/

        //$oMerger->merge();
        //$oMerger->setFileName($name);
        //$oMerger->stream();

        return $pdf->stream($name);
    }

    public function show($id)
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
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles', 'workdays']);
            }])->first();

        $images = [];

        $materials = Material::with('unitMeasure','typeScrap')
            /*->where('enable_status', 1)*/->get();

        /*$array = [];
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
        }*/

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion venta VISTA',
            'time' => $end
        ]);


        return view('quoteSale.show', compact('quote', 'unitMeasures', 'customers', 'consumables', 'electrics', 'workforces', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'images', 'currency', 'igv'));

    }
}
