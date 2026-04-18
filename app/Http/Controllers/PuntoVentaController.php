<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Category;
use App\DataGeneral;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Item;
use App\Mail\StockLowNotificationMail;
use App\MaterialPresentation;
use App\Output;
use App\OutputDetail;
use App\PorcentageQuote;
use App\QuoteMaterialReservation;
use App\Services\InventoryCostService;
use Illuminate\Support\Facades\Mail;
use App\Material;
use App\MaterialDiscountQuantity;
use App\Notification;
use App\NotificationUser;
use App\Sale;
use App\SaleDetail;
use App\StoreMaterial;
use App\TipoPago;
use App\User;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Http;

class PuntoVentaController extends Controller
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
        $categories = Category::all();
        $tipoPagos  = TipoPago::all();

        // Si no existe, se crea con valueText = 'no'
        $cfg = DataGeneral::firstOrCreate(
            ['name' => 'punto_venta_worker'],
            ['valueText' => 'no']
        );

        // True solo si está configurado en "si"
        $askWorker = strtolower($cfg->valueText) === 'si';

        // Lista de trabajadores habilitados
        $workers = Worker::where('enable', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $cashBoxes = CashBox::where('is_active', 1)->orderBy('position')->get();
        $subtypes = CashBoxSubtype::whereNull('cash_box_id')->where('is_active', 1)->orderBy('position')->get();


        return view('puntoVenta.index', compact(
            'categories',
            'tipoPagos',
            'askWorker',
            'workers',
            'cashBoxes','subtypes'
        ));
    }

    public function getDataProducts(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $category_id = $request->input('category_id');
        $product_search = $request->input('product_search');

        $materialIds = StoreMaterial::where('enable_status', 1)
            ->pluck('material_id')
            ->unique()
            ->toArray();

        $query = Material::where('enable_status', 1)
            /*->whereIn('id', $materialIds)*/
            ->where('stock_current', '>', 0)
            ->orderBy('id');

        // Aplicar filtros si se proporcionan

        if ($category_id != "") {
            $query->where('category_id', $category_id);

        }

        if ($product_search != "") {
            $search = trim($product_search);

            $query->where(function ($q) use ($search) {
                $q->where('codigo', $search) // <-- código/barcode exacto
                ->orWhere('full_name', 'like', '%'.$search.'%');
            });

            // Opcional: PRIORIDAD a los que matchean por código exacto (sale primero)
            $query->orderByRaw("CASE WHEN codigo = ? THEN 0 ELSE 1 END", [$search]);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $products = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $arrayProducts = [];

        foreach ( $products as $product )
        {
            $stock = Material::where('id', $product->id)
                    ->select('stock_current')
                    ->first()
                    ->stock_current ?? 0;
            array_push($arrayProducts, [
                "id" => $product->id,
                "full_name" => $product->full_name,
                "category" => ($product->category_id == null) ? '': $product->category->description,
                "price" => $product->list_price,
                "stock" => $stock,
                "image" => $product->image,
                "unit" => $product->unitMeasure->description,
                "tax" => ($product->type_tax_id == null) ? 18 : $product->typeTax->tax,
                "rating" => 4,
                "type" => ($product->tipo_venta_id == null) ? 0 : $product->tipoVenta->id,
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

        return ['data' => $arrayProducts, 'pagination' => $pagination];
    }

    public function getDiscountProduct(Request $request, $product_id)
    {
        $materialDiscounts = MaterialDiscountQuantity::where('material_id', $product_id)
            ->with(['discount', 'material'])
            ->join('discount_quantities', 'material_discount_quantities.discount_quantity_id', '=', 'discount_quantities.id')
            ->orderBy('discount_quantities.quantity', 'desc')
            ->select('material_discount_quantities.*') // Selecciona los campos de MaterialDiscountQuantity
            ->get();
        //dump($materialDiscounts);
        $quantity = $request->input('quantity');
        //dump("quantity ". $quantity);
        $arrayDiscount = [];

        $dataGeneralIGV = PorcentageQuote::where('name', 'igv')->first();
        $igvdata = $dataGeneralIGV->value;

        $igv = round((100 + $igvdata)/100 , 2 );
        foreach ( $materialDiscounts as $materialDiscount )
        {
            $cantidad = $materialDiscount->discount->quantity;
            //dump("quantity ". $quantity);
            //dump("cantidad ". $cantidad);
            if ( $quantity >= $cantidad )
            {
                $material = $materialDiscount->material;
                //dump($material);
                // Aquí es donde aplicas el descuento
                array_push($arrayDiscount, [
                    "id" => $materialDiscount->id,
                    "percentage" => $materialDiscount->percentage,
                    "haveDiscount" => true,
                    "valueDiscount" => round(($material->list_price/$igv)*($materialDiscount->percentage/100)*$quantity, 2),
                    "stringDiscount" => "<p>Dscto. ".$materialDiscount->discount->description." <strong class='float-right'> ".round(($material->list_price/$igv)*($materialDiscount->percentage/100), 2)."</strong></p>",
                ]);

                // Salir del bucle una vez que se ha encontrado el descuento aplicable
                break;
            }
        }

        if ( count($arrayDiscount) == 0 )
        {
            array_push($arrayDiscount, [
                "id" => null,
                "percentage" => null,
                "haveDiscount" => false,
                "valueDiscount" => 0,
                "stringDiscount" => ""
            ]);
        }

        //dump($arrayDiscount);

        return ['data' => $arrayDiscount];
    }

    public function storeO(Request $request)
    {
        $begin = microtime(true);
        $workerDefault = Worker::where('user_id', Auth::user()->id)->first();
        $workerId = $request->input('worker_id');


        DB::beginTransaction();
        try {

            $items = json_decode($request->get('items'));

            // Validar que haya stock suficiente para todos los productos ANTES de crear la venta
            foreach ($items as $item) {

                $materialId = (int) $item->productId;

                $material = Material::find($materialId);
                if (!$material) {
                    throw new \Exception("Material con ID {$materialId} no encontrado.");
                }

                $currentQuantityStore = (float) $material->stock_current;

                $presentationId = $item->presentationId ?? null;

                // ✅ calcular unidades a descontar (SIEMPRE server-side)
                if (!empty($presentationId)) {

                    $packs = (int) ($item->productQuantity ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Cantidad de paquetes inválida para el producto '{$material->description}'.");
                    }

                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("La presentación seleccionada no es válida o no pertenece al material '{$material->description}'.");
                    }

                    $unitsPerPack = (int) $presentation->quantity;
                    if ($unitsPerPack < 1) {
                        throw new \Exception("La presentación tiene cantidad inválida para el material '{$material->description}'.");
                    }

                    $units = $packs * $unitsPerPack; // ✅ unidades reales que se descuentan

                } else {

                    $units = (float) ($item->productQuantity ?? 0);
                    if ($units <= 0) {
                        throw new \Exception("Cantidad inválida para el producto '{$material->description}'.");
                    }
                }

                if ($currentQuantityStore < $units) {
                    throw new \Exception(
                        "Stock insuficiente para el producto '{$material->description}'. " .
                        "Disponible: {$material->stock_current}, requerido: {$units}"
                    );
                }
            }

            // TODO: VALIDACIONES DE INVOICE
            // Determinar tipo de documento SUNAT
            $type_document = null;
            $numero_documento_cliente = null;
            $nombre_cliente = null;
            $direccion_cliente = null;
            $tipo_documento_cliente = null;
            $email_cliente = null;

            // Asignar según el tipo de comprobante
            if ($request->invoice_type === 'boleta') {
                $type_document = '03'; // Boleta
                $numero_documento_cliente = $request->dni;
                $nombre_cliente = $request->name;
                $direccion_cliente = $request->address;
                $tipo_documento_cliente = '1'; // DNI
                $email_cliente = $request->email_invoice_boleta;
            } elseif ($request->invoice_type === 'factura') {
                $type_document = '01'; // Factura
                $numero_documento_cliente = $request->ruc;
                $nombre_cliente = $request->razon_social;
                $direccion_cliente = $request->direccion_fiscal;
                $tipo_documento_cliente = '6'; // RUC
                $email_cliente = $request->email_invoice_factura;
            }

            $sale = Sale::create([
                'date_sale' => Carbon::now(),
                'serie' => $this->generateRandomString(),
                'worker_id' => null,
                'caja' => null,
                'currency' => 'PEN',
                'op_exonerada' => $request->get('total_exonerada'),
                'op_inafecta' => 0,
                'op_gravada' => $request->get('total_gravada'),
                'igv' => $request->get('total_igv'),
                'total_descuentos' => $request->get('total_descuentos'),
                'importe_total' => $request->get('total_importe'),
                'vuelto' => $request->get('total_vuelto'),
                'tipo_pago_id' => $request->get('tipo_pago'),

                // Facturación
                'type_document' => $type_document,
                'numero_documento_cliente' => $numero_documento_cliente,
                'tipo_documento_cliente' => $tipo_documento_cliente,
                'nombre_cliente' => $nombre_cliente,
                'direccion_cliente' => $direccion_cliente,
                'email_cliente' => $email_cliente,

                // Los siguientes campos se llenarán más adelante cuando se genere el comprobante con Greenter
                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'fecha_emision' => null,
            ]);

            if ($workerId) {
                $sale->worker_id = $workerId;
                $sale->caja = $workerId;
                $sale->save();
            } else {
                // fallback: el trabajador actual logueado, por ejemplo
                $sale->worker_id = $workerDefault->id;
                $sale->caja = $workerDefault->id;
                $sale->save();
            }

            // ✅ Crear UNA sola salida (Output) para toda la venta
            $output = Output::create([
                'execution_order'  => "VENTA DE POS",
                'request_date'     => $sale->date_sale ?? Carbon::now(),
                'requesting_user'  => Auth::id(),
                'responsible_user' => Auth::id(),
                'state'            => 'confirmed',
                'indicator'        => 'or',
            ]);

            // ===========================
            // ✅ COSTOS PROMEDIO (KARDEX) HASTA FECHA DE VENTA
            // ===========================
            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            $materialIds = [];
            foreach ($items as $it) {
                $materialIds[] = (int) $it->productId;
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material (hasta la fecha de emisión)
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            for ($i = 0; $i < sizeof($items); $i++) {

                $presentationId = $items[$i]->presentationId ?? null;
                $packs = null;
                $unitsPerPack = null;

                // Si viene presentación, entonces productQuantity = packs
                if (!empty($presentationId)) {
                    $packs = (int) ($items[$i]->productQuantity ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Cantidad de paquetes inválida para el producto ID {$items[$i]->productId}.");
                    }

                    // Traer presentación real y usar su quantity (cantidad por pack)
                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $items[$i]->productId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("La presentación seleccionada no es válida o no pertenece al material ID {$items[$i]->productId}.");
                    }

                    $unitsPerPack = (int) $presentation->quantity; // 👈 fuente de verdad
                    if ($unitsPerPack < 1) {
                        throw new \Exception("La presentación tiene cantidad inválida.");
                    }

                    $unitsEquivalent = $packs * $unitsPerPack; // ✅ REGLA FINAL
                } else {
                    // venta unitaria (o decimal si aplica)
                    $unitsEquivalent = (float) ($items[$i]->productQuantity ?? 0);
                    if ($unitsEquivalent <= 0) {
                        throw new \Exception("Cantidad inválida para el producto ID {$items[$i]->productId}.");
                    }
                }

                // ==========================================
                // 1. Crear SaleDetail (NO CAMBIA)
                // ==========================================
                $presentationId = $items[$i]->presentationId ?? null;
                $materialId = (int) $items[$i]->productId;

                // cantidad real (stock)
                $qtyReal = (float) $unitsEquivalent;

                // cantidad SUNAT (packs si hay presentación, si no qtyReal)
                $qtyForSunat = (!empty($presentationId))
                    ? (float) ($items[$i]->productQuantity ?? 0)   // packs
                    : (float) $qtyReal;                             // unidades reales

                if ($qtyForSunat <= 0) {
                    throw new \Exception("Cantidad inválida (qtyForSunat) para producto ID {$materialId}.");
                }

                // total línea con IGV (según tu aclaración)
                $totalLine = (float) $items[$i]->productTotal;

                // precio_unitario con IGV (Nubefact: precio_unitario)
                $precioUnitario = $totalLine / $qtyForSunat;

                // valor_unitario sin IGV (Nubefact: valor_unitario)
                $valorUnitario  = $precioUnitario / 1.18;

                // costo promedio vigente (Kardex)
                $unitCost  = (float) ($avgCosts[$materialId] ?? 0.0);
                $totalCost = $qtyReal * $unitCost;

                $saleDetail = SaleDetail::create([
                    'sale_id'        => $sale->id,
                    'material_id'    => $items[$i]->productId,
                    'material_presentation_id' => $presentationId,
                    // ✅ requerido por NubefactTrait
                    'valor_unitario' => $valorUnitario,
                    'price'          => $precioUnitario,   // con IGV por qty SUNAT

                    // ✅ stock real
                    'quantity'       => $qtyReal,
                    'packs'          => $packs,
                    'units_per_pack' => $unitsPerPack,

                    'percentage_tax' => $items[$i]->productTax,
                    'total'          => $totalLine,        // ✅ con IGV (productTotal)
                    'discount'       => $items[$i]->productDiscount,

                    // ✅ costos para utilidad
                    'unit_cost'      => $unitCost,
                    'total_cost'     => $totalCost,
                ]);

                $material = Material::findOrFail($items[$i]->productId);
                $cantidadVendida = (float) $unitsEquivalent;

                // ==========================================
                // 2. CASO ITEMEABLE (tipo_venta_id == 3)
                // ==========================================
                if ((int) $material->tipo_venta_id === 3) {

                    if (floor($cantidadVendida) != $cantidadVendida) {
                        throw new \Exception(
                            "Cantidad decimal no soportada para material itemeable: {$material->full_name}"
                        );
                    }

                    $cantidadVendidaInt = (int) $cantidadVendida;

                    // Traer items disponibles
                    $itemsDisponibles = Item::where('material_id', $material->id)
                        ->whereIn('state_item', ['entered', 'scrapped'])
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->take($cantidadVendidaInt)
                        ->get();

                    if ($itemsDisponibles->count() < $cantidadVendidaInt) {
                        throw new \Exception(
                            "Stock insuficiente del material {$material->full_name}. " .
                            "Requiere {$cantidadVendidaInt} y hay {$itemsDisponibles->count()}."
                        );
                    }

                    // Crear OutputDetail por cada item
                    foreach ($itemsDisponibles as $item) {

                        OutputDetail::create([
                            'output_id'   => $output->id,
                            'sale_detail_id' => $saleDetail->id,  // ✅ NUEVO
                            'item_id'     => $item->id,
                            'material_id' => $material->id,
                            'quote_id'    => $sale->quote_id ?? null,
                            'custom'      => 0,
                            'percentage'  => 1, // 1 item = 1 unidad
                            'price'       => $precioUnitario,
                            'length'      => $item->length,
                            'width'       => $item->width,

                            // ✅ NUEVO: costo aplicado (auditoría)
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $unitCost,
                        ]);

                        // Marcar item como salido
                        $item->state_item = 'exited';
                        $item->save();
                    }

                    // Descontar stock directo del material
                    $material->stock_current = max(
                        0,
                        (float) $material->stock_current - $cantidadVendidaInt
                    );
                    $material->save();

                    $cantidadParaStore = $cantidadVendidaInt;

                }
                // ==========================================
                // 3. CASO NO ITEMEABLE
                // ==========================================
                else {

                    // Creamos UN SOLO OutputDetail con la cantidad en percentage
                    OutputDetail::create([
                        'output_id'   => $output->id,
                        'sale_detail_id' => $saleDetail->id, // ✅ NUEVO
                        'item_id'     => null,
                        'material_id' => $material->id,
                        'quote_id'    => $sale->quote_id ?? null,
                        'custom'      => 0,
                        'percentage'  => $cantidadVendida, // 👈 AQUÍ VA LA CANTIDAD
                        //'price'       => (float) $items[$i]->productPrice,
                        'price'       => $precioUnitario,
                        'length'      => null,
                        'width'       => null,

                        // ✅ NUEVO: costo aplicado
                        'unit_cost'      => $unitCost,
                        'total_cost'     => $totalCost,
                    ]);

                    // Descontar stock directo del material
                    $material->stock_current = max(
                        0,
                        (float) $material->stock_current - $cantidadVendida
                    );
                    $material->save();

                    $cantidadParaStore = $cantidadVendida;
                }

            }

            // Agregar movimientos a la caja
            $paymentType = $request->get('tipo_pago');
            $vuelto = $request->get('total_vuelto');
            $typeVuelto = $request->get('type_vuelto');

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
            if ($vuelto && $paymentType == 4) {
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

            // Crear notificacion
            $notification = Notification::create([
                'content' => 'Venta creada por '.Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'owner' , 'principal'])->get();
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
                'action' => 'Guardar venta',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => collect($e->getTrace())->take(8), // para no mandar 5000 líneas
            ], 422);
        }
        return response()->json(['message' => 'Venta guardada con éxito.',
            'sale_id' => $sale->id,
            'url_print' => route('puntoVenta.print', $sale->id)
        ], 200);

    }

    public function store(Request $request)
    {
        $begin = microtime(true);
        $workerDefault = Worker::where('user_id', Auth::user()->id)->first();
        $workerId = $request->input('worker_id');

        DB::beginTransaction();
        try {

            $items = json_decode($request->get('items'));

            // ======= (TU CÓDIGO SIN CAMBIOS) Validar stock =======
            foreach ($items as $item) {
                $materialId = (int) $item->productId;

                $material = Material::find($materialId);
                if (!$material) {
                    throw new \Exception("Material con ID {$materialId} no encontrado.");
                }

                $currentQuantityStore = (float) $material->stock_current;
                $presentationId = $item->presentationId ?? null;

                if (!empty($presentationId)) {
                    $packs = (int) ($item->productQuantity ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Cantidad de paquetes inválida para el producto '{$material->description}'.");
                    }

                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("La presentación seleccionada no es válida o no pertenece al material '{$material->description}'.");
                    }

                    $unitsPerPack = (int) $presentation->quantity;
                    if ($unitsPerPack < 1) {
                        throw new \Exception("La presentación tiene cantidad inválida para el material '{$material->description}'.");
                    }

                    $units = $packs * $unitsPerPack;
                } else {
                    $units = (float) ($item->productQuantity ?? 0);
                    if ($units <= 0) {
                        throw new \Exception("Cantidad inválida para el producto '{$material->description}'.");
                    }
                }

                if ($currentQuantityStore < $units) {
                    throw new \Exception(
                        "Stock insuficiente para el producto '{$material->description}'. " .
                        "Disponible: {$material->stock_current}, requerido: {$units}"
                    );
                }
            }

            // ======= (TU CÓDIGO SIN CAMBIOS) Datos de factura/boleta =======
            $type_document = null;
            $numero_documento_cliente = null;
            $nombre_cliente = null;
            $direccion_cliente = null;
            $tipo_documento_cliente = null;
            $email_cliente = null;

            if ($request->invoice_type === 'boleta') {
                $type_document = '03';
                $numero_documento_cliente = $request->dni;
                $nombre_cliente = $request->name;
                $direccion_cliente = $request->address;
                $tipo_documento_cliente = '1';
                $email_cliente = $request->email_invoice_boleta;
            } elseif ($request->invoice_type === 'factura') {
                $type_document = '01';
                $numero_documento_cliente = $request->ruc;
                $nombre_cliente = $request->razon_social;
                $direccion_cliente = $request->direccion_fiscal;
                $tipo_documento_cliente = '6';
                $email_cliente = $request->email_invoice_factura;
            }

            $sale = Sale::create([
                'date_sale' => Carbon::now(),
                'serie' => $this->generateRandomString(),
                'worker_id' => null,
                'caja' => null,
                'currency' => 'PEN',
                'op_exonerada' => $request->get('total_exonerada'),
                'op_inafecta' => 0,
                'op_gravada' => $request->get('total_gravada'),
                'igv' => $request->get('total_igv'),
                'total_descuentos' => $request->get('total_descuentos'),
                'importe_total' => $request->get('total_importe'),
                'vuelto' => $request->get('total_vuelto'),

                // ⚠️ Si aún tienes tipo_pago en tu front, puedes mantenerlo.
                // Si ya lo migraste, puedes dejar null o mapearlo a cash_box_id.
                'tipo_pago_id' => $request->get('tipo_pago'),

                'type_document' => $type_document,
                'numero_documento_cliente' => $numero_documento_cliente,
                'tipo_documento_cliente' => $tipo_documento_cliente,
                'nombre_cliente' => $nombre_cliente,
                'direccion_cliente' => $direccion_cliente,
                'email_cliente' => $email_cliente,

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

            if ($workerId) {
                $sale->worker_id = $workerId;
                $sale->caja = $workerId;
                $sale->save();
            } else {
                $sale->worker_id = $workerDefault->id;
                $sale->caja = $workerDefault->id;
                $sale->save();
            }

            // ✅ Output único
            $output = Output::create([
                'execution_order'  => "VENTA DE POS",
                'request_date'     => $sale->date_sale ?? Carbon::now(),
                'requesting_user'  => Auth::id(),
                'responsible_user' => Auth::id(),
                'state'            => 'confirmed',
                'indicator'        => 'or',
            ]);

            // ===========================
            // ✅ COSTOS PROMEDIO (KARDEX) HASTA FECHA DE VENTA
            // ===========================
            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            $materialIds = [];
            foreach ($items as $it) {
                $materialIds[] = (int) $it->productId;
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material (hasta la fecha de emisión)
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // ======= (TU CÓDIGO + ADICIONES) SaleDetails / OutputDetails / Stock =======
            for ($i = 0; $i < sizeof($items); $i++) {

                $presentationId = $items[$i]->presentationId ?? null;
                $packs = null;
                $unitsPerPack = null;

                if (!empty($presentationId)) {
                    $packs = (int) ($items[$i]->productQuantity ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Cantidad de paquetes inválida para el producto ID {$items[$i]->productId}.");
                    }

                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $items[$i]->productId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("La presentación seleccionada no es válida o no pertenece al material ID {$items[$i]->productId}.");
                    }

                    $unitsPerPack = (int) $presentation->quantity;
                    if ($unitsPerPack < 1) {
                        throw new \Exception("La presentación tiene cantidad inválida.");
                    }

                    $unitsEquivalent = $packs * $unitsPerPack; // ✅ unidades reales vendidas
                } else {
                    $unitsEquivalent = (float) ($items[$i]->productQuantity ?? 0);
                    if ($unitsEquivalent <= 0) {
                        throw new \Exception("Cantidad inválida para el producto ID {$items[$i]->productId}.");
                    }
                }

                // ===========================
                // ✅ AJUSTE PARA NUBEFACT (precio_unitario / valor_unitario)
                // ===========================
                $materialId = (int) $items[$i]->productId;
                $qtyReal = (float) $unitsEquivalent;

                $qtyForSunat = (!empty($presentationId))
                    ? (float) ($packs ?? 0)   // packs
                    : (float) $qtyReal;       // unidades

                if ($qtyForSunat <= 0) {
                    throw new \Exception("Cantidad inválida (qtyForSunat) para producto ID {$materialId}.");
                }

                $totalLine = (float) $items[$i]->productTotal; // ✅ total con IGV
                $precioUnitario = $totalLine / $qtyForSunat;   // ✅ con IGV por qty SUNAT
                $valorUnitario  = $precioUnitario / 1.18;      // ✅ sin IGV

                $unitCost  = (float) ($avgCosts[$materialId] ?? 0.0);
                $totalCost = $qtyReal * $unitCost;

                $saleDetail = SaleDetail::create([
                    'sale_id'        => $sale->id,
                    'material_id'    => $materialId,
                    'material_presentation_id' => $presentationId,
                    'valor_unitario' => $valorUnitario,
                    'price'          => $precioUnitario,
                    'quantity'       => $qtyReal,
                    'packs'          => $packs,
                    'units_per_pack' => $unitsPerPack,
                    'percentage_tax' => $items[$i]->productTax,
                    'total'          => $totalLine,
                    'discount'       => $items[$i]->productDiscount,
                    'unit_cost'      => $unitCost,
                    'total_cost'     => $totalCost,
                ]);

                $material = Material::findOrFail($materialId);
                $cantidadVendida = (float) $qtyReal;

                if ((int) $material->tipo_venta_id === 3) {

                    if (floor($cantidadVendida) != $cantidadVendida) {
                        throw new \Exception("Cantidad decimal no soportada para material itemeable: {$material->full_name}");
                    }

                    $cantidadVendidaInt = (int) $cantidadVendida;

                    $itemsDisponibles = Item::where('material_id', $material->id)
                        ->whereIn('state_item', ['entered', 'scrapped'])
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->take($cantidadVendidaInt)
                        ->get();

                    if ($itemsDisponibles->count() < $cantidadVendidaInt) {
                        throw new \Exception(
                            "Stock insuficiente del material {$material->full_name}. " .
                            "Requiere {$cantidadVendidaInt} y hay {$itemsDisponibles->count()}."
                        );
                    }

                    foreach ($itemsDisponibles as $itemObj) {

                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => $itemObj->id,
                            'material_id'    => $material->id,
                            'quote_id'       => $sale->quote_id ?? null,
                            'custom'         => 0,
                            'percentage'     => 1,
                            'price'          => $precioUnitario,
                            'length'         => $itemObj->length,
                            'width'          => $itemObj->width,
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $unitCost,
                        ]);

                        $itemObj->state_item = 'exited';
                        $itemObj->save();
                    }

                    //$material->unit_price = $unitCost;
                    $material->stock_current = max(0, (float) $material->stock_current - $cantidadVendidaInt);
                    $material->save();

                } else {

                    OutputDetail::create([
                        'output_id'      => $output->id,
                        'sale_detail_id' => $saleDetail->id,
                        'item_id'        => null,
                        'material_id'    => $material->id,
                        'quote_id'       => $sale->quote_id ?? null,
                        'custom'         => 0,
                        'percentage'     => $cantidadVendida,
                        'price'          => $precioUnitario,
                        'length'         => null,
                        'width'          => null,
                        'unit_cost'      => $unitCost,
                        'total_cost'     => $totalCost,
                    ]);

                    //$material->unit_price = $unitCost;
                    $material->stock_current = max(0, (float) $material->stock_current - $cantidadVendida);
                    $material->save();
                }
            }

            // ============================================================
            // ✅ NUEVO BLOQUE: CAJA / VUELTO con CashBox + Subtype + Sesión
            // ============================================================

            $cashBoxId   = $request->input('cash_box_id');
            $subtypeId   = $request->input('cash_box_subtype_id');
            $vuelto      = (float) $request->get('total_vuelto');
            $vueltoBoxId = $request->input('vuelto_cash_box_id');

            if (!$cashBoxId) {
                return response()->json(['message' => 'Debe seleccionar una caja (CashBox).'], 422);
            }

            $cashRegister = CashRegister::where('cash_box_id', $cashBoxId)
                ->where('user_id', Auth::id())
                ->where('status', 1)
                ->latest()
                ->first();

            if (!$cashRegister) {
                return response()->json(['message' => 'No hay sesión abierta para la caja seleccionada.'], 422);
            }

            $cashBox = $cashRegister->cashBox;
            if (!$cashBox) {
                return response()->json(['message' => 'La sesión seleccionada no tiene CashBox asociado.'], 422);
            }

            $regularize = 1;
            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                if (!$subtypeId) {
                    return response()->json(['message' => 'Debe seleccionar el subtipo bancario (Yape/Plin/POS/Transfer).'], 422);
                }

                $subtype = CashBoxSubtype::findOrFail($subtypeId);
                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                $subtypeId = null;
            }

            $amountSale = (float)$request->get('total_importe') + (float)$request->get('total_vuelto');

            CashMovement::create([
                'cash_register_id'      => $cashRegister->id,
                'type'                  => 'sale',
                'amount'                => $amountSale,
                'description'           => 'Venta registrada',
                'observation'           => ($cashBox->type === 'bank') ? 'Pago bancario' : 'Pago efectivo',
                'regularize'            => $regularize,
                'cash_box_subtype_id'   => $subtypeId,
                'sale_id'               => $sale->id,
            ]);

            if ($regularize == 1) {
                $cashRegister->current_balance += $amountSale;
                $cashRegister->total_sales     += $amountSale;
                $cashRegister->save();
            }

            if ($vuelto > 0) {

                $vueltoSubtypeId = $request->input('vuelto_cash_box_subtype_id');

                if (!$vueltoBoxId) {
                    return response()->json(['message' => 'Seleccione la caja desde donde se dará el vuelto.'], 422);
                }

                $vueltoRegister = CashRegister::where('cash_box_id', $vueltoBoxId)
                    ->where('user_id', Auth::id())
                    ->where('status', 1)
                    ->latest()
                    ->first();

                if (!$vueltoRegister) {
                    return response()->json(['message' => 'No hay sesión abierta para la caja del vuelto.'], 422);
                }

                $vueltoCashBox = $vueltoRegister->cashBox;
                if (!$vueltoCashBox) {
                    return response()->json(['message' => 'La sesión del vuelto no tiene CashBox asociado.'], 422);
                }

                // Si el vuelto sale de bancario con subtypes, exige subtipo
                $vueltoSubtypeToSave = null;
                if ($vueltoCashBox->type === 'bank' && (int)$vueltoCashBox->uses_subtypes === 1) {
                    if (!$vueltoSubtypeId) {
                        return response()->json(['message' => 'Seleccione el subtipo bancario para el vuelto (Yape/Plin/Transfer/POS).'], 422);
                    }
                    $vueltoSubtypeToSave = CashBoxSubtype::findOrFail($vueltoSubtypeId)->id;
                }

                // Crear egreso por vuelto (expense siempre confirmado)
                CashMovement::create([
                    'cash_register_id' => $vueltoRegister->id,
                    'type' => 'expense',
                    'amount' => $vuelto,
                    'description' => 'Vuelto entregado de la venta',
                    'observation' => 'Vuelto aplicado',
                    'regularize' => 1,
                    'cash_box_subtype_id' => $vueltoSubtypeToSave,
                    'sale_id' => $sale->id
                ]);

                // Actualizar caja del vuelto (impacta balance inmediato)
                $vueltoRegister->current_balance -= $vuelto;
                $vueltoRegister->total_expenses  += $vuelto;
                $vueltoRegister->save();
            }

            // ======= (TU CÓDIGO SIN CAMBIOS) Notificación / Audit =======
            $notification = Notification::create([
                'content' => 'Venta creada por '.Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'owner' , 'principal'])->get();
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
                'action' => 'Guardar venta',
                'time' => $end
            ]);

            DB::commit();

            // ===========================
            // ✅ NUBEFACT (FUERA DE TRANSACCIÓN)
            // ===========================
            $nubefactResult = null;

            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);

                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);
                    $this->persistNubefactFilesAndUpdateSale($sale, $nubefactResult);

                } catch (\Throwable $e) {
                    $sale->update([
                        'sunat_status'  => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            $urlPrint = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if (in_array($sale->type_document, ['01', '03'])) {

                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }

                if (!empty($sale->pdf_path)) {
                    if (!empty($sale->pdf_path)) {
                        $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                        if (file_exists($localPath)) {
                            $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                            $printType = 'sunat_pdf';
                        }
                    } elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }

            return response()->json([
                'message' => 'Venta guardada con éxito.' . ($nubefactResult ? ' Comprobante generado.' : ''),
                'sale_id' => $sale->id,
                'nubefact' => $nubefactResult,
                'url_print' => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => collect($e->getTrace())->take(8),
            ], 422);
        }
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
        $users = User::role(['admin', 'principal', 'owner'])->where('id', '!=', Auth::id())->get();

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

    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function printDocumentSale($id)
    {
        $sale = Sale::where('id', $id)
            ->with('worker')
            ->with(['details' => function ($query) {
                $query->with(['material']);
            }])
            ->firstOrFail();

        $paymentMovement = CashMovement::where('sale_id', $sale->id)
            ->where('type', 'sale')
            ->with(['cashRegister.cashBox', 'cashBoxSubtype'])
            ->orderBy('id', 'desc')
            ->first();

        $paymentLabel = '—';

        if ($paymentMovement && $paymentMovement->cashRegister && $paymentMovement->cashRegister->cashBox) {
            $box = $paymentMovement->cashRegister->cashBox;

            if ($box->type === 'cash') {
                // efectivo
                $paymentLabel = $box->name; // Ej: "Efectivo"
            } else {
                // bancario
                $sub = $paymentMovement->cashBoxSubtype;
                if ($sub) {
                    // Ej: "Bancario BCP - Yape"
                    //$paymentLabel = $box->name . ' - ' . $sub->name;
                    $paymentLabel = $sub->name;
                } else {
                    // Ej: "Bancario BCP"
                    $paymentLabel = $box->name;
                }
            }
        }

        $dataName = DataGeneral::where('name', 'empresa')->first();
        $dataRuc = DataGeneral::where('name', 'ruc')->first();
        $dataAddress = DataGeneral::where('name', 'address')->first();

        $nameEmpresa = $dataName->valueText;
        $ruc = $dataRuc->valueText;
        $address = $dataAddress->valueText;

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


        /*$view = view('exports.salePdf', compact('titleCuenta1Empresa',
            'nroCuenta1Empresa',
            'cciCuenta1Empresa',
            'imgCuenta1Empresa',
            'ownerCuenta1Empresa',
            'titleCuenta2Empresa',
            'nroCuenta2Empresa',
            'cciCuenta2Empresa',
            'imgCuenta2Empresa',
            'ownerCuenta2Empresa',
            'tieneCuentas','sale', 'nameEmpresa', 'ruc', 'address'));*/

        //$pdf = PDF::loadHTML($view);
        // Configurar el tamaño de la página a un tamaño personalizado para el ticket
        //$customPaper = array(0, 0, 226.77, 650); // Ancho y alto en puntos (1 pulgada = 72 puntos)
        //$customPaper = array(0, 0, 250, 650);
        //$pdf->setPaper($customPaper);
        /*$customPaper = array(0, 0, 226.8, 900); // Ancho fijo, altura suficientemente grande para el contenido
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOptions([
            'default_font_size' => 12,
            'default_font' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'default_margin' => [
                'top'    => 0,
                'right'  => 0,
                'bottom' => 0,
                'left'   => 0,
            ],
        ]);*/

        $pdf = Pdf::loadView('exports.salePdf2', compact('titleCuenta1Empresa',
            'nroCuenta1Empresa',
            'cciCuenta1Empresa',
            'imgCuenta1Empresa',
            'ownerCuenta1Empresa',
            'titleCuenta2Empresa',
            'nroCuenta2Empresa',
            'cciCuenta2Empresa',
            'imgCuenta2Empresa',
            'ownerCuenta2Empresa',
            'tieneCuentas','sale', 'nameEmpresa', 'ruc', 'address',
            'paymentLabel'   // ✅ nuevo
        ))->setPaper([0, 0, 226.8, 900], 'portrait');


        $length = 5;
        $codeOrder = ''.str_pad($id,$length,"0", STR_PAD_LEFT);
        $name = "comprobante_electronicoB". $codeOrder . '_'. $sale->serie . '.pdf';

        return $pdf->stream($name);

    }

    public function listar() {
        $registros = Sale::all();

        $arrayYears = $registros->pluck('created_at')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        return view('puntoVenta.list', compact('arrayYears'));
    }

    public function getSalesAdmin(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $code = $request->input('code');
        $year = $request->input('year');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $query = Sale::where('state_annulled', 0)->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Sale::where('state_annulled', 0)->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($code != "") {
            $query->where('id', 'LIKE', '%'.$code.'%');

        }

        if ($year != "") {
            $query->whereYear('created_at', $year);

        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $sales = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $arraySales = [];

        foreach ( $sales as $sale )
        {
            $tipo_comprobante = null;
            if ($sale->type_document == '01')
            {
                $tipo_comprobante = 'factura';
            } elseif ( $sale->type_document == '03' ) {
                $tipo_comprobante = 'boleta';
            }

            $tipo_documento_cliente = null;
            if ($sale->tipo_documento_cliente == 1)
            {
                $tipo_documento_cliente = 'dni';
            } elseif ( $sale->tipo_documento_cliente == 6 ) {
                $tipo_documento_cliente = 'ruc';
            }

            $printUrl = route('puntoVenta.print', $sale->id); // ticket por defecto

            if (!empty($sale->pdf_path)) {
                // PDF local guardado
                $printUrl = asset('comprobantes/pdfs/' . $sale->pdf_path);
            }

            // ===============================
// NUEVO: obtener canal de pago desde CashMovement
// ===============================
            $paymentMovement = \App\CashMovement::where('sale_id', $sale->id)
                ->where('type', 'sale')
                ->with(['cashRegister.cashBox', 'cashBoxSubtype'])
                ->orderBy('id', 'desc')
                ->first();

            $tipoPagoLabel = '—';

            if ($paymentMovement && $paymentMovement->cashRegister && $paymentMovement->cashRegister->cashBox) {
                $cashBox = $paymentMovement->cashRegister->cashBox;

                if ($cashBox->type === 'cash') {
                    // Ej: "Efectivo"
                    $tipoPagoLabel = $cashBox->name;
                } else {
                    // Bancario
                    if ($paymentMovement->cashBoxSubtype) {
                        // Ej: "BCP - Yape"
                        $tipoPagoLabel = $cashBox->name . ' - ' . $paymentMovement->cashBoxSubtype->name;
                    } else {
                        // Ej: "BCP"
                        $tipoPagoLabel = $cashBox->name;
                    }
                }
            }

            array_push($arraySales, [
                "id" => $sale->id,
                "code" => "VENTA - ".$sale->id,
                "date" => ($sale->date_sale != null) ? $sale->formatted_sale_date : "",
                "currency" => ($sale->currency == 'PEN') ? 'Soles' : 'Dólares',
                //"total" => $sale->importe_total,
                "total" => number_format($sale->importe_total, 2, '.', ''),
                "tipo_pago" => strtoupper($tipoPagoLabel),
                "nombre_cliente" => $sale->nombre_cliente,
                "tipo_documento_cliente" => $tipo_documento_cliente,
                "numero_documento_cliente" => $sale->numero_documento_cliente,
                "direccion_cliente" => $sale->direccion_cliente,
                "email_cliente" => $sale->email_cliente,
                "tipo_comprobante" => $tipo_comprobante,
                "print_url" => $printUrl,
                "print_label" => !empty($sale->pdf_path) ? 'Ver PDF' : 'Ver Ticket',
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

        return ['data' => $arraySales, 'pagination' => $pagination];
    }

    public function getOrderDetails($orderId)
    {

        $sale = Sale::with([
            'details.material'
        ])->find($orderId);

        if (!$sale) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $details = $sale->details->map(function ($detail) {
            return [
                'code' => $detail->material->code,
                'producto' => $detail->material->full_name,
                'quantity' => $detail->quantity,
                'price' => $detail->price,
                'total' => number_format($detail->total, 2),
            ];
        });

        return response()->json(['details' => $details], 200);
    }

    public function anularOrder($id)
    {
        DB::beginTransaction();
        try {

            $sale = Sale::with('details')->find($id);

            if (!$sale) {
                return response()->json(['message' => 'Orden no encontrada'], 422);
            }

            if ($sale->state_annulled == 1) {
                return response()->json(['message' => 'La orden ya ha sido anulada previamente'], 422);
            }

            // Revertir el stock de cada detalle
            foreach ($sale->details as $detail) {
                $material = Material::find($detail->material_id);
                if ($material) {
                    $material->stock_current += $detail->quantity;
                    $material->save();
                }

                // TODO: Eliminamos el detalle de salida para no afectar kardex
                //$detail->delete();
                $outputDetail = OutputDetail::where('sale_detail_id', $detail->id)->first();
                if (isset($outputDetail))
                {
                    $outputDetail->delete();
                }
            }

            $sale->state_annulled = 1;
            $sale->save();

            // Cambios en los movimientos
            // Revertir los movimientos de caja asociados a la orden
            $movements = CashMovement::where('sale_id', $sale->id)->get();


            //$tipoPago = $sale->tipoPago->description;
            foreach ($movements as $movement) {

                //$cashBoxSubType = CashBoxSubtype::find($movement->cash_box_subtype_id);
                $cashBoxSubType = $movement->cash_box_subtype_id
                    ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                    : null;
                //$is_deferred = $cashBoxSubType->is_deferred;
                // si no hay subtype (efectivo) => NO diferido
                $is_deferred = $cashBoxSubType ? (int) $cashBoxSubType->is_deferred : 0;

                // para re-usarlo al crear movimientos (si no hay subtype => null)
                $cash_box_subtype_id_to_use = $cashBoxSubType ? $cashBoxSubType->id : null;

                // Si es un movimiento de tipo "sale"
                if ($movement->type === 'sale') {
                    // Caso de pago POS (no pago directo)
                    if ($is_deferred == 1) {
                        if ($movement->regularize == 0) {
                            // No se regularizó: se elimina el movimiento
                            $movement->delete();
                        } elseif ($movement->regularize == 1) {
                            // Si se regularizó, se crea un movimiento inverso de tipo "expense"
                            CashMovement::create([
                                'cash_register_id'      => $movement->cash_register_id,
                                'sale_id'               => $sale->id,
                                'type'                  => 'expense',
                                'amount'                => $movement->amount_regularize,
                                'description'           => 'Reversión de venta (POS regularizado) por anulación de venta',
                                'regularize'            => $movement->regularize,
                                //'cash_box_subtype_id'   => $cashBoxSubType->id,
                                'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                            ]);
                            $cashRegister = CashRegister::find($movement->cash_register_id);
                            $cashRegister->current_balance -= $movement->amount_regularize;
                            $cashRegister->total_sales    -= $movement->amount_regularize;
                            $cashRegister->total_expenses += $movement->amount_regularize;
                            $cashRegister->save();
                        }
                    } else {
                        // Para ventas normales, se revierte creando un movimiento de tipo "expense"
                        CashMovement::create([
                            'cash_register_id'      => $movement->cash_register_id,
                            'sale_id'               => $sale->id,
                            'type'                  => 'expense',
                            'amount'                => $movement->amount,
                            'description'           => 'Reversión de venta por anulación de venta',
                            'regularize'            => $movement->regularize,
                            //'cash_box_subtype_id'   => $cashBoxSubType->id,
                            'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                        ]);
                        $cashRegister = CashRegister::find($movement->cash_register_id);
                        $cashRegister->current_balance -= $movement->amount;
                        $cashRegister->total_sales    -= $movement->amount;
                        $cashRegister->total_expenses += $movement->amount;
                        $cashRegister->save();
                    }
                }
                // Si es un movimiento de tipo "expense" (por ejemplo, el vuelto)
                elseif ($movement->type === 'expense') {
                    // Se revierte creando un movimiento de tipo "income"
                    CashMovement::create([
                        'cash_register_id'      => $movement->cash_register_id,
                        'sale_id'               => $sale->id,
                        'type'                  => 'income',
                        'amount'                => $movement->amount,
                        'description'           => 'Reversión de gasto (vuelto) por anulación de orden',
                        'subtype'               => $movement->subtype,
                        'regularize'            => $movement->regularize,
                        //'cash_box_subtype_id'   => $cashBoxSubType->id,
                        'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                    ]);
                    $cashRegister = CashRegister::find($movement->cash_register_id);
                    $cashRegister->current_balance += $movement->amount;
                    $cashRegister->total_incomes  += $movement->amount;
                    $cashRegister->total_expenses -= $movement->amount;
                    $cashRegister->save();
                }
            }

            // TODO: Revertir los reservations
            $reservations = QuoteMaterialReservation::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                // Borramos la reserva de esta cotización
                $reservation->delete();
            }

            DB::commit();

            return response()->json(['message' => 'Orden anulada con éxito'], 200);

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

    }

    public function updateInvoiceData(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:sales,id',
            'type_document' => 'required|in:boleta,factura',
        ]);

        $order = Sale::findOrFail($request->order_id);

        if ($request->type_document === 'boleta') {
            $order->type_document = '03';
            $order->numero_documento_cliente = $request->dni;
            $order->nombre_cliente = $request->name;
            $order->email_cliente = $request->email;
        } elseif ($request->type_document === 'factura') {
            $order->type_document = '01';
            $order->numero_documento_cliente = $request->ruc;
            $order->nombre_cliente = $request->razon_social;
            $order->direccion_cliente = $request->direccion_fiscal;
            $order->email_cliente = $request->email;
        }

        $order->save();

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
