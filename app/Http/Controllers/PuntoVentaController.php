<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Category;
use App\CreditNote;
use App\CreditNoteDetail;
use App\CreditNoteDetailItem;
use App\Customer;
use App\DataGeneral;
use App\Http\Controllers\Traits\FreeSaleFinancialReversalTrait;
use App\Http\Controllers\Traits\NubefactTrait;
use App\InventoryLevel;
use App\Item;
use App\Location;
use App\Mail\StockLowNotificationMail;
use App\MaterialPresentation;
use App\Output;
use App\OutputDetail;
use App\PorcentageQuote;
use App\PriceList;
use App\Quote;
use App\QuoteMaterialReservation;
use App\QuoteStockLot;
use App\SalePartialPayment;
use App\Services\InventoryCostService;
use App\Services\Sales\PosCatalogReadService;
use App\Services\SettingService;
use App\StockItem;
use App\StockLot;
use App\Support\TenantContext;
use App\Warehouse;
use Illuminate\Support\Facades\Log;
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
use Illuminate\Support\Str;
use App\CompanyBankAccount;

class PuntoVentaController extends Controller
{
    use NubefactTrait;
    use FreeSaleFinancialReversalTrait;

    /** @var InventoryCostService */
    private $inventoryCostService;

    public function __construct(InventoryCostService $inventoryCostService)
    {
        $this->inventoryCostService = $inventoryCostService;
    }

    public function index()
    {
        return view('puntoVenta.index', app(\App\Services\Sales\PosScreenOptions::class)->data('v1'));
    }

    public function indexV2()
    {
        return view('puntoVenta.indexV2', app(\App\Services\Sales\PosScreenOptions::class)->data('v2'));
    }

    public function getDataProducts(Request $request, $pageNumber = 1)
    {
        return response()->json(app(PosCatalogReadService::class)->v1($request, $pageNumber));
    }


    public function getStockItemsByMaterial($materialId)
    {
        return response()->json(app(PosCatalogReadService::class)->variants(request(), $materialId));
    }


    public function getDataProductsV2(Request $request, $pageNumber = 1)
    {
        return response()->json(app(PosCatalogReadService::class)->v2($request, $pageNumber));
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
                'dispatch_status' => 'despachado',

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

    public function storeO2(Request $request)
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
                'dispatch_status' => 'despachado',

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
                            $urlPrint  = route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf']);
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

    public function store(Request $request)
    {
        try {
            $record = app(\App\Services\Sales\PosSaleService::class)->store($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message'=>'Revise los datos de la venta.','errors'=>$e->errors()],422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message'=>'No se registró la venta. Revise la disponibilidad y la sesión de caja antes de intentar nuevamente.'],422);
        }
        // All business data is committed. Never roll back or resend on a later failure.
        $sale=$record['sale'];$warnings=[];$result=null;$outcome=null;
        $urlPrint=null;$printType='ticket';
        if(!$record['replayed']){
            try { app(\App\Services\Sales\PosSaleNotice::class)->send($sale); }
            catch(\Throwable $e){$warnings[]='La venta está registrada, pero no se completaron sus notificaciones internas.';}
        }
        try {
            $urlPrint=route('puntoVenta.print',$sale->id);
            if(!$record['replayed']&&in_array($sale->type_document,['01','03'],true)){
                $outcome=app(\App\Services\SaleEmissionService::class)->emit($sale,
                    function(\App\Sale $locked,\App\Services\NubefactCompanyConnection $connection){
                        return app(\App\Services\Sales\PosEmissionPayload::class)->build($locked,$connection);
                    });
                $result=$outcome['result'];$warnings=array_merge($warnings,$outcome['warnings']);
                if($outcome['emission_status']==='confirmed'&&is_array($result)){
                    try {
                        $files=$this->persistNubefactFilesAndUpdateSale($sale,$result);
                        $warnings=array_merge($warnings,$files['errores']??[]);
                    } catch(\Throwable $e) {
                        $warnings[]='El comprobante está confirmado, pero falta recuperar sus archivos.';
                    }
                    if(!empty($sale->pdf_path)){$urlPrint=route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf']);$printType='sunat_pdf';}
                    elseif(!empty($result['enlace_del_pdf'])){$urlPrint=$result['enlace_del_pdf'];$printType='sunat_pdf';}
                }
            }
            if($sale->emission_status==='confirmed'&&!empty($sale->pdf_path)){
                $urlPrint=route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf']);$printType='sunat_pdf';
            }
            if($record['replayed'])$warnings[]='La operación ya estaba registrada. No se repitió el cobro, el consumo ni el envío al proveedor.';
        } catch(\Throwable $e) {
            $warnings[]='La venta está registrada; revise el estado de emisión antes de repetir cualquier envío.';
        }
        return response()->json(['message'=>$warnings?'Venta registrada con advertencias.':'Venta guardada con éxito.',
            'sale_id'=>$sale->id,'sale_created'=>true,'warnings'=>array_values(array_unique($warnings)),
            'nubefact'=>$result,'url_print'=>$urlPrint,'print_type'=>$printType,
            'emission_status'=>$outcome['emission_status']??$sale->emission_status,'replayed'=>$record['replayed']],200);
    }

    protected function getAvailableStockByStockItem(int $stockItemId): float
    {
        $lots = StockLot::where('stock_item_id', $stockItemId)->get();

        return (float) $lots->sum(function ($lot) {
            return (float) $lot->qty_on_hand - (float) $lot->qty_reserved;
        });
    }

    protected function syncInventoryLevelFromLots(
        int $stockItemId,
        int $warehouseId,
        int $locationId
    ): void
    {
        $companyId =
            TenantContext::companyId();

        $warehouse =
            Warehouse::query()
                ->where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->first();

        if (!$warehouse) {
            throw new \RuntimeException(
                'El almacén no pertenece a la empresa actual.'
            );
        }

        $location =
            Location::query()
                ->where('id', $locationId)
                ->where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->first();

        if (!$location) {
            throw new \RuntimeException(
                'La ubicación no pertenece al almacén y empresa actuales.'
            );
        }

        $qtyOnHand =
            (float) StockLot::query()
                ->where('company_id', $companyId)
                ->where('stock_item_id', $stockItemId)
                ->where('warehouse_id', $warehouseId)
                ->where('location_id', $locationId)
                ->sum('qty_on_hand');

        $qtyReserved =
            (float) StockLot::query()
                ->where('company_id', $companyId)
                ->where('stock_item_id', $stockItemId)
                ->where('warehouse_id', $warehouseId)
                ->where('location_id', $locationId)
                ->sum('qty_reserved');

        $inventoryLevel =
            InventoryLevel::query()
                ->where('company_id', $companyId)
                ->where('stock_item_id', $stockItemId)
                ->where('warehouse_id', $warehouseId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

        if (!$inventoryLevel) {
            $inventoryLevel =
                InventoryLevel::create([
                    'company_id' =>
                        $companyId,

                    'stock_item_id' =>
                        $stockItemId,

                    'warehouse_id' =>
                        $warehouseId,

                    'location_id' =>
                        $locationId,

                    'qty_on_hand' =>
                        0,

                    'qty_reserved' =>
                        0,

                    'min_alert' =>
                        0,

                    'max_alert' =>
                        0,

                    'average_cost' =>
                        0,

                    'last_cost' =>
                        0,
                ]);
        }

        $inventoryLevel->qty_on_hand =
            $qtyOnHand;

        $inventoryLevel->qty_reserved =
            $qtyReserved;

        $inventoryLevel->save();
    }

    public function manageNotifications(Material $material)
    {
        $settings = app(SettingService::class);

        $notifyPopup = $settings->get(
            'inventory.stock_notifications.popup'
        );

        $notifyBell = $settings->get(
            'inventory.stock_notifications.bell'
        );

        $notifyEmail = $settings->get(
            'inventory.stock_notifications.email'
        );

        $notifyTelegram = $settings->get(
            'inventory.stock_notifications.telegram'
        );

        // Texto base
        $content = 'El producto '.$material->full_name.' está por agotarse.';

        $nameMaterial = $material->full_name;

        // Obtener usuarios con roles específicos (excepto el actual)
        $users = User::role(['admin', 'principal', 'owner'])->where('id', '!=', Auth::id())->get();

        if ($notifyBell)
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

        if ($notifyPopup)
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

        if ($notifyEmail)
        {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new StockLowNotificationMail($nameMaterial));
            }
        }

        // Si deseas dejar el código preparado para Telegram:
        if ($notifyTelegram)
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
                $query->with(['material', 'stockItem']);
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

        $company = TenantContext::company();

        $nameEmpresa = $company->business_name;
        $ruc = $company->ruc;
        $address = $company->address;

        $companyBankAccounts =
            CompanyBankAccount::query()
                ->with('bank')
                ->where(
                    'company_id',
                    TenantContext::companyId()
                )
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('position')
                ->orderBy('id')
                ->get();

        $settings = app(SettingService::class);

        $logotipoEmpresa = $settings->get('company.branding.logo');

        $logotipoBNEmpresa = $settings->get('company.branding.logo_monochrome');

        $tieneCuentas = $companyBankAccounts->isNotEmpty();

        $pdf = Pdf::loadView(
            'exports.salePdf2',
            compact(
                'companyBankAccounts',
                'tieneCuentas',
                'sale',
                'nameEmpresa',
                'ruc',
                'address',
                'logotipoEmpresa',
                'logotipoBNEmpresa',
                'paymentLabel'
            )
        )->setPaper(
            [0, 0, 226.8, 900],
            'portrait'
        );

        $length = 5;
        $codeOrder = ''.str_pad($id,$length,"0", STR_PAD_LEFT);
        $name = "comprobante_electronicoB". $codeOrder . '_'. $sale->serie . '.pdf';

        return $pdf->stream($name);

    }

    public function listar()
    {
        return view('puntoVenta.list', app(\App\Services\Sales\SaleListOptions::class)->data(false));
    }

    private function cleanUtf8($value)
    {
        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            return $value;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        return $value;
    }

    public function getSalesAdmin(Request $request, $pageNumber = 1)
    {
        $saleList = app(\App\Services\Sales\SaleListAccess::class);
        $saleList->context(false);
        $saleList->validateFilters($request);
        abort_unless(ctype_digit((string) $pageNumber) && (int) $pageNumber > 0, 422);
        $perPage = 10;
        $code = $request->input('code');
        $year = $request->input('year');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $customerId = $request->input('customer_id');
        $paymentStatus = $request->input('payment_status');
        $dispatchStatus = $request->input('dispatch_status');
        $cashBoxId = $request->input('cash_box_id');
        $cashBoxSubtypeId = $request->input('cash_box_subtype_id');
        $invoiceStatus = $request->input('invoice_status');

        $saleStatus = $request->get('sale_status', 'active');

        if ( $startDate == "" || $endDate == "" )
        {
            $query = $saleList->visible(Sale::query(), false)->with(['quote.customer', 'worker.user', 'customer'])
                /*->where('state_annulled', 0)*/
                ->orderBy('created_at', 'DESC');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = $saleList->visible(Sale::query(), false)->with(['quote.customer', 'worker.user', 'customer'])
                /*->where('state_annulled', 0)*/
                ->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal)
                ->orderBy('created_at', 'DESC');
        }

        if ($saleStatus === 'annulled') {
            $query->where('state_annulled', 1);
        } else {
            $query->where('state_annulled', 0);
        }

        if ($request->filled('created_by_id')) { $query->where('sales.created_by', (int) $request->input('created_by_id')); }

        // Aplicar filtros si se proporcionan
        if ($code != "") {
            $query->where('id', 'LIKE', '%'.$code.'%');

        }

        if ($year != "") {
            $query->whereYear('created_at', $year);

        }

        // Cliente
        if ($customerId != "") {
            if ($customerId === 'venta_directa') {
                $query->whereNull('quote_id');
            } elseif ($customerId === 'cotizacion_sin_cliente') {
                $query->whereHas('quote', function ($q) {
                    $q->whereNull('customer_id');
                });
            } else {
                $query->where(function ($customerQuery) use ($customerId) {
                    $customerQuery->where('sales.customer_id', $customerId)->orWhereHas('quote', function ($q) use ($customerId) { $q->where('customer_id', $customerId); });
                });
            }
        }

        // Estado despacho
        if ($dispatchStatus != "") {
            $query->where('dispatch_status', $dispatchStatus);
        }

        // Comprobante
        if ($invoiceStatus != "") {
            if ($invoiceStatus === 'con_comprobante') {
                $query->whereIn('type_document', ['01', '03'])
                    ->where(function ($q) {
                        $q->whereNull('sunat_status')
                            ->orWhere('sunat_status', '!=', 'Error');
                    });
            }

            if ($invoiceStatus === 'sin_comprobante') {
                $query->where(function ($q) {
                    $q->whereNull('type_document')
                        ->orWhereNotIn('type_document', ['01', '03'])
                        ->orWhere('sunat_status', 'Error');
                });
            }
        }

        // Método de pago
        if ($cashBoxId != "") {
            if ($cashBoxId === 'pago_parcial') {
                $query->where('pagos_parciales_venta', 's');
            } else {
                $query->where('pagos_parciales_venta', 'n')
                    ->whereHas('cashMovements', function ($q) use ($cashBoxId, $cashBoxSubtypeId) {
                        $q->where('type', 'sale')
                            ->whereHas('cashRegister', function ($qr) use ($cashBoxId) {
                                $qr->where('cash_box_id', $cashBoxId);
                            });

                        if ($cashBoxSubtypeId != "") {
                            $q->where('cash_box_subtype_id', $cashBoxSubtypeId);
                        }
                    });
            }
        }

        // The same effective balance drives filters and the displayed payment state.
        if (in_array($paymentStatus, ['verde', 'naranja', 'rojo'], true)) {
            $net = \App\Services\Sales\PartialCreditSettlement::netSql();
            $retained = \App\Services\Sales\PartialCreditSettlement::retainedSql();
            if ($paymentStatus === 'verde') {
                $query->where(function ($q) use ($net, $retained) {
                    $q->where('pagos_parciales_venta', 'n')->orWhereRaw("($retained) >= ($net)");
                });
            } else {
                $query->where('pagos_parciales_venta', 's')->whereRaw("($net) > 0");
                if ($paymentStatus === 'naranja') {
                    $query->whereRaw("($retained) >= (($net) * 0.5)")->whereRaw("($retained) < ($net)");
                } else { $query->whereRaw("($retained) < (($net) * 0.5)"); }
            }
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = $totalFilteredRecords > 0 ? ($pageNumber - 1) * $perPage + 1 : 0;
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

            /*$printUrl = route('puntoVenta.print', $sale->id); // ticket por defecto

            if (!empty($sale->pdf_path)) {
                // PDF local guardado
                $printUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf']);
            }*/
            $esComprobanteElectronico = in_array(
                $sale->type_document,
                ['01', '03']
            );

            $pdfLocalValido = false;
            $pdfLocalPath = null;

            if (!empty($sale->pdf_path)) {
                $pdfLocalPath = public_path(
                    'comprobantes/pdfs/' . $sale->pdf_path
                );

                if (
                    is_file($pdfLocalPath) &&
                    filesize($pdfLocalPath) >= 5
                ) {
                    $cabeceraPdf = file_get_contents(
                        $pdfLocalPath,
                        false,
                        null,
                        0,
                        5
                    );

                    $pdfLocalValido = $cabeceraPdf === '%PDF-';
                }
            }

            /*
             * Para una venta normal usamos el ticket local.
             *
             * Para boleta o factura:
             * - si existe PDF válido, usamos el PDF;
             * - si no existe, no enviamos una URL falsa al PDF.
             */
            if ($esComprobanteElectronico) {
                $printUrl = $pdfLocalValido
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf'])
                    : null;
            } else {
                $printUrl = route('puntoVenta.print', $sale->id);
            }

            /*
             * Se puede recuperar cuando:
             * - es boleta o factura;
             * - la emisión no está marcada como error;
             * - no hay un PDF local válido;
             * - la venta no está anulada.
             */
            $puedeRecuperarArchivosNubefact =
                $esComprobanteElectronico &&
                $sale->sunat_status !== 'Error' &&
                !$pdfLocalValido &&
                (int) $sale->state_annulled !== 1;

            // ===============================
            // NUEVO: obtener canal de pago desde CashMovement
            // ===============================
            $paymentMovement = CashMovement::where('sale_id', $sale->id)
                ->where('type', 'sale')
                ->with(['cashRegister.cashBox', 'cashBoxSubtype'])
                ->orderBy('id', 'desc')
                ->first();

            $tipoPagoLabel = $sale->pagos_parciales_venta === 's'
                ? 'PAGO PARCIAL'
                : 'SIN MOVIMIENTO';

            if ($sale->pagos_parciales_venta === 'n' && $paymentMovement && $paymentMovement->cashRegister && $paymentMovement->cashRegister->cashBox) {
                $cashBox = $paymentMovement->cashRegister->cashBox;

                if ($cashBox->type === 'cash') {
                    $tipoPagoLabel = $cashBox->name;
                } else {
                    if ($paymentMovement->cashBoxSubtype) {
                        $tipoPagoLabel = $cashBox->name . ' - ' . $paymentMovement->cashBoxSubtype->name;
                    } else {
                        $tipoPagoLabel = $cashBox->name;
                    }
                }
            }

            $nombreCliente = 'SIN CLIENTE';

            /*if ($sale->pagos_parciales_venta === 's') {

                if ($sale->quote) {
                    if ($sale->quote->customer) {
                        $nombreCliente = $sale->quote->customer->business_name;
                    } else {
                        $nombreCliente = 'COTIZACION SIN CLIENTE';
                    }
                } else {
                    $nombreCliente = 'VENTA DIRECTA';
                }
            } else {
                if ($sale->quote) {
                    if ($sale->quote->customer) {
                        $nombreCliente = $sale->quote->customer->business_name;
                    } else {
                        $nombreCliente = 'COTIZACION SIN CLIENTE';
                    }
                } else {
                    $nombreCliente = 'VENTA DIRECTA';
                }
            }*/
            if ((bool) $sale->free_sale) {
                /*
                 * Venta libre:
                 * 1. Si tiene customer_id, toma el nombre actual del Customer.
                 * 2. Si no tiene customer_id, toma el snapshot guardado en Sale.
                 * 3. Si no existe ninguno, muestra SIN CLIENTE.
                 */
                if ($sale->customer) {
                    $nombreCliente = $sale->customer->business_name;
                } elseif (!empty(trim((string) $sale->nombre_cliente))) {
                    $nombreCliente = $sale->nombre_cliente;
                }
            } else {
                /*
                 * Ventas normales existentes.
                 */
                if ($sale->quote) {
                    if ($sale->quote->customer) {
                        $nombreCliente = $sale->quote->customer->business_name;
                    } else {
                        $nombreCliente = 'COTIZACION SIN CLIENTE';
                    }
                } else {
                    $nombreCliente = 'VENTA DIRECTA';
                }
            }

            $balance = app(\App\Services\Sales\PartialCreditSettlement::class)->snapshot($sale);
            $totalVenta = $balance['net_cents'] / 100;
            $totalAbonado = null;
            $estadoPagoParcial = null;
            if ($sale->pagos_parciales_venta === 's') {
                $totalAbonado = $balance['retained_cents'] / 100;

                $porcentaje = $totalVenta > 0
                    ? (($totalAbonado / $totalVenta) * 100)
                    : 100;

                if ($porcentaje >= 100) {
                    $estadoPagoParcial = 'verde';
                } elseif ($porcentaje >= 50) {
                    $estadoPagoParcial = 'naranja';
                } else {
                    $estadoPagoParcial = 'rojo';
                }

                $totalTexto = number_format($totalAbonado, 2, '.', '') . ' / ' . number_format($totalVenta, 2, '.', '');
            } else {
                $totalTexto = number_format($totalVenta, 2, '.', '');
            }

            if ($balance['credited_cents'] > 0 && !empty($balance['reconciliation_required'])) { $totalTexto = 'Requiere conciliación de devolución histórica'; }

            $pendingCreditNote = app(\App\Services\Sales\PartialCreditSettlement::class)->pending($sale)
                ->latest()
                ->first();

            $rejectedCreditNote = $sale->creditNotes()
                ->where('status', 'rejected')
                ->latest()
                ->first();

            $estadoComprobante = 'SIN COMPROBANTE';

            if ($sale->type_document === '01') {
                $estadoComprobante = 'FACTURA';
            } elseif ($sale->type_document === '03') {
                $estadoComprobante = 'BOLETA';
            }

            if ($sale->sunat_status === 'Error') {
                $estadoComprobante .= ' - ERROR';

            } elseif ($sale->annulment_status === 'waiting_sunat_process') {
                $estadoComprobante .= ' - ESPERANDO PROCESO SUNAT';

            } elseif ($sale->annulment_status === 'pending') {
                $estadoComprobante .= ' - PENDIENTE ANULACIÓN';

            } elseif (!is_null($pendingCreditNote)) {
                $estadoComprobante .= ' - NC PENDIENTE';

            } elseif (!is_null($rejectedCreditNote)) {
                $estadoComprobante .= ' - NC RECHAZADA';

            } elseif ($sale->annulment_status === 'requires_credit_note') {
                $estadoComprobante .= ' - REQUIERE NOTA DE CRÉDITO';

            } elseif ($sale->annulment_status === 'accepted') {
                $estadoComprobante .= ' - ANULADO';

            } elseif ($sale->annulment_status === 'rejected') {
                $estadoComprobante .= ' - ANULACIÓN RECHAZADA';

            } elseif (!empty($sale->sunat_status)) {
                $estadoComprobante .= ' - ' . mb_strtoupper($sale->sunat_status, 'UTF-8');
            }

            $estadoAnulacion = 'NO ANULADA';

            switch ($sale->annulment_status) {
                case 'waiting_sunat_process':
                    $estadoAnulacion = 'ESPERAR PROCESO SUNAT';
                    break;

                case 'pending':
                    $estadoAnulacion = 'ANULACIÓN PENDIENTE';
                    break;

                case 'accepted':
                    $estadoAnulacion = 'ANULADA';
                    break;

                case 'rejected':
                    $estadoAnulacion = 'ANULACIÓN RECHAZADA';
                    break;

                case 'requires_credit_note':
                    $estadoAnulacion = 'REQUIERE NOTA DE CRÉDITO';
                    break;
            }

            $acceptedCreditNote = $sale->creditNotes()
                ->where('status', 'accepted')
                ->latest()
                ->first();

            $acceptedCreditNotePdfUrl = null;
            $acceptedCreditNoteXmlUrl = null;
            $acceptedCreditNoteCdrUrl = null;

            if (
                $acceptedCreditNote &&
                $acceptedCreditNote->status === 'accepted'
            ) {
                if (!empty($acceptedCreditNote->pdf_path)) {
                    $pdfPath = public_path(
                        'comprobantes/notas_credito/pdfs/' .
                        $acceptedCreditNote->pdf_path
                    );

                    if (is_file($pdfPath)) {
                        $acceptedCreditNotePdfUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_pdf', 'note_id' => $acceptedCreditNote->id]);
                    }
                }

                if (!empty($acceptedCreditNote->xml_path)) {
                    $xmlPath = public_path(
                        'comprobantes/notas_credito/xmls/' .
                        $acceptedCreditNote->xml_path
                    );

                    if (is_file($xmlPath)) {
                        $acceptedCreditNoteXmlUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_xml', 'note_id' => $acceptedCreditNote->id]);
                    }
                }

                if (!empty($acceptedCreditNote->cdr_path)) {
                    $cdrPath = public_path(
                        'comprobantes/notas_credito/cdrs/' .
                        $acceptedCreditNote->cdr_path
                    );

                    if (is_file($cdrPath)) {
                        $acceptedCreditNoteCdrUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_cdr', 'note_id' => $acceptedCreditNote->id]);
                    }
                }
            }

            $acceptedPartialCreditNote = $sale->creditNotes()
                ->where('status', 'accepted')
                ->where('credit_note_type', 'partial')
                ->latest()
                ->first();

            $acceptedPartialCreditNotePdfUrl = null;

            if (
                $acceptedPartialCreditNote &&
                !empty($acceptedPartialCreditNote->pdf_path)
            ) {
                $partialPdfPath = public_path(
                    'comprobantes/notas_credito/pdfs/' .
                    $acceptedPartialCreditNote->pdf_path
                );

                if (is_file($partialPdfPath)) {
                    $acceptedPartialCreditNotePdfUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_pdf', 'note_id' => $acceptedPartialCreditNote->id]);
                }
            }

            $hasAnyCreditNote =
                !is_null($pendingCreditNote) ||
                !is_null($rejectedCreditNote) ||
                !is_null($acceptedCreditNote);

            array_push($arraySales, [
                "id" => $sale->id,
                'created_by' => $sale->created_by,
                'created_by_name' => $sale->created_by_name ?: 'No registrado',
                "free_sale" => (bool) $sale->free_sale,
                "customer_id" => $sale->customer_id,
                "code" => "VENTA - ".$sale->id,
                "date" => ($sale->date_sale != null) ? $sale->formatted_sale_date : "",
                "currency" => ($sale->currency == 'PEN') ? 'Soles' : 'Dólares',
                //"total" => number_format($sale->importe_total, 2, '.', ''),
                "tipo_pago" => mb_strtoupper($this->cleanUtf8($tipoPagoLabel), 'UTF-8'),
                "dispatch_status" => $sale->dispatch_status ?? 'despachado',
                "nombre_cliente" => $this->cleanUtf8($nombreCliente),
                "tipo_documento_cliente" => $tipo_documento_cliente,
                "numero_documento_cliente" => $this->cleanUtf8($sale->numero_documento_cliente),
                "direccion_cliente" => $this->cleanUtf8($sale->direccion_cliente),
                "email_cliente" => $this->cleanUtf8($sale->email_cliente),
                "tipo_comprobante" => $tipo_comprobante,
                /*"print_url" => $this->cleanUtf8($printUrl),
                "print_label" => !empty($sale->pdf_path) ? 'Ver PDF' : 'Ver Ticket',*/
                "print_url" => $printUrl
                    ? $this->cleanUtf8($printUrl)
                    : null,

                "print_label" => $esComprobanteElectronico
                    ? ($pdfLocalValido ? 'Ver PDF' : 'PDF no disponible')
                    : 'Ver Ticket',

                "pdf_local_valido" => $pdfLocalValido,

                "can_retry_nubefact_files" =>
                    $puedeRecuperarArchivosNubefact,
                "type_document" => $sale->type_document,
                "sunat_status" => $sale->sunat_status,
                "pagos_parciales_venta" => $sale->pagos_parciales_venta,
                "total" => $totalTexto,
                "total_abonado" => $totalAbonado,
                "estado_pago_parcial" => $estadoPagoParcial,
                'invoice_info' => app(\App\Services\Sales\ExistingSaleInvoice::class)->flags($sale),
                'total_credit_note_info' => app(\App\Services\Sales\TotalCreditNoteService::class)->flags($sale),

                "annulment_status" => $sale->annulment_status,
                "internal_reversal_status" => $sale->internal_reversal_status,
                "annulment_type" => $sale->annulment_type,
                "estado_anulacion" => $estadoAnulacion,
                "estado_comprobante" => $estadoComprobante,
                "can_annul" => (int) $sale->state_annulled !== 1 && !app(\App\Services\Sales\PartialCreditSettlement::class)->hasPartialReturn($sale),
                /*"can_generate_credit_note" => $sale->annulment_status === 'requires_credit_note',*/
                "can_generate_credit_note" => !$hasAnyCreditNote && !$sale->free_sale && !$sale->state_annulled
                    && $sale->hasElectronicDocument() && $sale->serie_sunat && $sale->numero && !$sale->requiresEmissionReview()
                    && ($sale->annulment_type === 'credit_note' || !in_array($sale->annulment_status,['pending','waiting_sunat_process','unknown'],true)),
                "can_retry_annulment" => in_array($sale->annulment_status, ['waiting_sunat_process', 'rejected']),

                "is_annulled" => (int) $sale->state_annulled,
                "annulment_accepted_at" => $sale->annulment_accepted_at
                    ? Carbon::parse($sale->annulment_accepted_at)->format('d/m/Y h:i A')
                    : '',

                "annulment_pdf_url_local" => $sale->annulment_pdf_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'annul_pdf'])
                    : null,

                "annulment_xml_url_local" => $sale->annulment_xml_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'annul_xml'])
                    : null,

                "annulment_cdr_url_local" => $sale->annulment_cdr_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'annul_cdr'])
                    : null,

                'has_pending_credit_note' => !is_null($pendingCreditNote),
                'pending_credit_note_id' => optional($pendingCreditNote)->id,
                'pending_credit_note_type' => optional($pendingCreditNote)->credit_note_type,

                /*"has_rejected_credit_note" => !is_null($rejectedCreditNote),
                "rejected_credit_note_message" => optional($rejectedCreditNote)->sunat_message,*/
                "has_rejected_credit_note" => !is_null($rejectedCreditNote),

                "rejected_credit_note_id" => optional($rejectedCreditNote)->id,

                "rejected_credit_note_responsecode" => optional($rejectedCreditNote)->sunat_responsecode,

                "rejected_credit_note_message" => $this->cleanUtf8( optional($rejectedCreditNote)->sunat_message ),

                "is_credit_note_annulment" => !is_null($acceptedCreditNote),

                /*"credit_note_pdf_url_local" => $acceptedCreditNote && $acceptedCreditNote->pdf_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_pdf', 'note_id' => $acceptedCreditNote->id])
                    : null,

                "credit_note_xml_url_local" => $acceptedCreditNote && $acceptedCreditNote->xml_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_xml', 'note_id' => $acceptedCreditNote->id])
                    : null,

                "credit_note_cdr_url_local" => $acceptedCreditNote && $acceptedCreditNote->cdr_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_cdr', 'note_id' => $acceptedCreditNote->id])
                    : null,*/
                "credit_note_pdf_url_local" => $acceptedCreditNotePdfUrl,

                "credit_note_xml_url_local" => $acceptedCreditNoteXmlUrl,

                "credit_note_cdr_url_local" => $acceptedCreditNoteCdrUrl,

                "credit_note_status" => $sale->credit_note_status,

                "has_partial_credit_note" => !is_null($acceptedPartialCreditNote),

                /*"partial_credit_note_pdf_url_local" => $acceptedPartialCreditNote && $acceptedPartialCreditNote->pdf_path
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'credit_pdf', 'note_id' => $acceptedPartialCreditNote->id])
                    : null,*/
                "partial_credit_note_pdf_url_local" => $acceptedPartialCreditNotePdfUrl,

                "sunat_error_discarded_at" => $sale->sunat_error_discarded_at
                    ? Carbon::parse($sale->sunat_error_discarded_at)->format('d/m/Y h:i A')
                    : null,

                "sunat_error_discarded_by" => $sale->sunat_error_discarded_by,

                "sunat_error_discard_reason" => $this->cleanUtf8($sale->sunat_error_discard_reason),

                "sunat_error_is_discarded" => !empty($sale->sunat_error_discarded_at),
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
            'details.material',
            'details.stockItem'
        ])->find($orderId);

        if (!$sale) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $details = $sale->details->map(function ($detail) {
            return [
                'code' => optional($detail->stockItem)->sku
                    ?? optional($detail->material)->code
                    ?? '',

                'producto' => optional($detail->stockItem)->display_name
                    ?? optional($detail->material)->full_name
                    ?? $detail->description
                    ?? 'Producto',

                'quantity' => $detail->quantity,
                'price' => $detail->price,
                'total' => number_format($detail->total, 2),
            ];
        });

        return response()->json(['details' => $details], 200);
    }

    public function anularOrderO($id)
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

    public function anularOrder2($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with([
                'details',
            ])->lockForUpdate()->find($id);

            if (!$sale) {
                return response()->json(['message' => 'Orden no encontrada'], 422);
            }

            if ((int) $sale->state_annulled === 1) {
                return response()->json(['message' => 'La orden ya ha sido anulada previamente'], 422);
            }

            /*
             * ============================================================
             * 1) REVERTIR STOCK DESDE OUTPUT DETAILS
             * ============================================================
             * Ya NO se toca material->stock_current.
             *
             * Regla:
             * - Si output_detail tiene stock_lot_id:
             *      stock_lots.qty_on_hand += output_detail.percentage
             * - Si output_detail tiene item_id:
             *      item.state_item vuelve a entered
             * - Luego se sincroniza inventory_levels desde stock_lots
             * - Finalmente se elimina el output_detail para no afectar kardex
             */
            $inventoryKeysToSync = [];

            foreach ($sale->details as $detail) {
                $outputDetails = OutputDetail::where('sale_detail_id', $detail->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($outputDetails as $outputDetail) {
                    $qtyToReturn = (float) ($outputDetail->percentage ?? 0);

                    if ($qtyToReturn <= 0) {
                        $qtyToReturn = (float) ($detail->quantity ?? 0);
                    }

                    /*
                     * 1.1) Restaurar lote
                     */
                    if (!empty($outputDetail->stock_lot_id)) {
                        $lot = StockLot::where('id', $outputDetail->stock_lot_id)
                            ->lockForUpdate()
                            ->first();

                        if ($lot) {
                            $lot->qty_on_hand = (float) $lot->qty_on_hand + $qtyToReturn;
                            $lot->save();

                            $inventoryKeysToSync[] = [
                                'stock_item_id' => (int) $lot->stock_item_id,
                                'warehouse_id'  => $lot->warehouse_id,
                                'location_id'   => $lot->location_id,
                            ];
                        }
                    }

                    /*
                     * 1.2) Restaurar item físico si aplica
                     */
                    if (!empty($outputDetail->item_id)) {
                        $item = Item::where('id', $outputDetail->item_id)
                            ->lockForUpdate()
                            ->first();

                        if ($item) {
                            // Si luego necesitas diferenciar scrapped/entered original,
                            // conviene guardar previous_state_item en output_details.
                            $item->state_item = 'entered';
                            $item->save();
                        }
                    }

                    /*
                     * 1.3) Eliminar detalle de salida para que no afecte kardex
                     */
                    $outputDetail->delete();
                }
            }

            /*
             * 1.4) Sincronizar inventory_levels desde stock_lots
             */
            $inventoryKeysToSync = collect($inventoryKeysToSync)
                ->unique(function ($row) {
                    return implode('|', [
                        $row['stock_item_id'] ?? 'null',
                        $row['warehouse_id'] ?? 'null',
                        $row['location_id'] ?? 'null',
                    ]);
                })
                ->values()
                ->all();

            foreach ($inventoryKeysToSync as $key) {
                $this->syncInventoryLevelFromLots(
                    (int) $key['stock_item_id'],
                    $key['warehouse_id'],
                    $key['location_id']
                );
            }

            /*
             * ============================================================
             * 2) MARCAR VENTA COMO ANULADA
             * ============================================================
             */
            $sale->state_annulled = 1;
            $sale->save();

            /*
             * ============================================================
             * 3) REVERTIR MOVIMIENTOS DE CAJA
             * ============================================================
             */
            $movements = CashMovement::where('sale_id', $sale->id)->get();

            foreach ($movements as $movement) {
                $cashBoxSubType = $movement->cash_box_subtype_id
                    ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                    : null;

                $is_deferred = $cashBoxSubType ? (int) $cashBoxSubType->is_deferred : 0;
                $cash_box_subtype_id_to_use = $cashBoxSubType ? $cashBoxSubType->id : null;

                if ($movement->type === 'sale') {
                    if ($is_deferred == 1) {
                        if ((int) $movement->regularize === 0) {
                            $movement->delete();
                        } elseif ((int) $movement->regularize === 1) {
                            $amountToReverse = (float) ($movement->amount_regularize ?? $movement->amount ?? 0);

                            CashMovement::create([
                                'cash_register_id'      => $movement->cash_register_id,
                                'sale_id'               => $sale->id,
                                'type'                  => 'expense',
                                'amount'                => $amountToReverse,
                                'description'           => 'Reversión de venta (POS regularizado) por anulación de venta',
                                'regularize'            => $movement->regularize,
                                'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                            ]);

                            $cashRegister = CashRegister::find($movement->cash_register_id);
                            if ($cashRegister) {
                                $cashRegister->current_balance -= $amountToReverse;
                                $cashRegister->total_sales     -= $amountToReverse;
                                $cashRegister->total_expenses  += $amountToReverse;
                                $cashRegister->save();
                            }
                        }
                    } else {
                        $amountToReverse = (float) $movement->amount;

                        CashMovement::create([
                            'cash_register_id'      => $movement->cash_register_id,
                            'sale_id'               => $sale->id,
                            'type'                  => 'expense',
                            'amount'                => $amountToReverse,
                            'description'           => 'Reversión de venta por anulación de venta',
                            'regularize'            => $movement->regularize,
                            'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                        ]);

                        $cashRegister = CashRegister::find($movement->cash_register_id);
                        if ($cashRegister) {
                            $cashRegister->current_balance -= $amountToReverse;
                            $cashRegister->total_sales     -= $amountToReverse;
                            $cashRegister->total_expenses  += $amountToReverse;
                            $cashRegister->save();
                        }
                    }
                } elseif ($movement->type === 'expense') {
                    $amountToReverse = (float) $movement->amount;

                    CashMovement::create([
                        'cash_register_id'      => $movement->cash_register_id,
                        'sale_id'               => $sale->id,
                        'type'                  => 'income',
                        'amount'                => $amountToReverse,
                        'description'           => 'Reversión de gasto (vuelto) por anulación de orden',
                        'subtype'               => $movement->subtype,
                        'regularize'            => $movement->regularize,
                        'cash_box_subtype_id'   => $cash_box_subtype_id_to_use,
                    ]);

                    $cashRegister = CashRegister::find($movement->cash_register_id);
                    if ($cashRegister) {
                        $cashRegister->current_balance += $amountToReverse;
                        $cashRegister->total_incomes   += $amountToReverse;
                        $cashRegister->total_expenses  -= $amountToReverse;
                        $cashRegister->save();
                    }
                }
            }

            /*
             * ============================================================
             * 4) LIMPIAR RESERVAS ANTIGUAS SI EXISTEN
             * ============================================================
             * Para ventas nuevas ya no debería existir QuoteMaterialReservation.
             * Se deja solo como compatibilidad legacy.
             */
            if (!empty($sale->quote_id)) {
                QuoteMaterialReservation::where('quote_id', $sale->quote_id)
                    ->lockForUpdate()
                    ->delete();

                QuoteStockLot::where('quote_id', $sale->quote_id)
                    ->lockForUpdate()
                    ->delete();
            }

            DB::commit();

            return response()->json(['message' => 'Orden anulada con éxito'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 422);
        }
    }

    public function anularOrder1($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with([
                'details',
            ])->lockForUpdate()->find($id);

            if (!$sale) {
                return response()->json(['message' => 'Orden no encontrada'], 422);
            }

            if ((int) $sale->state_annulled === 1) {
                return response()->json(['message' => 'La orden ya ha sido anulada previamente'], 422);
            }

            $this->reverseSaleInternally(
                $sale,
                'Anulación interna de venta'
            );

            DB::commit();

            return response()->json([
                'message' => 'Orden anulada con éxito',
                'annulment_status' => 'accepted',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 422);
        }
    }

    public function annulmentPreview($id)
    {
        app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        app(\App\Services\Sales\SaleListAccess::class)->permission('annul_puntoVenta');
        try { return response()->json(app(\App\Services\Sales\SaleAnnulmentService::class)->preview($id)); }
        catch (\DomainException $e) { return response()->json(['message'=>$e->getMessage()],422); }
        catch (\Throwable $e) {
            $reference=bin2hex(random_bytes(12));
            \Illuminate\Support\Facades\Log::warning('Sale annulment preview needs review',['sale_id'=>$id,'reference'=>$reference,'exception'=>get_class($e)]);
            return response()->json(['message'=>'No se pudo preparar la anulación. Referencia: '.$reference,'diagnostic_reference'=>$reference],422);
        }
    }

    public function anularOrder(Request $request, $id)
    {
        return $this->runSaleAnnulment($request, $id, false);
    }

    private function runSaleAnnulment(Request $request, $id, bool $consult)
    {
        $data=$request->validate(['financial_token'=>'nullable|string|size:64','refund_cash_box_id'=>'nullable|integer|min:1','refund_subtype_id'=>'nullable|integer|min:1']);
        // Authorization is checked before the safe-error boundary as well as by the route guard.
        app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        app(\App\Services\Sales\SaleListAccess::class)->permission('annul_puntoVenta');
        try {
            return response()->json(app(\App\Services\Sales\SaleAnnulmentService::class)->execute($id,$data,$consult,function($sale,$result){$this->saveAnnulmentFiles($sale,$result);}));
        } catch (\DomainException $e) { return response()->json(['message'=>$e->getMessage()],422); }
        catch (\Illuminate\Validation\ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            $reference=bin2hex(random_bytes(12));
            \Illuminate\Support\Facades\Log::warning('Sale annulment needs review',['sale_id'=>$id,'reference'=>$reference,'exception'=>get_class($e)]);
            return response()->json(['message'=>'No se confirmó el resultado. Recargue y consulte la anulación antes de intentar otra operación. Referencia: '.$reference,'diagnostic_reference'=>$reference],422);
        }
    }

    private function saveAnnulmentFiles(Sale $sale, array $result): void
    {
        $updates=[];$failed=false;
        foreach(['pdf'=>'pdfs','xml'=>'xmls','cdr'=>'cdrs']as$type=>$folder){
            $url=$result['enlace_del_'.$type]??null;if(!$url)continue;
            $name='ANULACION_ORD'.$sale->id.'.'.($type==='cdr'?'cdr':$type);
            $dir=public_path('comprobantes/anulaciones/'.$folder);
            try { if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new \RuntimeException();
                $parts=parse_url($url);$host=strtolower($parts['host']??'');
                if(($parts['scheme']??'')!=='https'||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])&&$parts['port']!==443||!($host==='nubefact.com'||substr($host,-13)==='.nubefact.com'))throw new \RuntimeException('Untrusted file host');
                $response=\Illuminate\Support\Facades\Http::withOptions(['allow_redirects'=>false])->timeout(60)->get($url);$bytes=$response->body();
                if(!$response->successful()||!$this->contenidoNubefactEsValido($bytes,$type==='cdr'?'zip':$type,$response->header('Content-Type')))throw new \RuntimeException('Invalid file');
                $temp=$dir.'/'.$name.'.'.bin2hex(random_bytes(6)).'.tmp';
                if(file_put_contents($temp,$bytes,LOCK_EX)!==strlen($bytes)){@unlink($temp);throw new \RuntimeException('File write failed');}
                if(!rename($temp,$dir.'/'.$name)){@unlink($temp);throw new \RuntimeException('File replacement failed');}
                $updates['annulment_'.$type.'_path']=$name;
            } catch (\Throwable $ignored) { $failed=true; }
        }
        if($updates)Sale::where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)->where('branch_id',$sale->branch_id)->whereKey($sale->id)->update($updates);
        if($failed)throw new \RuntimeException('Annulment files pending');
    }

    private function saveTotalCreditFiles(CreditNote $note, array $result): void
    {
        $sale=app(\App\Services\Sales\SaleListAccess::class)->sale($note->sale_id);
        $updates=[];$failed=false;
        foreach(['pdf'=>'pdfs','xml'=>'xmls','cdr'=>'cdrs']as$type=>$folder){
            $url=$result['enlace_del_'.$type]??null;if(!$url)continue;
            $name='NC_'.$note->id.'.'.($type==='cdr'?'zip':$type);
            $dir=public_path('comprobantes/notas_credito/'.$folder);
            try { if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new \RuntimeException();
                $parts=parse_url($url);$host=strtolower($parts['host']??'');
                if(($parts['scheme']??'')!=='https'||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])&&$parts['port']!==443||!($host==='nubefact.com'||substr($host,-13)==='.nubefact.com'))throw new \RuntimeException('Untrusted file host');
                $response=\Illuminate\Support\Facades\Http::withOptions(['allow_redirects'=>false])->timeout(60)->get($url);$bytes=$response->body();
                if(!$response->successful()||!$this->contenidoNubefactEsValido($bytes,$type==='cdr'?'zip':$type,$response->header('Content-Type')))throw new \RuntimeException('Invalid file');
                $temp=$dir.'/'.$name.'.'.bin2hex(random_bytes(6)).'.tmp';
                if(file_put_contents($temp,$bytes,LOCK_EX)!==strlen($bytes)){@unlink($temp);throw new \RuntimeException('File write failed');}
                if(!rename($temp,$dir.'/'.$name)){@unlink($temp);throw new \RuntimeException('File replacement failed');}
                $updates[$type.'_path']=$name;
            } catch (\Throwable $ignored) { $failed=true; }
        }
        if($updates)CreditNote::where('sale_id',$sale->id)->whereKey($note->id)->update($updates);
        if($failed)throw new \RuntimeException('Annulment files pending');
    }

    private function reverseSaleInternallyO(
        Sale $sale,
        ?string $reason = null,
        bool $markAsAnnulled = true
    ): void
    {
        if ($sale->internal_reversal_status === 'reversed') {
            return;
        }

        /*
         * Indica si la venta contenía por lo menos un producto itemeable.
         *
         * Debemos detectarlo antes de eliminar los OutputDetail,
         * porque después ya no tendremos esa información operativa.
         */
        $hasItemizedProducts = false;

        /*
         * ============================================================
         * 1) REVERTIR STOCK DESDE OUTPUT DETAILS
         * ============================================================
         */
        $inventoryKeysToSync = [];

        foreach ($sale->details as $detail) {
            $outputDetails = OutputDetail::where('sale_detail_id', $detail->id)
                ->lockForUpdate()
                ->get();

            foreach ($outputDetails as $outputDetail) {
                $qtyToReturn = (float) ($outputDetail->percentage ?? 0);

                if ($qtyToReturn <= 0) {
                    $qtyToReturn = (float) ($detail->quantity ?? 0);
                }

                if (!empty($outputDetail->stock_lot_id)) {
                    $lot = StockLot::where('id', $outputDetail->stock_lot_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {
                        $lot->qty_on_hand = (float) $lot->qty_on_hand + $qtyToReturn;
                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' => (int) $lot->stock_item_id,
                            'warehouse_id'  => $lot->warehouse_id,
                            'location_id'   => $lot->location_id,
                        ];
                    }
                }

                /*
                 * Detectamos que la venta tenía un producto itemeable.
                 */
                if (!empty($outputDetail->item_id)) {
                    $hasItemizedProducts = true;

                    $item = Item::where('id', $outputDetail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        $item->state_item = 'entered';
                        $item->save();
                    }
                }

                /*
                 * Después de devolver el lote/item, eliminamos el detalle
                 * de salida igual que antes.
                 */
                $outputDetail->delete();
            }
        }

        /*
         * ============================================================
         * 2) SINCRONIZAR INVENTORY LEVELS
         * ============================================================
         */
        $inventoryKeysToSync = collect($inventoryKeysToSync)
            ->unique(function ($row) {
                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {
            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }

        /*
         * ============================================================
         * 3) REVERTIR MOVIMIENTOS DE CAJA
         * ============================================================
         */
        $movements = CashMovement::where('sale_id', $sale->id)
            ->lockForUpdate()
            ->get();

        foreach ($movements as $movement) {
            $cashBoxSubType = $movement->cash_box_subtype_id
                ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                : null;

            $is_deferred = $cashBoxSubType
                ? (int) $cashBoxSubType->is_deferred
                : 0;

            $cash_box_subtype_id_to_use = $cashBoxSubType
                ? $cashBoxSubType->id
                : null;

            if ($movement->type === 'sale') {
                if ($is_deferred == 1) {
                    if ((int) $movement->regularize === 0) {
                        $movement->delete();

                    } elseif ((int) $movement->regularize === 1) {
                        $amountToReverse = (float) (
                            $movement->amount_regularize
                            ?? $movement->amount
                            ?? 0
                        );

                        CashMovement::create([
                            'cash_register_id'    => $movement->cash_register_id,
                            'sale_id'             => $sale->id,
                            'type'                => 'expense',
                            'amount'              => $amountToReverse,
                            'description'         => 'Reversión de venta (POS regularizado) por anulación de venta #'
                                . $sale->id,
                            'regularize'          => $movement->regularize,
                            'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                        ]);

                        $cashRegister = CashRegister::where(
                            'id',
                            $movement->cash_register_id
                        )
                            ->lockForUpdate()
                            ->first();

                        if ($cashRegister) {
                            $cashRegister->current_balance -= $amountToReverse;
                            $cashRegister->total_sales     -= $amountToReverse;
                            $cashRegister->total_expenses  += $amountToReverse;
                            $cashRegister->save();
                        }
                    }
                } else {
                    $amountToReverse = (float) $movement->amount;

                    CashMovement::create([
                        'cash_register_id'    => $movement->cash_register_id,
                        'sale_id'             => $sale->id,
                        'type'                => 'expense',
                        'amount'              => $amountToReverse,
                        'description'         => 'Reversión de venta por anulación de venta #'
                            . $sale->id,
                        'regularize'          => $movement->regularize,
                        'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                    ]);

                    $cashRegister = CashRegister::where(
                        'id',
                        $movement->cash_register_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if ($cashRegister) {
                        $cashRegister->current_balance -= $amountToReverse;
                        $cashRegister->total_sales     -= $amountToReverse;
                        $cashRegister->total_expenses  += $amountToReverse;
                        $cashRegister->save();
                    }
                }

            } elseif ($movement->type === 'expense') {
                $amountToReverse = (float) $movement->amount;

                CashMovement::create([
                    'cash_register_id'    => $movement->cash_register_id,
                    'sale_id'             => $sale->id,
                    'type'                => 'income',
                    'amount'              => $amountToReverse,
                    'description'         => 'Reversión de gasto (vuelto) por anulación de orden #'
                        . $sale->id,
                    'subtype'             => $movement->subtype,
                    'regularize'          => $movement->regularize,
                    'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                ]);

                $cashRegister = CashRegister::where(
                    'id',
                    $movement->cash_register_id
                )
                    ->lockForUpdate()
                    ->first();

                if ($cashRegister) {
                    $cashRegister->current_balance += $amountToReverse;
                    $cashRegister->total_incomes   += $amountToReverse;
                    $cashRegister->total_expenses  -= $amountToReverse;
                    $cashRegister->save();
                }
            }
        }

        /*
         * ============================================================
         * 4) ACTUALIZAR ESTADO DE LA COTIZACIÓN
         * ============================================================
         *
         * Si la venta retiró al menos un Item físico:
         * - La cotización no puede reconstruir la selección.
         * - Se marca como canceled.
         *
         * Si no retiró Items físicos:
         * - Puede utilizarse como origen para recotizar.
         * - Se marca como requote.
        */
        /*if (
            $hasItemizedProducts &&
            !empty($sale->quote_id)
        ) {
            $quote = Quote::where('id', $sale->quote_id)
                ->lockForUpdate()
                ->first();

            if ($quote && $quote->state !== 'canceled') {
                $quote->state = 'canceled';
                $quote->save();
            }
        }*/
        if (!empty($sale->quote_id)) {
            $quote = Quote::where('id', $sale->quote_id)
                ->lockForUpdate()
                ->first();

            if ($quote) {
                $quote->state = $hasItemizedProducts
                    ? 'canceled'
                    : 'requote';

                $quote->save();
            }
        }

        /*
         * ============================================================
         * 5) LIMPIAR RESERVAS LEGACY
         * ============================================================
         */
        if (!empty($sale->quote_id)) {
            QuoteMaterialReservation::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();

            QuoteStockLot::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();
        }

        /*
         * ============================================================
         * 6) ANULAR PAGOS PARCIALES
         * ============================================================
         */
        $partialPayments = SalePartialPayment::where('sale_id', $sale->id)
            ->lockForUpdate()
            ->get();

        foreach ($partialPayments as $partialPayment) {
            $partialPayment->state = 0;
            $partialPayment->save();
        }

        /*
         * ============================================================
         * 7) MARCAR REVERSIÓN OPERATIVA
         * ============================================================
         */
        if ($markAsAnnulled) {
            $sale->state_annulled = 1;
        }

        $sale->internal_reversal_status = 'reversed';
        $sale->internal_reversed_at = now();
        $sale->internal_reversed_by = auth()->id();

        if (
            empty($sale->annulment_status) ||
            $sale->annulment_status === 'none'
        ) {
            $sale->annulment_status = $markAsAnnulled
                ? 'accepted'
                : 'pending';
        }

        $sale->annulment_reason = $reason;

        if (empty($sale->annulment_requested_at)) {
            $sale->annulment_requested_at = now();
        }

        if (empty($sale->annulment_requested_by)) {
            $sale->annulment_requested_by = auth()->id();
        }

        if ($markAsAnnulled) {
            $sale->annulment_accepted_at =
                $sale->annulment_accepted_at ?: now();

            $sale->annulled_by =
                $sale->annulled_by ?: auth()->id();
        }

        $sale->save();
    }

    private function reverseSaleInternally(
        Sale $sale,
        ?string $reason = null,
        bool $markAsAnnulled = true
    ): void
    {


        if ($sale->internal_reversal_status === 'reversed') {
            return;
        }

        /*
         * Indica si la venta contenía por lo menos un producto itemeable.
         *
         * Debemos detectarlo antes de eliminar los OutputDetail,
         * porque después ya no tendremos esa información operativa.
         */
        if ($sale->creditNotes()->where('credit_note_type', 'partial')->where(function ($q) {
            $q->whereIn('status', ['pending', 'accepted'])->orWhere('internal_reversal_status', 'reversed');
        })->exists()) { throw new \DomainException('Esta venta tiene devoluciones parciales. Devuelva únicamente las cantidades restantes mediante notas parciales.'); }
        $refundService = app(\App\Services\Sales\DeferredSaleRefund::class);
        $deferredRefunds = $refundService->plan($sale);

        $hasItemizedProducts = false;

        /*
         * ============================================================
         * 1) REVERTIR STOCK DESDE OUTPUT DETAILS
         * ============================================================
         */
        $inventoryKeysToSync = [];

        foreach ($sale->details as $detail) {
            $outputDetails = OutputDetail::where('sale_detail_id', $detail->id)
                ->lockForUpdate()
                ->get();

            foreach ($outputDetails as $outputDetail) {
                $qtyToReturn = (float) ($outputDetail->percentage ?? 0);

                if ($qtyToReturn <= 0) {
                    $qtyToReturn = (float) ($detail->quantity ?? 0);
                }

                if (!empty($outputDetail->stock_lot_id)) {
                    $lot = StockLot::where('id', $outputDetail->stock_lot_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {
                        $lot->qty_on_hand = (float) $lot->qty_on_hand + $qtyToReturn;
                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' => (int) $lot->stock_item_id,
                            'warehouse_id'  => $lot->warehouse_id,
                            'location_id'   => $lot->location_id,
                        ];
                    }
                }

                /*
                 * Detectamos que la venta tenía un producto itemeable.
                 */
                if (!empty($outputDetail->item_id)) {
                    $hasItemizedProducts = true;

                    $item = Item::where('id', $outputDetail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        $item->state_item = 'entered';
                        $item->save();
                    }
                }

                /*
                 * Después de devolver el lote/item, eliminamos el detalle
                 * de salida igual que antes.
                 */
                $outputDetail->delete();
            }
        }

        /*
         * ============================================================
         * 2) SINCRONIZAR INVENTORY LEVELS
         * ============================================================
         */
        $inventoryKeysToSync = collect($inventoryKeysToSync)
            ->unique(function ($row) {
                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {
            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }

        /*
         * ============================================================
         * 3) REVERTIR MOVIMIENTOS DE CAJA
         * ============================================================
         */
        $movements = CashMovement::where('tenant_id', $sale->tenant_id)
            ->where('company_id', $sale->company_id)->where('branch_id', $sale->branch_id)
            ->where('sale_id', $sale->id)->lockForUpdate()
            ->get();

        $refundService->post($sale, $deferredRefunds);

        foreach ($movements as $movement) {
            if (array_key_exists($movement->id, $deferredRefunds) || $movement->cash_movement_origin_id) {
                continue;
            }
            if (SalePartialPayment::where('sale_id', $sale->id)->where('cash_movement_id', $movement->id)
                ->where('state', 0)->exists()) {
                continue;
            }
            if ($movement->type === 'expense' && strpos((string) $movement->description, 'Reversión de pago parcial') === 0) {
                continue;
            }

            if (in_array($movement->type, ['sale', 'income'], true) && (int) $movement->regularize === 0) {
                $movement->cancelPendingDeferred('sale_reversal');
                continue;
            }

            $cashBoxSubType = $movement->cash_box_subtype_id
                ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                : null;

            $is_deferred = $cashBoxSubType
                ? (int) $cashBoxSubType->is_deferred
                : 0;

            $cash_box_subtype_id_to_use = $cashBoxSubType
                ? $cashBoxSubType->id
                : null;

            if ($movement->type === 'sale') {
                if ($is_deferred == 1) {
                    if ((int) $movement->regularize === 1) {
                        $amountToReverse = (float) (
                            $movement->amount_regularize
                            ?? $movement->amount
                            ?? 0
                        );

                        CashMovement::create([
                            'cash_register_id'    => $movement->cash_register_id,
                            'sale_id'             => $sale->id,
                            'type'                => 'expense',
                            'amount'              => $amountToReverse,
                            'description'         => 'Reversión de venta (POS regularizado) por anulación de venta #'
                                . $sale->id,
                            'regularize'          => $movement->regularize,
                            'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                        ]);

                        $cashRegister = CashRegister::where(
                            'id',
                            $movement->cash_register_id
                        )
                            ->lockForUpdate()
                            ->first();

                        if ($cashRegister) {
                            $cashRegister->current_balance -= $amountToReverse;
                            $cashRegister->total_sales     -= $amountToReverse;
                            $cashRegister->total_expenses  += $amountToReverse;
                            $cashRegister->save();
                        }
                    }
                } else {
                    $amountToReverse = (float) $movement->amount;

                    CashMovement::create([
                        'cash_register_id'    => $movement->cash_register_id,
                        'sale_id'             => $sale->id,
                        'type'                => 'expense',
                        'amount'              => $amountToReverse,
                        'description'         => 'Reversión de venta por anulación de venta #'
                            . $sale->id,
                        'regularize'          => $movement->regularize,
                        'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                    ]);

                    $cashRegister = CashRegister::where(
                        'id',
                        $movement->cash_register_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if ($cashRegister) {
                        $cashRegister->current_balance -= $amountToReverse;
                        $cashRegister->total_sales     -= $amountToReverse;
                        $cashRegister->total_expenses  += $amountToReverse;
                        $cashRegister->save();
                    }
                }

            } elseif ($movement->type === 'expense') {
                $amountToReverse = (float) $movement->amount;

                CashMovement::create([
                    'cash_register_id'    => $movement->cash_register_id,
                    'sale_id'             => $sale->id,
                    'type'                => 'income',
                    'amount'              => $amountToReverse,
                    'description'         => 'Reversión de gasto (vuelto) por anulación de orden #'
                        . $sale->id,
                    'subtype'             => $movement->subtype,
                    'regularize'          => $movement->regularize,
                    'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                ]);

                $cashRegister = CashRegister::where(
                    'id',
                    $movement->cash_register_id
                )
                    ->lockForUpdate()
                    ->first();

                if ($cashRegister) {
                    $cashRegister->current_balance += $amountToReverse;
                    $cashRegister->total_incomes   += $amountToReverse;
                    $cashRegister->total_expenses  -= $amountToReverse;
                    $cashRegister->save();
                }
            }
        }

        /*
         * ============================================================
         * 4) ACTUALIZAR ESTADO DE LA COTIZACIÓN
         * ============================================================
         *
         * Si la venta retiró al menos un Item físico:
         * - La cotización no puede reconstruir la selección.
         * - Se marca como canceled.
         *
         * Si no retiró Items físicos:
         * - Puede utilizarse como origen para recotizar.
         * - Se marca como requote.
        */
        /*if (
            $hasItemizedProducts &&
            !empty($sale->quote_id)
        ) {
            $quote = Quote::where('id', $sale->quote_id)
                ->lockForUpdate()
                ->first();

            if ($quote && $quote->state !== 'canceled') {
                $quote->state = 'canceled';
                $quote->save();
            }
        }*/
        if (!empty($sale->quote_id)) {
            $quote = Quote::where('id', $sale->quote_id)
                ->lockForUpdate()
                ->first();

            if ($quote) {
                $quote->state = $hasItemizedProducts
                    ? 'canceled'
                    : 'requote';

                $quote->save();
            }
        }

        /*
         * ============================================================
         * 5) LIMPIAR RESERVAS LEGACY
         * ============================================================
         */
        if (!empty($sale->quote_id)) {
            QuoteMaterialReservation::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();

            QuoteStockLot::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();
        }

        /*
         * ============================================================
         * 6) ANULAR PAGOS PARCIALES
         * ============================================================
         */
        $partialPayments = SalePartialPayment::where('sale_id', $sale->id)
            ->lockForUpdate()
            ->get();

        foreach ($partialPayments as $partialPayment) {
            $partialPayment->state = 0;
            $partialPayment->save();
        }

        /*
         * ============================================================
         * 7) MARCAR REVERSIÓN OPERATIVA
         * ============================================================
         */
        if ($markAsAnnulled) {
            $sale->state_annulled = 1;
        }

        $sale->internal_reversal_status = 'reversed';
        $sale->internal_reversed_at = now();
        $sale->internal_reversed_by = auth()->id();

        if (
            empty($sale->annulment_status) ||
            $sale->annulment_status === 'none'
        ) {
            $sale->annulment_status = $markAsAnnulled
                ? 'accepted'
                : 'pending';
        }

        $sale->annulment_reason = $reason;

        if (empty($sale->annulment_requested_at)) {
            $sale->annulment_requested_at = now();
        }

        if (empty($sale->annulment_requested_by)) {
            $sale->annulment_requested_by = auth()->id();
        }

        if ($markAsAnnulled) {
            $sale->annulment_accepted_at =
                $sale->annulment_accepted_at ?: now();

            $sale->annulled_by =
                $sale->annulled_by ?: auth()->id();
        }

        $sale->save();
    }

    private function assertPartialCreditLot($detail, $output, $lot): void
    {
        foreach (['stock_item_id', 'warehouse_id', 'location_id'] as $field) {
            if (!$output->$field || (int) $lot->$field !== (int) $output->$field) {
                throw new \DomainException('El lote no coincide con el producto y la ubicación de la salida original.');
            }
        }
        if ((int) $detail->stock_item_id !== (int) $lot->stock_item_id) {
            throw new \DomainException('El lote no corresponde al producto vendido.');
        }
    }

    private function reverseCreditNoteStockPartially(CreditNote $creditNote, bool $preflight = false): void
    {
        if (
            $creditNote->internal_reversal_status === 'reversed'
        ) {
            return;
        }

        $creditNote->loadMissing([
            'details.saleDetail',
            'details.items',
        ]);

        $inventoryKeysToSync = [];
        $sale = $creditNote->sale;
        $returnLedger = app(\App\Services\Inventory\CreditNoteReturnLedger::class);
        $returnDate = now();


        foreach ($creditNote->details as $creditNoteDetail) {
            $saleDetail = $creditNoteDetail->saleDetail;

            if (!$saleDetail || (int) $saleDetail->sale_id !== (int) $sale->id || (int) $saleDetail->tenant_id !== (int) $sale->tenant_id || (int) $saleDetail->company_id !== (int) $sale->company_id) {
                throw new \DomainException('No se identificó un producto de esta venta y empresa.');
            }

            /*
             * ============================================================
             * PRODUCTO ITEMEABLE
             * ============================================================
             *
             * Si existen registros asociados, devolvemos solamente
             * los items seleccionados por el usuario.
             */
            if ($creditNoteDetail->items->isNotEmpty()) {
                foreach ($creditNoteDetail->items as $selectedItem) {
                    $outputDetail = OutputDetail::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                        'id',
                        $selectedItem->output_detail_id
                    )
                        ->where(
                            'sale_detail_id',
                            $saleDetail->id
                        )
                        ->where(
                            'item_id',
                            $selectedItem->item_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$outputDetail) {
                        throw new \Exception(
                            'No se encontró la salida original del item '
                            . ($selectedItem->item_code ?: '#' . $selectedItem->item_id)
                            . '.'
                        );
                    }

                    $qtyToApply = (float) (
                    $selectedItem->quantity ?: 1
                    );

                    if ($qtyToApply <= 0) {
                        $qtyToApply = 1;
                    }

                    $stockLotId =
                        $selectedItem->stock_lot_id
                            ?: $outputDetail->stock_lot_id;
                    if (!$stockLotId || (int) $stockLotId !== (int) $outputDetail->stock_lot_id) { throw new \DomainException('El item no conserva su lote original.'); }

                    if (!empty($stockLotId)) {
                        $lot = StockLot::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                            'id',
                            $stockLotId
                        )
                            ->lockForUpdate()
                            ->first();

                        if (!$lot) {
                            throw new \Exception(
                                'No se encontró el lote del item '
                                . ($selectedItem->item_code ?: '#' . $selectedItem->item_id)
                                . '.'
                            );
                        }

                        $this->assertPartialCreditLot($saleDetail, $outputDetail, $lot);
                        $ledgerRow = $returnLedger->row($creditNote, $creditNoteDetail, $outputDetail, $lot, $qtyToApply, $returnDate);
                        if (!$preflight) { $returnLedger->record($ledgerRow, $creditNote); }

                        $expectedLotQuantity = bcadd((string) $lot->qty_on_hand, (string) $qtyToApply, 6);
                        $lot->qty_on_hand = $expectedLotQuantity;
                        $lot->save();
                        $lot->refresh();
                        if (bccomp((string) $lot->qty_on_hand, $expectedLotQuantity, 6) !== 0) {
                            throw new \DomainException('La cantidad devuelta supera la precisión física del lote. Revise las unidades antes de emitir la nota.');
                        }

                        $inventoryKeysToSync[] = [
                            'stock_item_id' =>
                                (int) $lot->stock_item_id,
                            'warehouse_id' =>
                                $lot->warehouse_id,
                            'location_id' =>
                                $lot->location_id,
                        ];
                    }

                    $item = Item::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                        'id',
                        $selectedItem->item_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if (!$item) {
                        throw new \Exception(
                            'No se encontró el item '
                            . ($selectedItem->item_code ?: '#' . $selectedItem->item_id)
                            . '.'
                        );
                    }

                    $item->state_item = 'entered';
                    $item->save();

                    /*
                     * No eliminamos el OutputDetail por ahora.
                     * La tabla credit_note_detail_items permite saber
                     * que esta salida ya fue devuelta.
                     */
                }

                continue;
            }

            /*
             * ============================================================
             * PRODUCTO NORMAL / NO ITEMEABLE
             * ============================================================
             */
            $qtyToReturn =
                (float) $creditNoteDetail->quantity;

            if ($qtyToReturn <= 0) {
                continue;
            }

            $outputDetails = OutputDetail::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                'sale_detail_id',
                $saleDetail->id
            )
                ->where(function ($q) { $q->whereNull('item_id')->orWhere('item_id', 0); })
                ->orderBy('id')->lockForUpdate()
                ->get();

            /*
             * Compatibilidad por si existen salidas antiguas con item_id = 0.
             */
            if ($outputDetails->isEmpty()) {
                $outputDetails = OutputDetail::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                    'sale_detail_id',
                    $saleDetail->id
                )
                    ->where(function ($query) {
                        $query->whereNull('item_id')
                            ->orWhere('item_id', 0);
                    })
                    ->lockForUpdate()
                    ->get();
            }

            $previous = \App\CreditNoteDetail::where('sale_detail_id', $saleDetail->id)
                ->whereHas('creditNote', function ($q) use ($creditNote) {
                    $q->where('sale_id', $creditNote->sale_id)->where('id', '!=', $creditNote->id)->where('internal_reversal_status', 'reversed');
                })->get();
            foreach ($previous as $prior) {
                if ((int) $prior->creditNote->settlement_version !== 1) { throw new \DomainException('Las devoluciones históricas de este producto requieren conciliar sus lotes.'); }
            }
            $skipReturned = (float) $previous->sum('quantity');
            foreach ($outputDetails as $outputDetail) {
                if ($qtyToReturn <= 0) {
                    break;
                }

                $qtyFromOutput = (float) (
                    $outputDetail->percentage ?? 0
                );

                if ($qtyFromOutput <= 0 && $outputDetails->count() > 1) { throw new \DomainException('No se conserva la distribución de cantidades entre los lotes originales. Requiere conciliación.'); }
                if ($qtyFromOutput <= 0) {
                    $qtyFromOutput =
                        (float) $saleDetail->quantity;
                }

                $skip = min($skipReturned, $qtyFromOutput);
                $skipReturned -= $skip;
                $qtyToApply = min($qtyToReturn, $qtyFromOutput - $skip);
                if ($qtyToApply <= 0) { continue; }
                if (!$outputDetail->stock_lot_id) { throw new \DomainException('La salida no conserva el lote de origen. Requiere conciliación.'); }

                if (!empty($outputDetail->stock_lot_id)) {
                    $lot = StockLot::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)->where(
                        'id',
                        $outputDetail->stock_lot_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if (!$lot) { throw new \DomainException('El lote original no pertenece a la empresa de la venta.'); }
                    if ($lot) {
                        $this->assertPartialCreditLot($saleDetail, $outputDetail, $lot);
                        $ledgerRow = $returnLedger->row($creditNote, $creditNoteDetail, $outputDetail, $lot, $qtyToApply, $returnDate);
                        if (!$preflight) { $returnLedger->record($ledgerRow, $creditNote); }

                        $expectedLotQuantity = bcadd((string) $lot->qty_on_hand, (string) $qtyToApply, 6);
                        $lot->qty_on_hand = $expectedLotQuantity;
                        $lot->save();
                        $lot->refresh();
                        if (bccomp((string) $lot->qty_on_hand, $expectedLotQuantity, 6) !== 0) {
                            throw new \DomainException('La cantidad devuelta supera la precisión física del lote. Revise las unidades antes de emitir la nota.');
                        }

                        $inventoryKeysToSync[] = [
                            'stock_item_id' =>
                                (int) $lot->stock_item_id,
                            'warehouse_id' =>
                                $lot->warehouse_id,
                            'location_id' =>
                                $lot->location_id,
                        ];
                    }
                }

                $qtyToReturn -= $qtyToApply;
            }

            if ($qtyToReturn > 0.000001) {
                throw new \Exception(
                    'No se pudo identificar todo el stock que debe '
                    . 'retornar para el detalle #' . $saleDetail->id . '.'
                );
            }
        }

        /*
         * ============================================================
         * SINCRONIZAR INVENTORY LEVELS
         * ============================================================
         */
        $inventoryKeysToSync = collect(
            $inventoryKeysToSync
        )
            ->unique(function ($row) {
                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {
            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
            $lotQuantity = StockLot::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
                ->where('stock_item_id', $key['stock_item_id'])->where('warehouse_id', $key['warehouse_id'])
                ->where('location_id', $key['location_id'])->sum('qty_on_hand');
            $levelQuantity = InventoryLevel::where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
                ->where('stock_item_id', $key['stock_item_id'])->where('warehouse_id', $key['warehouse_id'])
                ->where('location_id', $key['location_id'])->sum('qty_on_hand');
            if (bccomp(number_format((float) $lotQuantity, 6, '.', ''), number_format((float) $levelQuantity, 6, '.', ''), 6) !== 0) {
                throw new \DomainException('La precisión del nivel físico no permite registrar esta devolución sin diferencias.');
            }

        }
        if (!$preflight) {
            $creditNote->stock_return_version = 1;
            $creditNote->save();
        }

    }

    public function consultarAnulacion(Request $request, $id)
    {
        return $this->runSaleAnnulment($request, $id, true);
    }

    public function totalCreditPreview($id)
    {
        app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        app(\App\Services\Sales\SaleListAccess::class)->permission('annul_puntoVenta');
        try { return response()->json(app(\App\Services\Sales\TotalCreditNoteService::class)->preview($id)); }
        catch (\Illuminate\Validation\ValidationException $e) { throw $e; }
        catch (\DomainException $e) { return response()->json(['message'=>$e->getMessage()],422); }
        catch (\Throwable $e) { return $this->totalCreditError($id,$e); }
    }

    public function generarNotaCreditoTotal(Request $request, $id)
    {
        return $this->runTotalCreditNote($request,$id,false);
    }

    private function runTotalCreditNote(Request $request, $id, bool $consult)
    {
        $data=$request->validate(['financial_token'=>'nullable|string|size:64','refund_cash_box_id'=>'nullable|integer|min:1',
            'refund_subtype_id'=>'nullable|integer|min:1','total_request_key'=>'nullable|string|max:64|regex:/^[A-Za-z0-9_-]+$/D']);
        app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        app(\App\Services\Sales\SaleListAccess::class)->permission('annul_puntoVenta');
        try {
            return response()->json(app(\App\Services\Sales\TotalCreditNoteService::class)->execute($id,$data,$consult,
                function($note,$result){$this->saveTotalCreditFiles($note,$result);}));
        } catch (\DomainException $e) { return response()->json(['message'=>$e->getMessage()],422); }
        catch (\Illuminate\Validation\ValidationException $e) { throw $e; }
        catch (\Throwable $e) { return $this->totalCreditError($id,$e); }
    }

    private function totalCreditError($id, \Throwable $error)
    {
        $reference=bin2hex(random_bytes(12));
        \Illuminate\Support\Facades\Log::warning('Total credit note requires review',['sale_id'=>$id,'reference'=>$reference,'exception'=>get_class($error)]);
        return response()->json(['message'=>'No se confirmó el resultado de la NC total. Consulte su estado antes de otra operación. Referencia: '.$reference,
            'diagnostic_reference'=>$reference],422);
    }

    public function consultarNotaCredito($id)
    {
        $scopedSale=app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        $latestNote=$scopedSale->creditNotes()->latest('id')->first();
        if($latestNote&&$latestNote->credit_note_type==='total'){
            return $this->runTotalCreditNote(app('request'),$id,true);
        }

        $scopedSale = app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        $newPartial = app(\App\Services\Sales\PartialCreditSettlement::class)->pending($scopedSale)
            ->where('settlement_version', 1)->latest('id')->first();
        if ($newPartial) { return $this->consultPartialCreditNote($newPartial); }
        if (!$scopedSale->creditNotes()->where('status', 'pending')->exists()) {
            $alreadyAppliedPartial = $scopedSale->creditNotes()->where('settlement_version', 1)
                ->where('credit_note_type', 'partial')->where('status', 'accepted')
                ->where('internal_reversal_status', 'reversed')->latest('id')->first();
            if ($alreadyAppliedPartial) { return $this->completePartialCreditNote($alreadyAppliedPartial->id); }
        }


        DB::beginTransaction();

        try {
            $sale = Sale::with(['details'])
                ->lockForUpdate()
                ->find($id);

            if (!$sale) {
                return response()->json([
                    'message' => 'Venta no encontrada.',
                ], 422);
            }

            $creditNote = CreditNote::where('sale_id', $sale->id)
                ->where('status', 'pending')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (!$creditNote) {
                return response()->json([
                    'message' => 'Esta venta no tiene una Nota de Crédito pendiente.',
                ], 422);
            }

            $result = $this->consultarNotaCreditoNubefact($creditNote);

            $this->persistNubefactCreditNoteResult($creditNote, $result);

            $creditNote->refresh();
            $sale->refresh();

            if ($creditNote->status === 'accepted') {

                /*
                 * Si por algún motivo aún no se aplicó la reversión operativa,
                 * se aplica aquí. Si ya se aplicó, no duplica nada.
                 */
                //$this->applyCreditNoteInternalReversal($creditNote);
                $this->applyTotalCreditNoteReversal(
                    $sale,
                    $creditNote
                );

                $creditNote->refresh();
                $sale->refresh();

                /*
                 * ============================================================
                 * NOTA DE CRÉDITO TOTAL
                 * ============================================================
                 */
                if ($creditNote->credit_note_type === 'total') {

                    $sale->state_annulled = 1;
                    $sale->annulment_type = 'credit_note';
                    $sale->annulment_status = 'accepted';
                    $sale->credit_note_status = 'total';
                    $sale->annulment_reason = 'Venta anulada mediante Nota de Crédito';
                    $sale->annulment_requested_at = $sale->annulment_requested_at ?: now();
                    $sale->annulment_requested_by = $sale->annulment_requested_by ?: auth()->id();
                    $sale->annulment_accepted_at = $sale->annulment_accepted_at ?: now();
                    $sale->annulled_by = $sale->annulled_by ?: auth()->id();
                    $sale->save();

                    DB::commit();

                    return response()->json([
                        'message' => 'La Nota de Crédito fue aceptada por SUNAT. La venta fue marcada como anulada definitivamente.',
                        'credit_note_status' => 'accepted',
                        'credit_note_type' => 'total',
                        'internal_reversal_status' => $creditNote->internal_reversal_status,
                    ], 200);
                }

                /*
                 * ============================================================
                 * NOTA DE CRÉDITO PARCIAL
                 * ============================================================
                 */
                if ($creditNote->credit_note_type === 'partial') {

                    $sale->credit_note_status = 'partial';
                    $sale->save();

                    DB::commit();

                    return response()->json([
                        'message' => 'La Nota de Crédito parcial fue aceptada por SUNAT. La reversión operativa ya fue aplicada.',
                        'credit_note_status' => 'accepted',
                        'credit_note_type' => 'partial',
                        'internal_reversal_status' => $creditNote->internal_reversal_status,
                        'cash_refund_status' => $creditNote->cash_refund_status,
                    ], 200);
                }
            }

            if ($creditNote->status === 'rejected') {
                DB::commit();

                return response()->json([
                    'message' =>
                        $creditNote->sunat_message
                            ?: 'La Nota de Crédito fue rechazada por SUNAT.',

                    'credit_note_status' => 'rejected',

                    'credit_note_type' =>
                        $creditNote->credit_note_type,

                    'sunat_responsecode' =>
                        $creditNote->sunat_responsecode,

                    'sunat_message' =>
                        $creditNote->sunat_message,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'message' => 'La Nota de Crédito todavía está pendiente de aceptación por SUNAT.',
                'credit_note_status' => 'pending',
                'credit_note_type' => $creditNote->credit_note_type,
                'internal_reversal_status' => $creditNote->internal_reversal_status,
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

    public function getCreditNotePartialData($id)
    {
        try {
            $scoped = app(\App\Services\Sales\SaleListAccess::class)->sale($id);
            $settlement = app(\App\Services\Sales\PartialCreditSettlement::class);
            if ($settlement->pending($scoped)->exists()) { throw new \DomainException('Consulte primero la nota de crédito pendiente.'); }

            $sale = Sale::with([
                'details.material',
                'details.stockItem',
                'creditNotes.details',
            ])->find($id);

            if (!$sale) {
                return response()->json([
                    'message' => 'Venta no encontrada.',
                ], 422);
            }

            if ((int) $sale->state_annulled === 1) {
                return response()->json([
                    'message' => 'La venta ya está anulada. No se puede generar Nota de Crédito parcial.',
                ], 422);
            }

            if (in_array($sale->annulment_status, ['pending', 'waiting_sunat_process', 'accepted'], true) || $sale->internal_reversal_status === 'reversed') {
                throw new \DomainException('La venta tiene una anulación en curso o aplicada. No puede iniciar una devolución parcial.');
            }
            if (!$sale->hasElectronicDocument()) {
                return response()->json([
                    'message' => 'La venta no tiene comprobante electrónico válido.',
                ], 422);
            }

            /*
             * Todos los OutputDetail de la venta que tengan item_id.
             * Se agrupan por sale_detail_id para evitar consultas repetidas.
             */
            $saleDetailIds = $sale->details->pluck('id')->all();

            $itemableOutputDetails = OutputDetail::whereIn(
                'sale_detail_id',
                $saleDetailIds
            )
                ->whereNotNull('item_id')
                ->where('item_id', '!=', 0)
                ->with([
                    'items.stockItem',
                    'items.stockLot',
                ])
                ->get()
                ->groupBy('sale_detail_id');

            /*
             * Items que ya están comprometidos por una NC pendiente
             * o que ya fueron devueltos mediante una NC aceptada.
             */
            $unavailableItemIds = CreditNoteDetailItem::whereHas(
                'creditNoteDetail.creditNote',
                function ($query) use ($sale) {
                    $query->where('sale_id', $sale->id)
                        ->whereIn('status', ['pending', 'accepted']);
                }
            )
                ->pluck('item_id')
                ->map(function ($itemId) {
                    return (int) $itemId;
                })
                ->unique()
                ->values()
                ->all();

            $items = [];

            foreach ($sale->details as $detail) {
                $soldQty = (float) $detail->quantity;

                /*
                 * Incluimos pending y accepted porque una NC pendiente
                 * ya tiene cantidades reservadas para devolución.
                 */
                $creditedQty = CreditNoteDetail::where(
                    'sale_detail_id',
                    $detail->id
                )
                    ->whereHas('creditNote', function ($query) {
                        $query->whereIn('status', ['pending', 'accepted']);
                    })
                    ->sum('quantity');

                $availableQty = $soldQty - (float) $creditedQty;

                if ($availableQty <= 0) {
                    continue;
                }

                if (empty($detail->material_id)) {
                    $description = strtoupper(
                        $detail->description ?: 'Servicio'
                    );
                } else {
                    if (
                        !empty($detail->stock_item_id) &&
                        $detail->stockItem
                    ) {
                        $description = $detail->stockItem->display_name;

                    } elseif ($detail->material) {
                        $description = $detail->material->full_name;

                    } else {
                        $description = 'Material ' . $detail->material_id;
                    }
                }

                /*
                 * OutputDetails itemeables correspondientes exclusivamente
                 * a este detalle de venta.
                 */
                $detailOutputItems = $itemableOutputDetails->get(
                    $detail->id,
                    collect()
                );

                $isItemable = $detailOutputItems->isNotEmpty();

                $availableItems = [];

                if ($isItemable) {
                    foreach ($detailOutputItems as $outputDetail) {
                        $itemId = (int) $outputDetail->item_id;

                        /*
                         * No mostrar items ya seleccionados en otra
                         * Nota de Crédito pendiente o aceptada.
                         */
                        if (in_array($itemId, $unavailableItemIds, true)) {
                            continue;
                        }

                        $item = $outputDetail->items;

                        if (!$item) {
                            continue;
                        }

                        $itemDescription = $item->code
                            ?: 'ITEM #' . $item->id;

                        /*
                         * Puedes ampliar este texto posteriormente con IMEI,
                         * serie, medidas u otros datos propios del Item.
                         */
                        if ($item->stockItem) {
                            $itemDescription .= ' - '
                                . $item->stockItem->display_name;
                        }

                        $availableItems[] = [
                            'item_id' => $item->id,
                            'output_detail_id' => $outputDetail->id,
                            'stock_lot_id' => $outputDetail->stock_lot_id,
                            'code' => $item->code,
                            'description' => $itemDescription,
                        ];
                    }
                }

                /*
                 * En un producto itemeable, la disponibilidad real también
                 * debe estar limitada por la cantidad de items seleccionables.
                 */
                if ($isItemable) {
                    $availableQty = min(
                        $availableQty,
                        count($availableItems)
                    );
                }

                if ($availableQty <= 0) {
                    continue;
                }

                $price = $this->partialCreditUnitPrice($detail);
                $valorUnitario = (float) $detail->valor_unitario;

                $items[] = [
                    'sale_detail_id' => $detail->id,
                    'description' => $description,
                    'sold_quantity' => $soldQty,
                    'credited_quantity' => (float) $creditedQty,
                    'available_quantity' => $availableQty,
                    'price' => $price,
                    'original_total_cents' => $settlement::cents((float) $detail->total - (float) $detail->discount),
                    'credited_total_cents' => $settlement::cents(CreditNoteDetail::where('sale_detail_id', $detail->id)->whereHas('creditNote', function ($q) { $q->whereIn('status', ['pending', 'accepted']); })->sum('total')),
                    'valor_unitario' => $valorUnitario,
                    'percentage_tax' => (float) $detail->percentage_tax,
                    'max_total' => round($availableQty * $price, 2),

                    /*
                     * Nuevos campos para manejar itemeables.
                     */
                    'is_itemable' => $isItemable,
                    'available_items' => $availableItems,
                ];
            }

            $financial = $settlement->snapshot($sale);
            if (!empty($financial['reconciliation_required'])) { throw new \DomainException('Los cobros o devoluciones históricos de esta venta requieren conciliación antes de emitir la nota.'); }
            return response()->json([
                'sale_id' => $sale->id,
                'code' => 'VENTA - ' . $sale->id,
                'type_document' => $sale->type_document,
                'serie_sunat' => $sale->serie_sunat,
                'numero' => $sale->numero,
                'cliente' => $sale->nombre_cliente,
                'total' => (float) $sale->importe_total,
                'items' => $items,
                'financial' => $financial,
                'financial_token' => hash('sha256', json_encode($financial)),
                'refund_channels' => $settlement->channels($sale),
                'partial_request_key' => (string) \Illuminate\Support\Str::uuid(),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generarNotaCreditoParcial(Request $request, $id)
    {
        $durable = false;
        $creditNote = null;
        $request->validate([
            'financial_token' => 'required|string|size:64',
            'partial_request_key' => 'required|uuid', 'items' => 'required|array|min:1',
            'items.*.sale_detail_id' => 'required|integer|min:1', 'items.*.quantity' => 'required|numeric|min:0.000001',
            'items.*.selected_item_ids' => 'nullable|array', 'items.*.selected_item_ids.*' => 'integer|min:1',
            'refund_cash_box_id' => 'nullable|integer|min:1', 'refund_subtype_id' => 'nullable|integer|min:1',
        ]);
        app(\App\Services\Sales\SaleListAccess::class)->sale($id);
        $settlement = app(\App\Services\Sales\PartialCreditSettlement::class);
        $payloadHash = hash('sha256', json_encode($request->only(['items', 'refund_cash_box_id', 'refund_subtype_id'])));
        DB::beginTransaction();

        try {
            $sale = Sale::with([
                'details.material',
                'details.stockItem',
            ])
                ->lockForUpdate()
                ->find($id);

            if (!$sale) {
                throw new \DomainException('Venta no encontrada.');
            }

            if ((int) $sale->state_annulled === 1) {
                throw new \DomainException('La venta ya está anulada.');
            }

            if (in_array($sale->annulment_status, ['pending', 'waiting_sunat_process', 'accepted'], true) || $sale->internal_reversal_status === 'reversed') {
                throw new \DomainException('La venta tiene una anulación en curso o aplicada. No puede iniciar una devolución parcial.');
            }
            if (!$sale->hasElectronicDocument()) {
                throw new \DomainException('La venta no tiene comprobante electrónico válido.');
            }

            $existing = $sale->creditNotes()->where('partial_request_key', $request->partial_request_key)->first();
            if ($existing) {
                if (!hash_equals((string) $existing->partial_request_hash, $payloadHash)) {
                    throw new \DomainException('Esta solicitud ya fue utilizada con otros datos. Recargue el listado.');
                }
                DB::commit();
                return response()->json(['message' => 'La solicitud ya está registrada como NC #' . $existing->id . '. Consulte su estado; no se realizó otro envío.',
                    'credit_note_status' => $existing->status], 200);
            }
            $financial = $settlement->snapshot($sale);
            if (!hash_equals(hash('sha256', json_encode($financial)), $request->financial_token)) {
                throw new \DomainException('Cambió la deuda o los cobros desde que abrió el modal. Ciérrelo y vuelva a abrirlo para revisar los importes.');
            }
            if ($settlement->pending($sale)->exists()) {
                throw new \DomainException('Consulte la nota de crédito pendiente antes de iniciar otra devolución.');
            }
            if ($sale->creditNotes()->where('status', 'rejected')->where('internal_reversal_status', 'reversed')->exists()) {
                throw new \DomainException('Existe una nota rechazada con movimientos aplicados. Requiere conciliación.');
            }
            $itemsRequest = $request->input('items', []);

            if (empty($itemsRequest)) {
                throw new \DomainException('Debe enviar al menos un producto.');
            }

            /*
             * No permitir que el mismo detalle llegue repetido
             * dentro del mismo request.
             */
            $requestedSaleDetailIds = collect($itemsRequest)
                ->pluck('sale_detail_id')
                ->filter()
                ->map(function ($value) {
                    return (int) $value;
                });

            if (
                $requestedSaleDetailIds->count() !==
                $requestedSaleDetailIds->unique()->count()
            ) {
                throw new \DomainException(
                    'Existen productos repetidos en la solicitud de devolución.'
                );
            }

            $creditNote = CreditNote::create([
                'sale_id' => $sale->id,
                'type_document' => '07',
                'reason_code' => '07',
                'settlement_version' => 1,
                'partial_request_key' => $request->partial_request_key,
                'partial_request_hash' => $payloadHash,
                'generation_key' => 'NC-PARCIAL-' . $sale->id . '-' . $request->partial_request_key,
                'reason_description' => $request->input(
                    'reason_description',
                    'Devolución parcial'
                ),
                'credit_note_type' => 'partial',
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            $totalGravada = 0;
            $totalExonerada = 0;
            $totalInafecta = 0;
            $totalIgv = 0;
            $total = 0;
            $validDetailsCreated = 0;

            foreach ($itemsRequest as $itemReq) {
                $saleDetailId = isset($itemReq['sale_detail_id'])
                    ? (int) $itemReq['sale_detail_id']
                    : null;

                if (!preg_match('/^\d{1,8}(?:\.\d{1,4})?$/D', (string) ($itemReq['quantity'] ?? ''))) {
                    throw new \DomainException('La cantidad excede la precisión admitida para una devolución. Use hasta cuatro decimales.');
                }
                $qtyToCredit = (float) ($itemReq['quantity'] ?? 0);
                if (!is_finite($qtyToCredit)) { throw new \DomainException('Cantidad inválida.'); }

                $selectedItemIds = collect(
                    $itemReq['selected_item_ids'] ?? []
                )
                    ->map(function ($itemId) {
                        return (int) $itemId;
                    })
                    ->filter(function ($itemId) {
                        return $itemId > 0;
                    })
                    ->values();

                if ($qtyToCredit <= 0) {
                    continue;
                }

                $detail = $sale->details->firstWhere(
                    'id',
                    $saleDetailId
                );

                if (!$detail) {
                    throw new \DomainException(
                        'Detalle de venta inválido.'
                    );
                }

                if ((int) $detail->tenant_id !== (int) $sale->tenant_id || (int) $detail->company_id !== (int) $sale->company_id) {
                    throw new \DomainException('El producto no pertenece al contexto de la venta.');
                }
                $soldQty = (float) $detail->quantity;

                /*
                 * Una NC pendiente ya compromete la cantidad,
                 * por eso se toma junto con las aceptadas.
                 */
                $creditedQty = CreditNoteDetail::where(
                    'sale_detail_id',
                    $detail->id
                )
                    ->whereHas('creditNote', function ($query) {
                        $query->whereIn(
                            'status',
                            ['pending', 'accepted']
                        );
                    })
                    ->sum('quantity');

                $availableQty =
                    $soldQty - (float) $creditedQty;

                if ($qtyToCredit > $availableQty) {
                    throw new \DomainException(
                        'La cantidad a devolver supera la cantidad disponible para '
                        . ($detail->description ?: 'el detalle #' . $detail->id)
                        . '.'
                    );
                }

                /*
                 * Verificar si este detalle de venta fue itemeable.
                 */
                $itemableOutputDetailsQuery = OutputDetail::where(
                    'sale_detail_id',
                    $detail->id
                )
                    ->whereNotNull('item_id')
                    ->where('item_id', '!=', 0);

                $isItemable = $itemableOutputDetailsQuery->exists();

                if ($isItemable) {
                    /*
                     * Los productos itemeables solo admiten unidades enteras.
                     */
                    if (
                        $qtyToCredit != floor($qtyToCredit)
                    ) {
                        throw new \DomainException(
                            'La cantidad de un producto itemeable debe ser entera.'
                        );
                    }

                    if (
                        $selectedItemIds->count() !==
                        $selectedItemIds->unique()->count()
                    ) {
                        throw new \DomainException(
                            'La selección contiene items repetidos.'
                        );
                    }

                    if (
                        $selectedItemIds->count() !==
                        (int) $qtyToCredit
                    ) {
                        throw new \DomainException(
                            'Debe seleccionar exactamente '
                            . (int) $qtyToCredit
                            . ' item(s) para '
                            . ($detail->description ?: 'el producto seleccionado')
                            . '.'
                        );
                    }

                    /*
                     * Buscar exclusivamente los OutputDetail de esta venta,
                     * este detalle y los items seleccionados.
                     */
                    $selectedOutputDetails = OutputDetail::where(
                        'sale_detail_id',
                        $detail->id
                    )
                        ->whereIn(
                            'item_id',
                            $selectedItemIds->all()
                        )
                        ->lockForUpdate()
                        ->get();

                    if (
                        $selectedOutputDetails->count() !==
                        $selectedItemIds->count()
                    ) {
                        throw new \DomainException(
                            'Uno o más items seleccionados no pertenecen '
                            . 'al producto o a la venta.'
                        );
                    }

                    /*
                     * Verificar que esos items no estén comprometidos
                     * en otra NC pendiente o aceptada de esta venta.
                     */
                    $alreadyUsedItemIds = CreditNoteDetailItem::whereIn(
                        'item_id',
                        $selectedItemIds->all()
                    )
                        ->whereHas(
                            'creditNoteDetail.creditNote',
                            function ($query) use ($sale) {
                                $query->where(
                                    'sale_id',
                                    $sale->id
                                )
                                    ->whereIn(
                                        'status',
                                        ['pending', 'accepted']
                                    );
                            }
                        )
                        ->pluck('item_id')
                        ->all();

                    if (!empty($alreadyUsedItemIds)) {
                        throw new \DomainException(
                            'Uno o más items seleccionados ya están '
                            . 'incluidos en otra Nota de Crédito.'
                        );
                    }
                } else {
                    /*
                     * Un producto normal no debe recibir IDs de items.
                     */
                    if ($selectedItemIds->isNotEmpty()) {
                        throw new \DomainException(
                            'Se enviaron items para un producto no itemeable.'
                        );
                    }

                    $selectedOutputDetails = collect();
                }

                $price = $this->partialCreditUnitPrice($detail);
                $ratio = (float) $detail->price > 0 ? (float) $detail->valor_unitario / (float) $detail->price : 0;
                $valorUnitario = $price * $ratio;

                $lineSubtotal =
                    $valorUnitario * $qtyToCredit;

                $creditedAmount = CreditNoteDetail::where('sale_detail_id', $detail->id)->whereHas('creditNote', function ($q) {
                    $q->whereIn('status', ['pending', 'accepted']);
                })->sum('total');
                $lineCents = (int) round($settlement::cents((float) $detail->total - (float) $detail->discount)
                    * ((float) $creditedQty + $qtyToCredit) / $soldQty) - $settlement::cents($creditedAmount);
                if ($lineCents <= 0) { throw new \DomainException('La cantidad seleccionada no genera un importe monetario válido.'); }
                $lineTotal = $lineCents / 100;
                $price = $lineTotal / $qtyToCredit;
                $valorUnitario = $price * $ratio;
                $lineSubtotal = $valorUnitario * $qtyToCredit;

                $lineIgv =
                    round($lineTotal, 2) - round($lineSubtotal, 2);

                $description = $detail->description ?: '';

                if (empty($description)) {
                    if (
                        !empty($detail->stock_item_id) &&
                        $detail->stockItem
                    ) {
                        $description =
                            $detail->stockItem->display_name;

                    } elseif ($detail->material) {
                        $description =
                            $detail->material->full_name;

                    } else {
                        $description =
                            'Detalle ' . $detail->id;
                    }
                }

                $creditNoteDetail = CreditNoteDetail::create([
                    'credit_note_id' => $creditNote->id,
                    'sale_detail_id' => $detail->id,
                    'description' => $description,
                    'quantity' => $qtyToCredit,
                    'price' => $price,
                    'valor_unitario' => $valorUnitario,
                    'subtotal' => round($lineSubtotal, 2),
                    'igv' => round($lineIgv, 2),
                    'total' => round($lineTotal, 2),
                ]);

                /*
                 * Guardar cada unidad itemeable seleccionada.
                 */
                if ($isItemable) {
                    foreach ($selectedOutputDetails as $outputDetail) {
                        $item = Item::where(
                            'id',
                            $outputDetail->item_id
                        )
                            ->lockForUpdate()
                            ->first();

                        if (!$item) {
                            throw new \DomainException(
                                'No se encontró uno de los items seleccionados.'
                            );
                        }

                        CreditNoteDetailItem::create([
                            'credit_note_detail_id' => $creditNoteDetail->id,
                            'item_id' => $item->id,
                            'output_detail_id' => $outputDetail->id,
                            'stock_lot_id' => $outputDetail->stock_lot_id,
                            'quantity' => 1,
                            'item_code' => $item->code,
                            'item_description' =>
                                $item->code
                                    ?: 'ITEM #' . $item->id,
                        ]);
                    }
                }

                $taxCode = $this->partialCreditTaxCode($detail);
                if ($taxCode === 1) { $totalGravada += round($lineSubtotal, 2); }
                elseif ($taxCode === 8) { $totalExonerada += round($lineSubtotal, 2); }
                else { $totalInafecta += round($lineSubtotal, 2); }
                $totalIgv += round($lineIgv, 2);
                $total += round($lineTotal, 2);
                $validDetailsCreated++;
            }

            if ($validDetailsCreated <= 0 || $total <= 0) {
                throw new \DomainException(
                    'El total de la Nota de Crédito debe ser mayor a cero.'
                );
            }

            $creditNote->update([
                'op_gravada' => round($totalGravada, 2),
                'op_exonerada' => round($totalExonerada, 2),
                'op_inafecta' => round($totalInafecta, 2),
                'igv' => round($totalIgv, 2),
                'importe_total' => round($total, 2),
            ]);

            /*
             * Recargar detalles e items antes de generar/persistir.
             */
            $creditNote->load([
                'details.items',
                'details.saleDetail',
            ]);

            $plan = $settlement->plan($financial, $settlement::cents($creditNote->importe_total));
            $creditNote->forceFill($plan);
            if ($settlement::cents($plan['cash_refund_amount']) > 0) {
                $settlement->destination($sale, $request->refund_cash_box_id, $request->refund_subtype_id);
                $creditNote->refund_cash_box_id = $request->refund_cash_box_id;
                $creditNote->refund_subtype_id = $request->refund_subtype_id ?: null;
            }
            $creditNote->save();
            // Validate the fiscal payload and company configuration before reserving a send.
            $this->buildNubefactCreditNotePartialData($sale, $creditNote);
            // Validate inventory provenance before contacting the provider, then roll back the rehearsal.
            DB::beginTransaction();
            try { $this->reverseCreditNoteStockPartially($creditNote, true); }
            finally { DB::rollBack(); }
            DB::commit();
            $durable = true;

            // The reserved request survives timeouts: repeating this POST never emits another document.
            $result = $this->generarNotaCreditoParcialNubefact($sale, $creditNote);
            $this->persistNubefactCreditNoteResult($creditNote, $result);
            return $this->completePartialCreditNote($creditNote->id);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) { DB::rollBack(); }
            if ($durable) {
                \Illuminate\Support\Facades\Log::warning('Partial NC requires review', ['credit_note_id' => $creditNote->id, 'exception' => get_class($e)]);
                return response()->json(['message' => 'La NC #' . $creditNote->id . ' quedó registrada y requiere consultar su estado. No vuelva a emitirla. Si no tiene número confirmado, concilie el envío con Nubefact. Referencia: ' . $creditNote->generation_key,
                    'credit_note_id' => $creditNote->id, 'credit_note_status' => $creditNote->fresh()->status], 202);
            }
            if (!$e instanceof \DomainException && !$e instanceof \Illuminate\Validation\ValidationException) {
                \Illuminate\Support\Facades\Log::warning('Partial NC validation failure', ['sale_id' => $id, 'exception' => get_class($e)]);
            }
            return response()->json(['message' => $e instanceof \DomainException ? $e->getMessage() : 'No se pudo preparar la devolución. Revise las cantidades, el canal y su sesión abierta.'], 422);
        }
    }

    private function partialCreditTaxCode($detail): int
    {
        $saved = (int) $detail->tax_provider_code;
        if (in_array($saved, [1, 8, 9], true)) { return $saved; }
        // Older taxable sales retain the original pre-tax and tax-inclusive unit prices.
        if ((float) $detail->percentage_tax > 0 || ((float) $detail->price > 0 && (float) $detail->valor_unitario > 0 && (float) $detail->valor_unitario < (float) $detail->price)) { return 1; }
        throw new \DomainException('El detalle no conserva una identidad tributaria suficiente para emitir la nota. Requiere revisión.');
    }

    private function partialCreditUnitPrice($detail): float
    {
        if ((float) $detail->quantity <= 0) { throw new \DomainException('El detalle no tiene cantidad vendida válida.'); }
        return max(0, (float) $detail->total - (float) $detail->discount) / (float) $detail->quantity;
    }

    private function completePartialCreditNote($noteId)
    {
        DB::transaction(function () use ($noteId) {
            $reference = CreditNote::findOrFail($noteId);
            $sale = Sale::where('id', $reference->sale_id)->lockForUpdate()->firstOrFail();
            $note = CreditNote::where('sale_id', $sale->id)->lockForUpdate()->findOrFail($noteId);
            if ($note->status === 'accepted' && $note->serie && $note->numero && $note->nubefact_response && $note->internal_reversal_status !== 'reversed') {
                $this->applyCreditNoteInternalReversal($note);
            }
        }, 3);
        $note = CreditNote::findOrFail($noteId);
        $applied = $note->internal_reversal_status === 'reversed';
        $message = $note->status === 'rejected' ? 'La nota fue rechazada. No se aplicaron nuevos movimientos.'
            : ($applied ? 'Nota aceptada. Productos reingresados; deuda reducida en ' . number_format($note->debt_reduction_amount, 2) . ' y devolución de ' . number_format($note->cash_refund_amount, 2) . ' registrada.'
            : 'Nota pendiente de aceptación o de confirmación de sus datos. Todavía no se modificaron stock ni dinero. Consulte su estado. Referencia: ' . $note->generation_key);
        return response()->json(['message' => $message, 'credit_note_status' => $note->status,
            'internal_reversal_status' => $note->internal_reversal_status, 'cash_refund_amount' => $note->cash_refund_amount,
            'debt_reduction_amount' => $note->debt_reduction_amount], $note->status === 'rejected' ? 422 : 200);
    }

    private function consultPartialCreditNote($note)
    {
        try {
            if ($note->status !== 'accepted') {
                $result = $this->consultarNotaCreditoNubefact($note);
                $this->persistNubefactCreditNoteResult($note, $result);
            }
            return $this->completePartialCreditNote($note->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Partial NC consultation requires review', ['credit_note_id' => $note->id, 'exception' => get_class($e)]);
            return response()->json(['message' => ($e instanceof \DomainException ? $e->getMessage() . ' ' : '') . 'No se completó la consulta o la aplicación de la NC #' . $note->id . '. Se conserva el documento. Verifique una sesión propia abierta en el canal elegido y vuelva a consultar; no genere otra nota.'], 422);
        }
    }

    private function applyCreditNoteInternalReversal(CreditNote $creditNote): void
    {
        if ($creditNote->internal_reversal_status === 'reversed') {
            return;
        }

        $creditNote->loadMissing([
            'sale.details',
            'details.saleDetail',
        ]);

        $sale = $creditNote->sale;

        if (!$sale) {
            throw new \Exception('La Nota de Crédito no tiene venta asociada.');
        }

        /*
         * ============================================================
         * NOTA DE CRÉDITO TOTAL
         * ============================================================
         */
        if ($creditNote->credit_note_type === 'total') {

            $sale->annulment_type = 'credit_note';
            $sale->credit_note_status = 'total';
            $sale->annulment_reason = 'Venta anulada mediante Nota de Crédito';
            $sale->save();

            $this->reverseSaleInternally(
                $sale,
                'Nota de Crédito total enviada. Reversión interna aplicada.',
                false
            );
        }

        /*
         * ============================================================
         * NOTA DE CRÉDITO PARCIAL
         * ============================================================
         */
        if ($creditNote->credit_note_type === 'partial') {

            /*
             * Revertir stock parcial
             */
            $this->reverseCreditNoteStockPartially($creditNote);

            /*
             * Registrar devolución de dinero
             */
            $this->registerCreditNoteCashRefund($creditNote);

            /*
             * Marcar venta con NC parcial
             */
            $sale->credit_note_status = 'partial';
            $sale->save();
        }

        /*
         * ============================================================
         * MARCAR REVERSIÓN INTERNA
         * ============================================================
         */
        $creditNote->internal_reversal_status = 'reversed';
        $creditNote->internal_reversed_at = now();
        $creditNote->internal_reversed_by = auth()->id();
        $creditNote->save();
    }

    private function registerCreditNoteCashRefund(CreditNote $creditNote): void
    {
        if ((int) $creditNote->settlement_version === 1) {
            app(\App\Services\Sales\PartialCreditSettlement::class)->refund($creditNote);
            return;
        }

        if ($creditNote->cash_refund_status === 'refunded') {
            return;
        }

        $creditNote->loadMissing([
            'sale',
        ]);

        $sale = $creditNote->sale;

        if (!$sale) {
            throw new \Exception('La Nota de Crédito no tiene venta asociada.');
        }

        $amountToRefund = (float) $creditNote->importe_total;

        if ($amountToRefund <= 0) {
            throw new \Exception('El monto de devolución de la Nota de Crédito debe ser mayor a cero.');
        }

        /*
         * Tomamos como referencia el último movimiento de venta
         * para usar la misma caja/canal de pago.
         */
        $saleMovement = CashMovement::where('sale_id', $sale->id)
            ->where('type', 'sale')
            ->orderBy('id', 'desc')
            ->first();

        if (!$saleMovement) {
            throw new \Exception('No se encontró movimiento de caja de la venta para registrar la devolución.');
        }

        $cashMovement = CashMovement::create([
            'cash_register_id'    => $saleMovement->cash_register_id,
            'sale_id'             => $sale->id,
            'type'                => 'expense',
            'amount'              => $amountToRefund,
            'description'         => 'Devolución por Nota de Crédito parcial '
                . ($creditNote->serie ? $creditNote->serie . '-' . $creditNote->numero : '')
                . ' de venta #' . $sale->id,
            'regularize'          => $saleMovement->regularize,
            'cash_box_subtype_id' => $saleMovement->cash_box_subtype_id,
        ]);

        $cashRegister = CashRegister::find($saleMovement->cash_register_id);

        if ($cashRegister) {
            $cashRegister->current_balance -= $amountToRefund;
            $cashRegister->total_expenses += $amountToRefund;
            $cashRegister->save();
        }

        $creditNote->cash_refund_status = 'refunded';
        $creditNote->cash_refund_at = now();
        $creditNote->cash_refund_by = auth()->id();
        $creditNote->cash_movement_id = $cashMovement->id;
        $creditNote->save();
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

    public function getSalesErrorAdmin(Request $request, $pageNumber = 1)
    {
        $saleList = app(\App\Services\Sales\SaleListAccess::class);
        $saleList->context(true);
        $saleList->validateFilters($request);
        abort_unless(ctype_digit((string) $pageNumber) && (int) $pageNumber > 0, 422);
        $perPage = 10;

        $code = $request->input('code');
        $year = $request->input('year');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $customerId = $request->input('customer_id');
        $paymentStatus = $request->input('payment_status');
        $dispatchStatus = $request->input('dispatch_status');
        $cashBoxId = $request->input('cash_box_id');
        $cashBoxSubtypeId = $request->input('cash_box_subtype_id');
        $documentType = $request->input('document_type'); // 01 factura, 03 boleta

        $query = $saleList->visible(Sale::query(), true)->with(['quote.customer', 'worker.user'])
            ->where('state_annulled', 0)
            ->whereIn('type_document', ['01', '03'])
            ->where('sunat_status', 'Error')
            ->whereNull('sunat_error_discarded_at')
            ->orderBy('created_at', 'DESC');

        /*
         * ============================================================
         * FILTRO POR FECHAS
         * ============================================================
         */
        if ($startDate != "" && $endDate != "") {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFinal);
        }

        /*
         * ============================================================
         * PERMISOS POR USUARIO
         * ============================================================
         */
        if ($request->filled('created_by_id')) { $query->where('sales.created_by', (int) $request->input('created_by_id')); }

        /*
         * ============================================================
         * FILTROS GENERALES
         * ============================================================
         */
        if ($code != "") {
            $query->where('id', 'LIKE', '%' . $code . '%');
        }

        if ($year != "") {
            $query->whereYear('created_at', $year);
        }

        if ($documentType != "") {
            $query->where('type_document', $documentType);
        }

        /*
         * ============================================================
         * FILTRO CLIENTE
         * ============================================================
         */
        if ($customerId != "") {
            if ($customerId === 'venta_directa') {
                $query->whereNull('quote_id');
            } elseif ($customerId === 'cotizacion_sin_cliente') {
                $query->whereHas('quote', function ($q) {
                    $q->whereNull('customer_id');
                });
            } else {
                $query->where(function ($customerQuery) use ($customerId) {
                    $customerQuery->where('sales.customer_id', $customerId)->orWhereHas('quote', function ($q) use ($customerId) { $q->where('customer_id', $customerId); });
                });
            }
        }

        /*
         * ============================================================
         * FILTRO ESTADO DESPACHO
         * ============================================================
         */
        if ($dispatchStatus != "") {
            $query->where('dispatch_status', $dispatchStatus);
        }

        /*
         * ============================================================
         * FILTRO MÉTODO DE PAGO
         * ============================================================
         */
        if ($cashBoxId != "") {
            if ($cashBoxId === 'pago_parcial') {
                $query->where('pagos_parciales_venta', 's');
            } else {
                $query->where('pagos_parciales_venta', 'n')
                    ->whereHas('cashMovements', function ($q) use ($cashBoxId, $cashBoxSubtypeId) {
                        $q->where('type', 'sale')
                            ->whereHas('cashRegister', function ($qr) use ($cashBoxId) {
                                $qr->where('cash_box_id', $cashBoxId);
                            });

                        if ($cashBoxSubtypeId != "") {
                            $q->where('cash_box_subtype_id', $cashBoxSubtypeId);
                        }
                    });
            }
        }

        /*
         * ============================================================
         * FILTRO CUMPLIMIENTO DE PAGO
         * ============================================================
         */
        if ($paymentStatus != "") {
            if ($paymentStatus === 'verde') {
                $query->where(function ($q) {
                    $q->where('pagos_parciales_venta', 'n')
                        ->orWhereRaw("
                        (
                            SELECT COALESCE(SUM(spp.amount), 0)
                            FROM sale_partial_payments spp
                            WHERE spp.sale_id = sales.id
                            AND spp.state = 1
                        ) >= sales.importe_total
                    ");
                });
            }

            if ($paymentStatus === 'naranja') {
                $query->where('pagos_parciales_venta', 's')
                    ->whereRaw("
                    (
                        SELECT COALESCE(SUM(spp.amount), 0)
                        FROM sale_partial_payments spp
                        WHERE spp.sale_id = sales.id
                        AND spp.state = 1
                    ) >= (sales.importe_total * 0.5)
                ")
                    ->whereRaw("
                    (
                        SELECT COALESCE(SUM(spp.amount), 0)
                        FROM sale_partial_payments spp
                        WHERE spp.sale_id = sales.id
                        AND spp.state = 1
                    ) < sales.importe_total
                ");
            }

            if ($paymentStatus === 'rojo') {
                $query->where('pagos_parciales_venta', 's')
                    ->whereRaw("
                    (
                        SELECT COALESCE(SUM(spp.amount), 0)
                        FROM sale_partial_payments spp
                        WHERE spp.sale_id = sales.id
                        AND spp.state = 1
                    ) < (sales.importe_total * 0.5)
                ");
            }
        }

        /*
         * ============================================================
         * PAGINACIÓN
         * ============================================================
         */
        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = $totalFilteredRecords > 0 ? ($pageNumber - 1) * $perPage + 1 : 0;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $sales = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $arraySales = [];

        foreach ($sales as $sale) {

            /*
             * ============================================================
             * TIPO COMPROBANTE
             * ============================================================
             */
            $tipoComprobante = null;
            $tipoComprobanteLabel = 'SIN COMPROBANTE';

            if ($sale->type_document === '01') {
                $tipoComprobante = 'factura';
                $tipoComprobanteLabel = 'FACTURA';
            } elseif ($sale->type_document === '03') {
                $tipoComprobante = 'boleta';
                $tipoComprobanteLabel = 'BOLETA';
            }

            /*
             * ============================================================
             * TIPO DOCUMENTO CLIENTE
             * ============================================================
             */
            $tipoDocumentoCliente = null;

            if ($sale->tipo_documento_cliente == 1) {
                $tipoDocumentoCliente = 'dni';
            } elseif ($sale->tipo_documento_cliente == 6) {
                $tipoDocumentoCliente = 'ruc';
            }

            /*
             * ============================================================
             * URL IMPRESIÓN
             * ============================================================
             */
            $printUrl = route('puntoVenta.print', $sale->id);

            if (!empty($sale->pdf_path)) {
                $printUrl = route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf']);
            }

            /*
             * ============================================================
             * MÉTODO DE PAGO
             * ============================================================
             */
            $paymentMovement = CashMovement::where('sale_id', $sale->id)
                ->where('type', 'sale')
                ->with(['cashRegister.cashBox', 'cashBoxSubtype'])
                ->orderBy('id', 'desc')
                ->first();

            $tipoPagoLabel = $sale->pagos_parciales_venta === 's'
                ? 'PAGO PARCIAL'
                : 'SIN MOVIMIENTO';

            if ($sale->pagos_parciales_venta === 'n' && $paymentMovement && $paymentMovement->cashRegister && $paymentMovement->cashRegister->cashBox) {
                $cashBox = $paymentMovement->cashRegister->cashBox;

                if ($cashBox->type === 'cash') {
                    $tipoPagoLabel = $cashBox->name;
                } else {
                    $tipoPagoLabel = $paymentMovement->cashBoxSubtype
                        ? $cashBox->name . ' - ' . $paymentMovement->cashBoxSubtype->name
                        : $cashBox->name;
                }
            }

            /*
             * ============================================================
             * CLIENTE
             * ============================================================
             */
            $nombreCliente = 'VENTA DIRECTA';

            if ($sale->quote) {
                if ($sale->quote->customer) {
                    $nombreCliente = $sale->quote->customer->business_name;
                } else {
                    $nombreCliente = 'COTIZACION SIN CLIENTE';
                }
            }

            /*
             * ============================================================
             * PAGO PARCIAL
             * ============================================================
             */
            $totalVenta = (float) $sale->importe_total;
            $totalAbonado = null;
            $estadoPagoParcial = null;

            if ($sale->pagos_parciales_venta === 's') {
                $totalAbonado = SalePartialPayment::where('sale_id', $sale->id)
                    ->where('state', 1)
                    ->sum('amount');

                $porcentaje = $totalVenta > 0
                    ? (($totalAbonado / $totalVenta) * 100)
                    : 0;

                if ($porcentaje >= 100) {
                    $estadoPagoParcial = 'verde';
                } elseif ($porcentaje >= 50) {
                    $estadoPagoParcial = 'naranja';
                } else {
                    $estadoPagoParcial = 'rojo';
                }

                $totalTexto = number_format($totalAbonado, 2, '.', '') . ' / ' . number_format($totalVenta, 2, '.', '');
            } else {
                $totalTexto = number_format($totalVenta, 2, '.', '');
            }

            /*
             * ============================================================
             * ERROR SUNAT
             * ============================================================
             */
            $sunatMessage = $sale->sunat_message ?: 'Error no especificado.';

            $shortSunatMessage = mb_strlen($sunatMessage, 'UTF-8') > 180
                ? mb_substr($sunatMessage, 0, 180, 'UTF-8') . '...'
                : $sunatMessage;

            array_push($arraySales, [
                'id' => $sale->id,
                'created_by' => $sale->created_by,
                'created_by_name' => $sale->created_by_name ?: 'No registrado',
                'code' => 'VENTA - ' . $sale->id,
                'date' => ($sale->date_sale != null) ? $sale->formatted_sale_date : '',
                'created_at' => $sale->created_at ? Carbon::parse($sale->created_at)->format('d/m/Y h:i A') : '',

                'currency' => ($sale->currency == 'PEN') ? 'Soles' : 'Dólares',
                'total' => $totalTexto,
                'importe_total' => number_format((float) $sale->importe_total, 2, '.', ''),
                'total_abonado' => $totalAbonado,
                'estado_pago_parcial' => $estadoPagoParcial,
                'invoice_info' => app(\App\Services\Sales\ExistingSaleInvoice::class)->flags($sale),
                'total_credit_note_info' => app(\App\Services\Sales\TotalCreditNoteService::class)->flags($sale),

                'tipo_pago' => mb_strtoupper($this->cleanUtf8($tipoPagoLabel), 'UTF-8'),
                'dispatch_status' => $sale->dispatch_status ?? 'despachado',

                'nombre_cliente' => $this->cleanUtf8($nombreCliente),
                'tipo_documento_cliente' => $tipoDocumentoCliente,
                'numero_documento_cliente' => $this->cleanUtf8($sale->numero_documento_cliente),
                'direccion_cliente' => $this->cleanUtf8($sale->direccion_cliente),
                'email_cliente' => $this->cleanUtf8($sale->email_cliente),

                'type_document' => $sale->type_document,
                'tipo_comprobante' => $tipoComprobante,
                'tipo_comprobante_label' => $tipoComprobanteLabel,
                'serie_sunat' => $sale->serie_sunat,
                'numero' => $sale->numero,

                'sunat_status' => $sale->sunat_status,
                'sunat_message' => $this->cleanUtf8($sunatMessage),
                'sunat_message_short' => $this->cleanUtf8($shortSunatMessage),

                'print_url' => $this->cleanUtf8($printUrl),
                'print_label' => !empty($sale->pdf_path) ? 'Ver PDF' : 'Ver Ticket',

                'can_retry_invoice' => true,
                'can_discard_error' => true,


            ]);
        }

        $pagination = [
            'currentPage' => (int) $pageNumber,
            'totalPages' => (int) $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords,
        ];

        return [
            'data' => $arraySales,
            'pagination' => $pagination,
        ];
    }

    public function listarErrors()
    {
        return view('puntoVenta.listError', app(\App\Services\Sales\SaleListOptions::class)->data(true));
    }

    public function discardSunatError(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::lockForUpdate()->find($id);

            if (!$sale) {
                return response()->json([
                    'message' => 'Venta no encontrada.',
                ], 422);
            }

            if ($sale->requiresEmissionReview()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sale_id' =>
                        'La emisión requiere conciliación antes de emitir, '
                        . 'anular o descartar el comprobante.',
                ]);
            }

            if ((int) $sale->state_annulled === 1) {
                return response()->json([
                    'message' => 'No se puede descartar el error porque la venta ya está anulada.',
                ], 422);
            }

            if (!in_array($sale->type_document, ['01', '03'], true)) {
                return response()->json([
                    'message' => 'La venta no tiene factura o boleta asociada.',
                ], 422);
            }

            if ($sale->sunat_status !== 'Error') {
                return response()->json([
                    'message' => 'Esta venta no se encuentra en estado Error SUNAT.',
                ], 422);
            }

            if (!empty($sale->sunat_error_discarded_at)) {
                return response()->json([
                    'message' => 'Este error ya fue descartado anteriormente.',
                ], 422);
            }

            $reason = trim((string) $request->input('reason', ''));

            if ($reason === '') {
                return response()->json([
                    'message' => 'Debe ingresar un motivo para descartar el error.',
                ], 422);
            }

            $sale->sunat_error_discarded_at = now();
            $sale->sunat_error_discarded_by = auth()->id();
            $sale->sunat_error_discard_reason = $reason;
            $sale->save();

            DB::commit();

            return response()->json([
                'message' => 'El comprobante con error fue descartado correctamente.',
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

    public function recuperarArchivosNubefact(Sale $sale)
    {

        if ($sale->requiresEmissionReview()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sale_id' =>
                    'La emisión requiere conciliación antes de emitir, '
                    . 'anular o descartar el comprobante.',
            ]);
        }

        /*
         * Solo permitimos recuperar archivos de boletas y facturas.
         */
        if (!in_array($sale->type_document, ['01', '03'])) {
            return response()->json([
                'message' =>
                    'Esta venta no tiene una boleta o factura electrónica.',
            ], 422);
        }

        /*
         * No intentamos recuperar archivos cuando la emisión inicial
         * fue registrada como fallida.
         */
        if ($sale->sunat_status === 'Error') {
            return response()->json([
                'message' =>
                    'El comprobante tiene un error de emisión. Primero debe resolverse el error tributario.',
            ], 422);
        }

        /*
         * Para consultar un comprobante existente necesitamos
         * necesariamente su serie y número.
         */
        if (
            empty($sale->serie_sunat) ||
            empty($sale->numero)
        ) {
            return response()->json([
                'message' =>
                    'La venta no tiene serie o número SUNAT para consultar el comprobante.',
            ], 422);
        }

        try {
            /*
             * 1. Consultar el comprobante existente.
             *
             * Esta función no vuelve a emitirlo.
             */
            $nubefactResult =
                $this->consultarComprobanteNubefact($sale);

            /*
             * 2. Descargar y validar PDF, XML y CDR.
             *
             * Esta función ya utiliza:
             * - tres intentos;
             * - validación de PDF;
             * - validación de XML;
             * - validación de ZIP;
             * - archivos temporales;
             * - logs.
             */
            $resultadoArchivos =
                $this->persistNubefactFilesAndUpdateSale(
                    $sale,
                    $nubefactResult
                );

            /*
             * Recargar los campos actualizados:
             * pdf_path, xml_path y cdr_path.
             */
            $sale->refresh();

            $archivosRecuperados = [];

            if (!empty($resultadoArchivos['pdf_descargado'])) {
                $archivosRecuperados[] = 'PDF';
            }

            if (!empty($resultadoArchivos['xml_descargado'])) {
                $archivosRecuperados[] = 'XML';
            }

            if (!empty($resultadoArchivos['cdr_descargado'])) {
                $archivosRecuperados[] = 'CDR';
            }

            /*
             * Ningún archivo pudo recuperarse.
             */
            if (empty($archivosRecuperados)) {
                Log::warning(
                    'No se recuperó ningún archivo de Nubefact',
                    [
                        'sale_id' => $sale->id,
                        'errores' =>
                            $resultadoArchivos['errores'] ?? [],
                    ]
                );

                return response()->json([
                    'message' =>
                        'Nubefact respondió, pero no fue posible descargar ningún archivo válido.',

                    'errors' =>
                        $resultadoArchivos['errores'] ?? [],
                ], 422);
            }

            /*
             * Algunos archivos se recuperaron, pero otros fallaron.
             */
            if (!empty($resultadoArchivos['errores'])) {
                Log::warning(
                    'Comprobante recuperado parcialmente',
                    [
                        'sale_id' => $sale->id,
                        'archivos_recuperados' =>
                            $archivosRecuperados,
                        'errores' =>
                            $resultadoArchivos['errores'],
                    ]
                );

                return response()->json([
                    'message' =>
                        'Se recuperaron: '
                        . implode(', ', $archivosRecuperados)
                        . '. Algunos archivos continúan pendientes.',

                    'partial' => true,

                    'files' => [
                        'pdf' => !empty(
                        $resultadoArchivos['pdf_descargado']
                        ),
                        'xml' => !empty(
                        $resultadoArchivos['xml_descargado']
                        ),
                        'cdr' => !empty(
                        $resultadoArchivos['cdr_descargado']
                        ),
                    ],

                    'errors' =>
                        $resultadoArchivos['errores'],

                    'pdf_url' => !empty($sale->pdf_path)
                        ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf'])
                        : null,
                ], 200);
            }

            /*
             * Recuperación completa.
             */
            return response()->json([
                'message' =>
                    'Los archivos del comprobante fueron recuperados correctamente.',

                'partial' => false,

                'files' => [
                    'pdf' => true,
                    'xml' => !empty($sale->xml_path),
                    'cdr' => !empty($sale->cdr_path),
                ],

                'pdf_url' => !empty($sale->pdf_path)
                    ? route('sales.list.document', ['id' => $sale->id, 'kind' => 'invoice_pdf'])
                    : null,
            ], 200);

        } catch (\Throwable $e) {
            Log::error(
                'Error recuperando archivos del comprobante',
                [
                    'sale_id' => $sale->id,
                    'serie' => $sale->serie_sunat,
                    'numero' => $sale->numero,
                    'error' => $e->getMessage(),
                ]
            );

            /*
             * No cambiamos sunat_status a Error.
             *
             * Este proceso no corresponde a una nueva emisión;
             * solamente intenta recuperar archivos.
             */
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function probarConsultaComprobanteNubefact($saleId)
    {
        /*
         * Este método es solo para ambiente local/desarrollo.
         * Evita que pueda ejecutarse accidentalmente en producción.
         */
        if (!app()->environment('local')) {
            return response()->json([
                'message' => 'Esta prueba solo está disponible en desarrollo.',
            ], 403);
        }

        $sale = Sale::find($saleId);

        if (!$sale) {
            return response()->json([
                'message' => 'No se encontró la venta indicada.',
            ], 404);
        }

        if (!in_array($sale->type_document, ['01', '03'])) {
            return response()->json([
                'message' => 'La venta no corresponde a una boleta o factura.',
                'sale_id' => $sale->id,
                'type_document' => $sale->type_document,
            ], 422);
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            return response()->json([
                'message' => 'La venta no tiene serie o número SUNAT.',
                'sale_id' => $sale->id,
                'serie_sunat' => $sale->serie_sunat,
                'numero' => $sale->numero,
            ], 422);
        }

        try {
            /*
             * Solo consulta.
             * No descarga ni guarda archivos.
             */
            $resultado = $this->consultarComprobanteNubefact($sale);

            Log::info('Prueba de consulta de comprobante Nubefact', [
                'sale_id' => $sale->id,
                'serie' => $sale->serie_sunat,
                'numero' => $sale->numero,
                'resultado' => $resultado,
            ]);

            return response()->json([
                'message' => 'Consulta realizada correctamente.',
                'venta' => [
                    'id' => $sale->id,
                    'tipo_documento' => $sale->type_document,
                    'serie' => $sale->serie_sunat,
                    'numero' => $sale->numero,
                    'estado_sunat' => $sale->sunat_status,
                ],
                'respuesta_nubefact' => $resultado,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error en prueba de consulta Nubefact', [
                'sale_id' => $sale->id,
                'serie' => $sale->serie_sunat,
                'numero' => $sale->numero,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'venta' => [
                    'id' => $sale->id,
                    'tipo_documento' => $sale->type_document,
                    'serie' => $sale->serie_sunat,
                    'numero' => $sale->numero,
                ],
            ], 422);
        }
    }

    private function applyTotalCreditNoteReversal(Sale $sale,CreditNote $creditNote) {
        /*
         * Idempotencia de la Nota de Crédito.
         */
        if (
            $creditNote->internal_reversal_status ===
            'reversed'
        ) {
            return;
        }

        if ((bool) $sale->free_sale) {
            /*
             * Venta libre:
             * no tocar inventario, Output ni OutputDetail.
             */
            $this->reverseFreeSaleFinancially(
                $sale,
                'Reversión financiera por Nota de Crédito total #' .
                $creditNote->id
            );

            $creditNote->internal_reversal_status =
                'reversed';

            $creditNote->internal_reversed_at = now();
            $creditNote->internal_reversed_by = auth()->id();
            $creditNote->save();

            return;
        }

        /*
         * Venta normal:
         * conserva la reversión actual de inventario y caja.
         */
        $this->applyCreditNoteInternalReversal(
            $creditNote
        );
    }
}
