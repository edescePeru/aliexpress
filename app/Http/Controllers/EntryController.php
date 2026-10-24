<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CompanyStockItem;
use App\DataGeneral;
use App\DetailEntry;
use App\Entry;
use App\EntryImage;
use App\Exports\EntriesReportExcelDownload;
use App\FollowMaterial;
use App\Http\Requests\StoreEntryPurchaseOrderRequest;
use App\Http\Requests\StoreEntryPurchaseRequest;
use App\Http\Requests\StoreOrderPurchaseRequest;
use App\Http\Requests\UpdateEntryPurchaseRequest;
use App\InventoryLevel;
use App\Item;
use App\Location;
use App\Material;
use App\MaterialOrder;
use App\MaterialVencimiento;
use App\Notification;
use App\NotificationUser;
use App\OrderPurchase;
use App\OrderPurchaseDetail;
use App\Output;
use App\OutputDetail;
use App\PaymentDeadline;
use App\Quote;
use App\Services\Inventory\EntryDeletionService;
use App\Services\Inventory\EntryWarehouseResolver;
use App\Services\InventoryCostService;
use App\Services\Purchases\NormalPurchaseReceiptSummary;
use App\Services\TipoCambioService;
use App\StockItem;
use App\StockLot;
use App\Supplier;
use App\SupplierCredit;
use App\Support\TenantContext;
use App\Typescrap;
use App\User;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Barryvdh\DomPDF\Facade as PDF;
use App\Services\SettingService;

class EntryController extends Controller
{
    protected $tipoCambioService;
    /** @var InventoryCostService */
    private $inventoryCostService;

    public function __construct(TipoCambioService $tipoCambioService, InventoryCostService $inventoryCostService)
    {
        $this->tipoCambioService = $tipoCambioService;
        $this->inventoryCostService = $inventoryCostService;
    }

    public function indexEntryPurchase()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('entry.index_entry_purchase', compact('permissions'));
    }

    public function getAllEntriesV2(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $year = $request->input('year');
        $invoice = $request->input('invoice');
        $guide = $request->input('guide');
        $supplier = $request->input('supplier');
        $order = $request->input('order');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $query = Entry::with('supplier')
                ->where(
                    'company_id',
                    TenantContext::companyId()
                )
                ->where('entry_type', 'Por compra')
                ->where('finance', false)
                ->orderBy('date_entry', 'desc');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Entry::with('supplier')
                ->where(
                    'company_id',
                    TenantContext::companyId()
                )
                ->where('entry_type', 'Por compra')
                ->where('finance', false)
                ->whereDate('date_entry', '>=', $fechaInicio)
                ->whereDate('date_entry', '<=', $fechaFinal)
                ->orderBy('date_entry', 'desc');
        }

        if ($year != "") {
            $query->whereYear('date_entry', $year);
        }

        if ($invoice != "") {
            $query->where('invoice', 'LIKE', '%'.$invoice.'%');

        }

        if ($guide != "") {
            $query->where('referral_guide', 'LIKE', '%'.$guide.'%');
        }

        if ($order != "") {
            $query->where('purchase_order', 'LIKE', '%'.$order.'%');

        }

        if ($supplier != "") {
            $query->whereHas('supplier', function ($query2) use ($supplier) {
                $query2->where('supplier_id', $supplier);
            });

        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $entries = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($query);

        $array = [];

        foreach ( $entries as $entry )
        {
            $file = "nn";
            if ( $entry->image != null ) {
                $string = substr($entry->image, -3);
                if( strtoupper($string) == 'PDF')
                {
                    $file = "pdf";
                } else {
                    $file = "img";
                }
            }

            $diferido = "";
            $diferidoText = "";
            if ( $entry->deferred_invoice === 'off' )
            {
                $diferido = 'off';
                $diferidoText = '<span class="badge bg-success">NO</span>';
            }else {
                $diferido = 'on';
                $diferidoText = '<span class="badge bg-warning">SI</span>';
            }


            array_push($array, [
                "id" => $entry->id,
                "guide" => $entry->referral_guide,
                "order" => $entry->purchase_order,
                "invoice" => $entry->invoice,
                "type" => $entry->entry_type,
                "supplier" =>
                    optional($entry->supplier)
                        ->business_name ?? '',
                "date_entry" => ($entry->date_entry == null || $entry->date_entry == "") ? '': $entry->date_entry->format('d/m/Y'),
                "diferido" => $diferido,
                "diferidoText" => $diferidoText,
                "file" => $file,
                "total" => $entry->total,
                "currency" => ($entry->currency_invoice == null || $entry->currency_invoice == "") ? '': $entry->currency_invoice,
                "image" => $entry->image
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

    public function listEntryPurchaseV2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Entry::query()
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->get();

        $arrayYears = $registros->pluck('date_entry')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arraySuppliers = Supplier::select(
            'id',
            'business_name'
        )->get()->toArray();

        return view('entry.index_entry_purchasev2', compact( 'permissions', 'arrayYears', 'arraySuppliers'));

    }

    public function indexEntryScraps()
    {
        return view('entry.index_entry_scrap');
    }

    public function createEntryPurchase()
    {
        $suppliers = Supplier::all();
        $warehouseOptions = app(EntryWarehouseResolver::class)
            ->optionsForCurrentContext();

        return view('entry.create_entry_purchaseV2', compact('suppliers', 'warehouseOptions'));
    }

    public function createEntryScrap()
    {
        return view('entry.create_entry_scrap');
    }

    public function storeEntryPurchaseOriginal(StoreEntryPurchaseRequest $request)
    {
        $begin = microtime(true);
        //dd($request);
        $validated = $request->validated();

        $fecha = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $precioCompra = 1;
        $precioVenta = 1;

        $tipoMoneda = ($request->has('currency_invoice')) ? 'USD':'PEN';
        if ( $tipoMoneda == 'USD' ) {
            $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
            $precioCompra = (float) $tipoCambioSunat->precioCompra;
            $precioVenta = (float) $tipoCambioSunat->precioVenta;
        }

        //dd($tipoCambioSunat);

        //$tipoCambioSunat = json_decode($response);
        $flag = DataGeneral::where('name', 'type_current')->first();

        if ( $request->get('purchase_order') != '' || $request->get('purchase_order') != null )
        {
            $order_purchase1 = OrderPurchase::where('code', $request->get('purchase_order'))->first();

            if ( isset($order_purchase1) )
            {
                return response()->json([
                    'message' => "No se encontró la orden de compra indicada"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            //dump($tipoCambioSunat->compra);
            $entry = Entry::create([
                'referral_guide' => $request->get('referral_guide'),
                'purchase_order' => $request->get('purchase_order'),
                'invoice' => $request->get('invoice'),
                'deferred_invoice' => ($request->has('deferred_invoice')) ? $request->get('deferred_invoice'):'off',
                'currency_invoice' => ($request->has('currency_invoice')) ? 'USD':'PEN',
                'supplier_id' => $request->get('supplier_id'),
                'entry_type' => $request->get('entry_type'),
                'date_entry' => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance' => false,
                /*'currency_compra' => (float) $tipoCambioSunat->compra,
                'currency_venta' => (float) $tipoCambioSunat->venta,*/
                'currency_compra' => $precioCompra,
                'currency_venta' => $precioVenta,
                'observation' => $request->get('observation'),
            ]);

            // TODO: Tratamiento de un archivo de forma tradicional
            if (!$request->file('image')) {
                $entry->image = 'no_image.png';
                $entry->save();
            } else {
                $path = public_path().'/images/entries/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();
                //$filename = $entry->id . '.' . $extension;
                if ( strtoupper($extension) != "PDF" )
                {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    //$request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf'.$entry->id . '.' .$extension;
                    $request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                }

            }

            if (!$request->file('imageOb')) {
                $entry->imageOb = 'no_image.png';
                $entry->save();
            } else {
                $path = public_path().'/images/entries/observations/';
                $image = $request->file('imageOb');
                $extension = $image->getClientOriginalExtension();
                if ( strtoupper($extension) != "PDF" )
                {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    //$request->file('image')->move($path, $filename);
                    $entry->imageOb = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf'.$entry->id . '.' .$extension;
                    $request->file('imageOb')->move($path, $filename);
                    $entry->imageOb = $filename;
                    $entry->save();
                }

            }

            $items = json_decode($request->get('items'));

            //dd($item->id);
            $materials_id = [];

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                array_push($materials_id, $items[$i]->id_material);
            }

            $counter = array_count_values($materials_id);
            //dd($counter);

            foreach ( $counter as $id_material => $count )
            {
                $material = Material::find($id_material);
                $material->stock_current = $material->stock_current + $count;
                $material->save();

                // TODO: ORDER_QUANTITY sera tomada de la orden de compra
                $detail_entry = DetailEntry::create([
                    'entry_id' => $entry->id,
                    'material_id' => $id_material,
                    'ordered_quantity' => $count,
                    'entered_quantity' => $count,
                    'date_vence' => ($request->get('date_vence') == "" || $request->get('date_vence') == null) ? null: Carbon::createFromFormat('d/m/Y', $request->get('date_vence'))
                ]);

                if ( $material->perecible == 's' )
                {
                    // TODO: Crear los MaterialVencimientos
                    MaterialVencimiento::create([
                        'material_id' => $id_material,
                        'fecha_vencimiento' => ($request->get('date_vence') == "" || $request->get('date_vence') == null) ? null: Carbon::createFromFormat('d/m/Y', $request->get('date_vence'))
                    ]);
                }

                // TODO: Revisamos si hay un material en seguimiento y creamos
                // TODO: la notificacion y cambiamos el estado
                $follows = FollowMaterial::where('material_id', $id_material)
                    ->get();
                if ( !$follows->isEmpty() )
                {
                    // TODO: Creamos notificacion y cambiamos el estado
                    // Crear notificacion
                    $notification = Notification::create([
                        'content' => 'El material ' . $detail_entry->material->full_description . ' ha sido ingresado.',
                        'reason_for_creation' => 'follow_material',
                        'user_id' => Auth::user()->id,
                        'url_go' => route('follow.index')
                    ]);

                    // Roles adecuados para recibir esta notificación admin, logistica
                    $users = User::role(['admin', 'operator'])->get();
                    foreach ( $users as $user )
                    {
                        $followUsers = FollowMaterial::where('material_id', $detail_entry->material_id)
                            ->where('user_id', $user->id)
                            ->get();
                        if ( !$followUsers->isEmpty() )
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
                    foreach ( $follows as $follow )
                    {
                        $follow->state = 'in_warehouse';
                        $follow->save();
                    }
                }

                //dd($id_material .' '. $count);
                $total_detail = 0;
                for ( $i=0; $i<sizeof($items); $i++ )
                {
                    if( $detail_entry->material_id == $items[$i]->id_material )
                    {
                        /*** if ($flag == 'dolares')
                         * Mantener la logica desde el if hasta el final igual
                         * if ($flag == 'soles')
                         * Cambiar el if $entry->currency_invoice === 'USD' y multiplicar $entry->currency_venta
                         * el else dejarlo igual al actual
                        */

                        if ( $flag->valueText == 'usd' )
                        {
                            if ( $entry->currency_invoice === 'PEN' )
                            {
                                $precio1 = round((float)$items[$i]->price,2) / (float) $entry->currency_compra;
                                $price1 = ($detail_entry->material->unit_price > $precio1) ? $detail_entry->material->unit_price : $precio1;
                                $materialS = Material::find($detail_entry->material_id);
                                if ( $materialS->unit_price < $price1 )
                                {
                                    $materialS->unit_price = $price1;
                                    $materialS->save();

                                    $detail_entry->unit_price = round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                } else {
                                    $detail_entry->unit_price = round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                }
                                //dd($detail_entry->material->materialType);
                                if ( isset($detail_entry->material->typeScrap) )
                                {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => (float)$detail_entry->material->typeScrap->length,
                                            'width' => (float)$detail_entry->material->typeScrap->width,
                                            'weight' => 0,
                                            'price' => round((float)$items[$i]->price, 2),
                                            'percentage' => 1,
                                            'typescrap_id' => $detail_entry->material->typeScrap->id,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                } else {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => 0,
                                            'width' => 0,
                                            'weight' => 0,
                                            'price' => round((float)$items[$i]->price, 2),
                                            'percentage' => 1,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                }
                            } else {
                                $price = ((float)$detail_entry->material->unit_price > round((float)$items[$i]->price,2)) ? $detail_entry->material->unit_price : round((float)$items[$i]->price,2);
                                $materialS = Material::find($detail_entry->material_id);
                                if ( (float)$materialS->unit_price < round((float)$items[$i]->price,2) )
                                {
                                    $materialS->unit_price = (float) round((float)$items[$i]->price,2);
                                    $materialS->save();

                                    $detail_entry->unit_price = (float) round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                } else {
                                    $detail_entry->unit_price = (float) round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                }
                                //dd($detail_entry->material->materialType);
                                if ( isset($detail_entry->material->typeScrap) )
                                {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => (float)$detail_entry->material->typeScrap->length,
                                            'width' => (float)$detail_entry->material->typeScrap->width,
                                            'weight' => 0,
                                            'price' => (float)$price,
                                            'percentage' => 1,
                                            'typescrap_id' => $detail_entry->material->typeScrap->id,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                } else {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => 0,
                                            'width' => 0,
                                            'weight' => 0,
                                            'price' => (float)$price,
                                            'percentage' => 1,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                }
                            }
                        } elseif ( $flag->valueText == 'pen' )
                        {
                            if ( $entry->currency_invoice === 'USD' )
                            {
                                $precio1 = round((float)$items[$i]->price,2) * (float) $entry->currency_venta;
                                $price1 = ($detail_entry->material->unit_price > $precio1) ? $detail_entry->material->unit_price : $precio1;
                                $materialS = Material::find($detail_entry->material_id);
                                if ( $materialS->unit_price < $price1 )
                                {
                                    $materialS->unit_price = $price1;
                                    $materialS->save();

                                    $detail_entry->unit_price = round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                } else {
                                    $detail_entry->unit_price = round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                }
                                //dd($detail_entry->material->materialType);
                                if ( isset($detail_entry->material->typeScrap) )
                                {
                                    if ( $material->tipo_venta_id == 3 )
                                    {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => (float)$detail_entry->material->typeScrap->length,
                                            'width' => (float)$detail_entry->material->typeScrap->width,
                                            'weight' => 0,
                                            'price' => round((float)$items[$i]->price,2),
                                            'percentage' => 1,
                                            'typescrap_id' => $detail_entry->material->typeScrap->id,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }

                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                } else {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => 0,
                                            'width' => 0,
                                            'weight' => 0,
                                            'price' => round((float)$items[$i]->price, 2),
                                            'percentage' => 1,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                }
                            } else {
                                $price = ((float)$detail_entry->material->unit_price > round((float)$items[$i]->price,2)) ? $detail_entry->material->unit_price : round((float)$items[$i]->price,2);
                                $materialS = Material::find($detail_entry->material_id);
                                if ( (float)$materialS->unit_price < round((float)$items[$i]->price,2) )
                                {
                                    $materialS->unit_price = (float) round((float)$items[$i]->price,2);
                                    $materialS->save();

                                    $detail_entry->unit_price = (float) round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                } else {
                                    $detail_entry->unit_price = (float) round((float)$items[$i]->price,2);
                                    $detail_entry->save();
                                }
                                //dd($detail_entry->material->materialType);
                                if ( isset($detail_entry->material->typeScrap) )
                                {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => (float)$detail_entry->material->typeScrap->length,
                                            'width' => (float)$detail_entry->material->typeScrap->width,
                                            'weight' => 0,
                                            'price' => (float)$price,
                                            'percentage' => 1,
                                            'typescrap_id' => $detail_entry->material->typeScrap->id,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                } else {
                                    if ( $material->tipo_venta_id == 3 ) {
                                        $item = Item::create([
                                            'detail_entry_id' => $detail_entry->id,
                                            'material_id' => $detail_entry->material_id,
                                            'code' => $items[$i]->item,
                                            'length' => 0,
                                            'width' => 0,
                                            'weight' => 0,
                                            'price' => (float)$price,
                                            'percentage' => 1,
                                            'location_id' => $items[$i]->id_location,
                                            'state' => $items[$i]->state,
                                            'state_item' => 'entered'
                                        ]);
                                    }
                                    $total_detail = $total_detail + (float)$items[$i]->price;
                                }
                            }
                        }



                    }
                }
                $detail_entry->total_detail = round($total_detail,2);
                $detail_entry->save();
            }


            /* SI ( En el campo factura y en (Orden Compra/Servicio) ) AND Diferente a 000
                Entonces
                SI ( Existe en la tabla creditos ) ENTONCES
                actualiza la factura en la tabla de creditos
            */
            if ( ($entry->invoice != '' || $entry->invoice != null) )
            {
                if ( $entry->purchase_order != '' || $entry->purchase_order != null )
                {
                    $order_purchase = OrderPurchase::where('code', $entry->purchase_order)->first();
                    /*$credit = SupplierCredit::with('deadline')
                        ->where('code_order', $entry->purchase_order)
                        ->where('state_credit', 'outstanding')->first();*/

                    if ( isset($order_purchase) )
                    {
                        /*if ( $credit->invoice != "" || $credit->invoice != null )
                        {
                            //$credit->delete();
                            $deadline = PaymentDeadline::find($credit->deadline->id);
                            $fecha_issue = Carbon::parse($entry->date_entry);
                            $fecha_expiration = $fecha_issue->addDays($deadline->days);
                            // TODO: Poner dias
                            $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                            $credit->supplier_id = $entry->supplier_id;
                            $credit->invoice = $credit->invoice.' | '.$entry->invoice;
                            $credit->image_invoice = $entry->image;
                            $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                            $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                            $credit->date_issue = $entry->date_entry;
                            $credit->date_expiration = $fecha_expiration;
                            $credit->days_to_expiration = $dias_to_expire;
                            $credit->code_order = $entry->purchase_order;
                            $credit->save();
                        } else {
                            //$credit->delete();
                            $deadline = PaymentDeadline::find($credit->deadline->id);
                            $fecha_issue = Carbon::parse($entry->date_entry);
                            $fecha_expiration = $fecha_issue->addDays($deadline->days);
                            // TODO: Poner dias
                            $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                            $credit->supplier_id = $entry->supplier_id;
                            $credit->invoice = $entry->invoice;
                            $credit->image_invoice = $entry->image;
                            $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                            $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                            $credit->date_issue = $entry->date_entry;
                            $credit->date_expiration = $fecha_expiration;
                            $credit->days_to_expiration = $dias_to_expire;
                            $credit->code_order = $entry->purchase_order;
                            $credit->save();
                        }*/
                        if ( isset($order_purchase->deadline) )
                        {
                            if ( $order_purchase->deadline->credit == 1 || $order_purchase->deadline->credit == true )
                            {
                                $deadline = PaymentDeadline::find($order_purchase->deadline->id);
                                $fecha_issue = Carbon::parse($entry->date_entry);
                                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                // TODO: Poner dias
                                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                                $credit = SupplierCredit::create([
                                    'supplier_id' => $order_purchase->supplier->id,
                                    'invoice' => ($this->onlyZeros($entry->invoice) == true) ? null:$entry->invoice,
                                    'image_invoice' => $entry->image,
                                    'total_soles' => ($order_purchase->currency_order == 'PEN') ? (float)$entry->total:null,
                                    'total_dollars' => ($order_purchase->currency_order == 'USD') ? (float)$entry->total:null,
                                    'date_issue' => Carbon::parse($entry->date_entry),
                                    'order_purchase_id' => $order_purchase->id,
                                    'state_credit' => 'outstanding',
                                    'order_service_id' => null,
                                    'date_expiration' => $fecha_expiration,
                                    'days_to_expiration' => $dias_to_expire,
                                    'code_order' => $order_purchase->code,
                                    'payment_deadline_id' => $order_purchase->payment_deadline_id,
                                    'entry_id' => $entry->id
                                ]);
                            }
                        }

                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Crear Ingreso Almacen',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por compra guardado con éxito.'], 200);

    }

    public function storeEntryPurchaseO(StoreEntryPurchaseRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $fecha = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
        $fechaFormato = $fecha->format('Y-m-d');

        $precioCompra = 1;
        $precioVenta = 1;

        $tipoMoneda = ($request->has('currency_invoice')) ? 'USD' : 'PEN';
        if ($tipoMoneda == 'USD') {
            $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
            $precioCompra = (float)$tipoCambioSunat->precioCompra;
            $precioVenta = (float)$tipoCambioSunat->precioVenta;
        }

        $flag = DataGeneral::where('name', 'type_current')->first();

        if ($request->get('purchase_order') != '' || $request->get('purchase_order') != null) {
            $order_purchase1 = OrderPurchase::where('code', $request->get('purchase_order'))->first();

            if (isset($order_purchase1)) {
                return response()->json([
                    'message' => "No se encontró la orden de compra indicada"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {

            $entry = Entry::create([
                'referral_guide' => $request->get('referral_guide'),
                'purchase_order' => $request->get('purchase_order'),
                'invoice' => $request->get('invoice'),
                'deferred_invoice' => ($request->has('deferred_invoice')) ? $request->get('deferred_invoice') : 'off',
                'currency_invoice' => ($request->has('currency_invoice')) ? 'USD' : 'PEN',
                'supplier_id' => $request->get('supplier_id'),
                'entry_type' => $request->get('entry_type'),
                'date_entry' => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance' => false,
                'currency_compra' => $precioCompra,
                'currency_venta' => $precioVenta,
                'observation' => $request->get('observation'),
            ]);

            // IMAGEN PRINCIPAL
            if (!$request->file('image')) {
                $entry->image = 'no_image.png';
                $entry->save();
            }
            else {
                $path = public_path() . '/images/entries/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

                if (strtoupper($extension) != "PDF") {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path . $filename, 80, 'JPG');
                    $entry->image = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf' . $entry->id . '.' . $extension;
                    $request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                }
            }

            // IMAGEN OBSERVACIÓN
            if (!$request->file('imageOb')) {
                $entry->imageOb = 'no_image.png';
                $entry->save();
            }
            else {
                $path = public_path() . '/images/entries/observations/';
                $image = $request->file('imageOb');
                $extension = $image->getClientOriginalExtension();

                if (strtoupper($extension) != "PDF") {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path . $filename, 80, 'JPG');
                    $entry->imageOb = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf' . $entry->id . '.' . $extension;
                    $request->file('imageOb')->move($path, $filename);
                    $entry->imageOb = $filename;
                    $entry->save();
                }
            }

            // ITEMS (JSON)
            $items = json_decode($request->get('items'));

            // Agrupar por (material_id + date_vence)
            $grouped = []; // $grouped[materialId][dateKey] = qty
            foreach ($items as $it) {
                $materialId = (int)$it->id_material;

                $dateStr = isset($it->date_vence) ? trim((string)$it->date_vence) : '';
                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;

                if (!isset($grouped[$materialId])) $grouped[$materialId] = [];
                if (!isset($grouped[$materialId][$dateKey])) $grouped[$materialId][$dateKey] = 0;

                $grouped[$materialId][$dateKey] += (float)($it->quantity ?? 1);
            }

            // Mapa: detail_entry por (material + fecha)
            $detailEntryMap = [];

            // 1) Crear DetailEntry por grupo y sumar stock
            foreach ($grouped as $id_material => $dates) {

                $material = Material::find($id_material);
                if (!$material) {
                    throw new \Exception("Material no encontrado: " . $id_material);
                }

                foreach ($dates as $dateKey => $count) {

                    $date = ($dateKey === '__NULL__')
                        ? null
                        : Carbon::createFromFormat('d/m/Y', $dateKey);

                    $material->stock_current = $material->stock_current + $count;

                    $detail_entry = DetailEntry::create([
                        'entry_id' => $entry->id,
                        'material_id' => $id_material,
                        'ordered_quantity' => $count,
                        'entered_quantity' => $count,
                        'date_vence' => $date
                    ]);

                    $detailEntryMap[$id_material][$dateKey] = $detail_entry;

                    if ($material->perecible == 's') {
                        MaterialVencimiento::firstOrCreate([
                            'material_id' => $id_material,
                            'fecha_vencimiento' => $date
                        ]);
                    }

                    // FollowMaterial (igual que antes)
                    $follows = FollowMaterial::where('material_id', $id_material)->get();
                    if (!$follows->isEmpty()) {

                        $notification = Notification::create([
                            'content' => 'El material ' . $detail_entry->material->full_description . ' ha sido ingresado.',
                            'reason_for_creation' => 'follow_material',
                            'user_id' => Auth::user()->id,
                            'url_go' => route('follow.index')
                        ]);

                        $users = User::role(['admin', 'owner'])->get();
                        foreach ($users as $user) {
                            $followUsers = FollowMaterial::where('material_id', $detail_entry->material_id)
                                ->where('user_id', $user->id)
                                ->get();

                            if (!$followUsers->isEmpty()) {
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

                        foreach ($follows as $follow) {
                            $follow->state = 'in_warehouse';
                            $follow->save();
                        }
                    }
                }

                $material->save();
            }

            // 2) Crear Items y acumular total_detail POR detail_entry
            $totalByDetailEntryId = [];

            for ($i = 0; $i < sizeof($items); $i++) {

                $materialId = (int)$items[$i]->id_material;
                $dateStr = isset($items[$i]->date_vence) ? trim((string)$items[$i]->date_vence) : '';
                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;

                if (!isset($detailEntryMap[$materialId][$dateKey])) {
                    continue;
                }

                $detail_entry = $detailEntryMap[$materialId][$dateKey];

                if (!isset($totalByDetailEntryId[$detail_entry->id])) {
                    $totalByDetailEntryId[$detail_entry->id] = 0;
                }

                // OJO: en este loop $material debe ser el del detail_entry
                $material = $detail_entry->material;

                // ---- TU LÓGICA ORIGINAL (misma estructura) ----
                if ($flag->valueText == 'usd') {

                    if ($entry->currency_invoice === 'PEN') {

                        // Conversión a USD para referencia (se mantiene)
                        $precio1 = round((float)$items[$i]->price, 2) / (float)$entry->currency_compra;

                        // ✅ YA NO actualizamos Material->unit_price por "máximo". Solo guardamos el precio del ingreso en el detail_entry.
                        $detail_entry->unit_price = round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        // ✅ Precio del Item: usar el precio del documento (sin "max")
                        $priceForItem = round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }

                    } else {

                        // ✅ YA NO actualizamos Material->unit_price por "máximo". Solo guardamos el precio del ingreso en el detail_entry.
                        $detail_entry->unit_price = (float)round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        // ✅ Precio del Item: usar el precio del documento (sin "max")
                        $priceForItem = (float)round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }
                    }

                } elseif ($flag->valueText == 'pen') {

                    if ($entry->currency_invoice === 'USD') {

                        // Conversión a PEN para referencia (se mantiene)
                        $precio1 = round((float)$items[$i]->price, 2) * (float)$entry->currency_venta;

                        // ✅ YA NO actualizamos Material->unit_price por "máximo". Solo guardamos el precio del ingreso en el detail_entry.
                        $detail_entry->unit_price = round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        // ✅ Precio del Item: usar el precio del documento (sin "max")
                        $priceForItem = round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }

                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }

                    } else {

                        // ✅ YA NO actualizamos Material->unit_price por "máximo". Solo guardamos el precio del ingreso en el detail_entry.
                        $detail_entry->unit_price = (float)round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        // ✅ Precio del Item: usar el precio del documento (sin "max")
                        $priceForItem = (float)round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => 'good',
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }
                    }
                }
            }

            // Guardar total_detail por detail_entry
            foreach ($totalByDetailEntryId as $detailEntryId => $total) {
                DetailEntry::where('id', $detailEntryId)->update([
                    'total_detail' => round($total, 2)
                ]);
            }

            // ===========================
            // ✅ ACTUALIZAR materials.unit_price con costo promedio (KARDEX)
            //    Esto reemplaza por completo la lógica antigua de "guardar el mayor"
            // ===========================
            $entryDate = $entry->date_entry instanceof Carbon
                ? $entry->date_entry
                : Carbon::parse($entry->date_entry);

            $affectedMaterialIds = array_map('intval', array_keys($grouped));
            $affectedMaterialIds = array_values(array_unique($affectedMaterialIds));

            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($affectedMaterialIds, $entryDate);

            foreach ($affectedMaterialIds as $mid) {
                $avg = (float) ($avgCosts[$mid] ?? 0.0);

                // Si prefieres no setear 0 cuando aún no hay historial, descomenta:
                // if ($avg <= 0) continue;

                Material::where('id', $mid)->update([
                    'unit_price' => $avg
                ]);
            }

            // BLOQUE ORIGINAL (CREDITOS) - igual
            if (($entry->invoice != '' || $entry->invoice != null)) {
                if ($entry->purchase_order != '' || $entry->purchase_order != null) {
                    $order_purchase = OrderPurchase::where('code', $entry->purchase_order)->first();

                    if (isset($order_purchase)) {
                        if (isset($order_purchase->deadline)) {
                            if ($order_purchase->deadline->credit == 1 || $order_purchase->deadline->credit == true) {
                                $deadline = PaymentDeadline::find($order_purchase->deadline->id);
                                $fecha_issue = Carbon::parse($entry->date_entry);
                                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                                SupplierCredit::create([
                                    'supplier_id' => $order_purchase->supplier->id,
                                    'invoice' => ($this->onlyZeros($entry->invoice) == true) ? null : $entry->invoice,
                                    'image_invoice' => $entry->image,
                                    'total_soles' => ($order_purchase->currency_order == 'PEN') ? (float)$entry->total : null,
                                    'total_dollars' => ($order_purchase->currency_order == 'USD') ? (float)$entry->total : null,
                                    'date_issue' => Carbon::parse($entry->date_entry),
                                    'order_purchase_id' => $order_purchase->id,
                                    'state_credit' => 'outstanding',
                                    'order_service_id' => null,
                                    'date_expiration' => $fecha_expiration,
                                    'days_to_expiration' => $dias_to_expire,
                                    'code_order' => $order_purchase->code,
                                    'payment_deadline_id' => $order_purchase->payment_deadline_id,
                                    'entry_id' => $entry->id
                                ]);
                            }
                        }
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Crear Ingreso Almacen',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Ingreso por compra guardado con éxito.'], 200);
    }

    public function storeEntryPurchase(StoreEntryPurchaseRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $fecha = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
        $fechaFormato = $fecha->format('Y-m-d');

        $precioCompra = 1;
        $precioVenta = 1;

        $tipoMoneda = ($request->has('currency_invoice')) ? 'USD' : 'PEN';
        if ($tipoMoneda == 'USD') {
            $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
            $precioCompra = (float) $tipoCambioSunat->precioCompra;
            $precioVenta = (float) $tipoCambioSunat->precioVenta;
        }

        $currency = app(SettingService::class)->get('finance.base_currency');

        if ($request->get('purchase_order') != '' || $request->get('purchase_order') != null) {
            $order_purchase1 = OrderPurchase::where('code', $request->get('purchase_order'))->first();

            if (isset($order_purchase1)) {
                return response()->json([
                    'message' => "No se encontró la orden de compra indicada"
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Antes de crear Entry, guardar imágenes o escribir stock.
            $items = app(\App\Services\Inventory\EntryDestinationValidator::class)->normalize(
                $request->get('items'),
                $request->get('entry_context_company_id'),
                $request->get('entry_context_branch_id')
            );
            $entry = Entry::create([
                'referral_guide'   => $request->get('referral_guide'),
                'purchase_order'   => $request->get('purchase_order'),
                'invoice'          => $request->get('invoice'),
                'deferred_invoice' => ($request->has('deferred_invoice')) ? $request->get('deferred_invoice') : 'off',
                'currency_invoice' => ($request->has('currency_invoice')) ? 'USD' : 'PEN',
                'supplier_id'      => $request->get('supplier_id'),
                'entry_type'       => $request->get('entry_type'),
                'date_entry'       => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance'          => false,
                'currency_compra'  => $precioCompra,
                'currency_venta'   => $precioVenta,
                'observation'      => $request->get('observation'),

                'company_id' => TenantContext::companyId(),
            ]);

            // =========================
            // IMAGEN PRINCIPAL
            // =========================
            if (!$request->file('image')) {
                $entry->image = 'no_image.png';
                $entry->save();
            }
            else {
                $path = public_path() . '/images/entries/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

                if (strtoupper($extension) != "PDF") {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path . $filename, 80, 'JPG');
                    $entry->image = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf' . $entry->id . '.' . $extension;
                    $request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                }
            }

            // =========================
            // IMAGEN OBSERVACIÓN
            // =========================
            if (!$request->file('imageOb')) {
                $entry->imageOb = 'no_image.png';
                $entry->save();
            }
            else {
                $path = public_path() . '/images/entries/observations/';
                $image = $request->file('imageOb');
                $extension = $image->getClientOriginalExtension();

                if (strtoupper($extension) != "PDF") {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path . $filename, 80, 'JPG');
                    $entry->imageOb = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf' . $entry->id . '.' . $extension;
                    $request->file('imageOb')->move($path, $filename);
                    $entry->imageOb = $filename;
                    $entry->save();
                }
            }

            // =========================
            // ITEMS (JSON)
            // =========================
            // $items ya fue validado y normalizado dentro de esta transacción.

            /**
             * Agrupar por:
             * stock_item_id + id_location + lote + fecha_vencimiento + price
             */
            $grouped = [];

            foreach ($items as $it) {
                $materialId  = (int) ($it->id_material ?? 0);
                $stockItemId = (int) ($it->stock_item_id ?? 0);
                $locationId = (int) $it->id_location;
                $warehouseId = (int) $it->warehouse_id;

                $lotCode = isset($it->material_lote) ? trim((string) $it->material_lote) : '';
                $dateStr = isset($it->date_vence) ? trim((string) $it->date_vence) : '';
                $unitPrice = (float) ($it->price ?? 0);

                if (!$stockItemId) {
                    throw new \Exception("Uno de los items no tiene stock_item_id.");
                }

                if (!$materialId) {
                    throw new \Exception("Uno de los items no tiene id_material.");
                }

                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;
                $lotKey  = ($lotCode === '') ? '__NOLOT__' : $lotCode;

                $groupKey = implode('|', [
                    $stockItemId,
                    $warehouseId ?: 'null',
                    $lotKey,
                    $dateKey,
                    number_format($unitPrice, 4, '.', '')
                ]);

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'material_id'   => $materialId,
                        'stock_item_id' => $stockItemId,
                        'location_id' => $locationId,
                        'warehouse_id'  => $warehouseId,
                        'lot_code'      => $lotCode,
                        'date_vence'    => $dateStr,
                        'unit_price'    => $unitPrice,
                        'count'         => 0,
                    ];
                }

                $grouped[$groupKey]['count'] += (float) ($it->quantity ?? 1);
            }

            // Mapa de DetailEntry por groupKey
            $detailEntryMap = [];
            $stockLotMap = [];
            // =========================
            // 1) Crear DetailEntry por grupo
            //    + actualizar inventory_levels
            //    + crear/actualizar stock_lots
            // =========================
            foreach ($grouped as $groupKey => $group) {
                $material = Material::find($group['material_id']);
                if (!$material) {
                    throw new \Exception("Material no encontrado: " . $group['material_id']);
                }

                $stockItem = StockItem::with('material')->find($group['stock_item_id']);
                if (!$stockItem) {
                    throw new \Exception("StockItem no encontrado: " . $group['stock_item_id']);
                }

                if ((int) $stockItem->material_id !== (int) $material->id) {
                    throw new \RuntimeException(
                        'El StockItem no corresponde al material indicado.'
                    );
                }

                $companyStockItem = CompanyStockItem::query()
                    ->where(
                        'company_id',
                        TenantContext::companyId()
                    )
                    ->where(
                        'stock_item_id',
                        $stockItem->id
                    )
                    ->where('is_active', true)
                    ->first();

                if (!$companyStockItem) {
                    throw new \RuntimeException(
                        'El producto no está habilitado para la empresa actual.'
                    );
                }

                $date = null;
                if (!empty($group['date_vence']) && $group['date_vence'] !== '__NULL__') {
                    $date = Carbon::createFromFormat('d/m/Y', $group['date_vence']);
                }

                $detailEntry = DetailEntry::create([
                    'company_id'        => $entry->company_id,
                    'entry_id'          => $entry->id,
                    'material_id'       => $group['material_id'], // compatibilidad temporal
                    'ordered_quantity'  => $group['count'],
                    'entered_quantity'  => $group['count'],
                    'unit_price'        => round((float) $group['unit_price'], 2),
                    'date_vence'        => $date,
                    'stock_item_id'     => $group['stock_item_id'],
                    //'location_id'       => $group['location_id'],
                    //'lot_code'          => $group['lot_code'] !== '' ? $group['lot_code'] : null,
                ]);

                $detailEntryMap[$groupKey] = $detailEntry;

                // Si sigue aplicando para perecibles en tu lógica actual
                if ($material->perecible == 's' && $date) {
                    MaterialVencimiento::firstOrCreate([
                        'material_id' => $group['material_id'],
                        'fecha_vencimiento' => $date
                    ]);
                }

                // =========================
                // Actualizar InventoryLevel
                // =========================
                $this->increaseInventoryLevel(
                    $group['stock_item_id'],
                    $group['location_id'],
                    $group['warehouse_id'],
                    $group['count'],
                    $group['unit_price']
                );

                // =========================
                // Crear/actualizar lote si aplica
                // =========================
                $stockLot = $this->increaseStockLot(
                    $group['stock_item_id'],
                    $group['location_id'],
                    $group['warehouse_id'],
                    $detailEntry->id,
                    $group['lot_code'] ?? null,
                    !empty($group['date_vence']) ? $date : null,
                    $group['count'],
                    $group['unit_price']
                );

                $stockLotMap[$groupKey] = $stockLot;

                // =========================
                // FollowMaterial (igual que antes)
                // =========================
                $follows = FollowMaterial::where('material_id', $group['material_id'])->get();
                if (!$follows->isEmpty()) {
                    $notification = Notification::create([
                        'content' => 'El material ' . $material->full_description . ' ha sido ingresado.',
                        'reason_for_creation' => 'follow_material',
                        'user_id' => Auth::user()->id,
                        'url_go' => route('follow.index')
                    ]);

                    $users = User::role(['admin', 'owner'])->get();
                    foreach ($users as $user) {
                        $followUsers = FollowMaterial::where('material_id', $group['material_id'])
                            ->where('user_id', $user->id)
                            ->get();

                        if (!$followUsers->isEmpty()) {
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

                    foreach ($follows as $follow) {
                        $follow->state = 'in_warehouse';
                        $follow->save();
                    }
                }
            }

            // =========================
            // 2) Crear Items unitarios y acumular total_detail
            // =========================
            $totalByDetailEntryId = [];

            foreach ($items as $it) {
                $materialId  = (int) ($it->id_material ?? 0);
                $stockItemId = (int) ($it->stock_item_id ?? 0);
                $locationId = (int) $it->id_location;
                $warehouseId = (int) $it->warehouse_id;

                $lotCode = isset($it->material_lote) ? trim((string) $it->material_lote) : '';
                $dateStr = isset($it->date_vence) ? trim((string) $it->date_vence) : '';
                $unitPrice = (float) ($it->price ?? 0);

                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;
                $lotKey  = ($lotCode === '') ? '__NOLOT__' : $lotCode;

                $groupKey = implode('|', [
                    $stockItemId,
                    //$locationId ?: 'null',
                    $warehouseId ?: 'null',
                    $lotKey,
                    $dateKey,
                    number_format($unitPrice, 4, '.', '')
                ]);

                if (!isset($detailEntryMap[$groupKey])) {
                    continue;
                }

                $detailEntry = $detailEntryMap[$groupKey];
                $material = $detailEntry->material;

                $stockLot = $stockLotMap[$groupKey] ?? null;

                if (!isset($totalByDetailEntryId[$detailEntry->id])) {
                    $totalByDetailEntryId[$detailEntry->id] = 0;
                }

                // =========================
                // Conservamos tu lógica de moneda/costo unitario por item
                // =========================
                if ($currency === 'USD') {
                    if ($entry->currency_invoice === 'PEN') {
                        $detailEntry->unit_price = round((float) $it->price, 2);
                        $detailEntry->save();
                        $priceForItem = round((float) $it->price, 2);
                    } else {
                        $detailEntry->unit_price = round((float) $it->price, 2);
                        $detailEntry->save();
                        $priceForItem = round((float) $it->price, 2);
                    }
                }
                elseif ($currency === 'PEN') {
                    if ($entry->currency_invoice === 'USD') {
                        $detailEntry->unit_price = round((float) $it->price, 2);
                        $detailEntry->save();
                        $priceForItem = round((float) $it->price, 2);
                    } else {
                        $detailEntry->unit_price = round((float) $it->price, 2);
                        $detailEntry->save();
                        $priceForItem = round((float) $it->price, 2);
                    }
                }
                else {
                    $priceForItem = round((float) $it->price, 2);
                }

                // =========================
                // Crear Item unitario
                // =========================
                if ($material->tipo_venta_id == 3) {

                    if (!$stockLot) {
                        throw new \RuntimeException(
                            'No se pudo determinar el lote asociado al ítem.'
                        );
                    }

                    if (isset($material->typeScrap)) {

                        Item::create([
                            'company_id' => $stockLot->company_id,

                            'detail_entry_id' => $detailEntry->id,
                            'stock_item_id'   => $detailEntry->stock_item_id,
                            'stock_lot_id'    => $stockLot ? $stockLot->id : null,
                            'material_id'     => $detailEntry->material_id,
                            'code'            => $it->item,
                            'length'          => (float) $material->typeScrap->length,
                            'width'           => (float) $material->typeScrap->width,
                            'weight'          => 0,
                            'price'           => $priceForItem,
                            'unit_cost'       => (float) $detailEntry->unit_price,
                            'percentage'      => 1,
                            'typescrap_id'    => $material->typeScrap->id,
                            'warehouse_id'    => $warehouseId,
                            'location_id'     => $it->id_location,
                            'state'           => $it->state ?? 'good',
                            'state_item'      => 'entered'
                        ]);
                    }
                    else {
                        Item::create([
                            'company_id' => $stockLot->company_id,

                            'detail_entry_id' => $detailEntry->id,
                            'stock_item_id'   => $detailEntry->stock_item_id,
                            'stock_lot_id'    => $stockLot ? $stockLot->id : null,
                            'material_id'     => $detailEntry->material_id,
                            'code'            => $it->item,
                            'length'          => 0,
                            'width'           => 0,
                            'weight'          => 0,
                            'price'           => $priceForItem,
                            'unit_cost'       => (float) $detailEntry->unit_price,
                            'percentage'      => 1,
                            'warehouse_id'    => $warehouseId,
                            'location_id'     => $it->id_location,
                            'state'           => $it->state ?? 'good',
                            'state_item'      => 'entered'
                        ]);
                    }
                }

                $totalByDetailEntryId[$detailEntry->id] += (float) $it->price;
            }

            // =========================
            // 3) Guardar total_detail por detail_entry
            // =========================
            foreach ($totalByDetailEntryId as $detailEntryId => $total) {
                DetailEntry::where('id', $detailEntryId)->update([
                    'total_detail' => round($total, 2)
                ]);
            }

            // =========================
            // 4) Recalcular costo promedio del material padre (compatibilidad temporal)
            // =========================
            $entryDate = $entry->date_entry instanceof Carbon
                ? $entry->date_entry
                : Carbon::parse($entry->date_entry);

            $affectedStockItemIds = collect($grouped)
                ->pluck('stock_item_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values()
                ->toArray();

            //$avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($affectedMaterialIds, $entryDate);
            $avgCosts = $this->inventoryCostService->getAverageCostsByStockItem($affectedStockItemIds, $entryDate);

            foreach ($affectedStockItemIds as $stockItemId) {
                $avg = (float) ($avgCosts[$stockItemId] ?? 0.0);

                InventoryLevel::query()
                    ->where(
                        'company_id',
                        TenantContext::companyId()
                    )
                    ->where(
                        'stock_item_id',
                        $stockItemId
                    )
                    ->update([
                        'average_cost' => $avg
                    ]);
            }

            // =========================
            // 5) BLOQUE ORIGINAL CREDITOS
            // =========================
            if (($entry->invoice != '' || $entry->invoice != null)) {
                if ($entry->purchase_order != '' || $entry->purchase_order != null) {
                    $order_purchase = OrderPurchase::where('code', $entry->purchase_order)->first();

                    if (isset($order_purchase)) {
                        if (isset($order_purchase->deadline)) {
                            if ($order_purchase->deadline->credit == 1 || $order_purchase->deadline->credit == true) {
                                $deadline = PaymentDeadline::find($order_purchase->deadline->id);
                                $fecha_issue = Carbon::parse($entry->date_entry);
                                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                                SupplierCredit::create([
                                    'supplier_id' => $order_purchase->supplier->id,
                                    'invoice' => ($this->onlyZeros($entry->invoice) == true) ? null : $entry->invoice,
                                    'image_invoice' => $entry->image,
                                    'total_soles' => ($order_purchase->currency_order == 'PEN') ? (float) $entry->total : null,
                                    'total_dollars' => ($order_purchase->currency_order == 'USD') ? (float) $entry->total : null,
                                    'date_issue' => Carbon::parse($entry->date_entry),
                                    'order_purchase_id' => $order_purchase->id,
                                    'state_credit' => 'outstanding',
                                    'order_service_id' => null,
                                    'date_expiration' => $fecha_expiration,
                                    'days_to_expiration' => $dias_to_expire,
                                    'code_order' => $order_purchase->code,
                                    'payment_deadline_id' => $order_purchase->payment_deadline_id,
                                    'entry_id' => $entry->id
                                ]);
                            }
                        }
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Crear Ingreso Almacen',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();
            $first = reset($errors);
            return response()->json(['message' => $first[0] ?? 'Revise los destinos del ingreso.', 'errors' => $errors], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Ingreso por compra guardado con éxito.'], 200);
    }

    private function increaseStockLot(
        int $stockItemId,
        ?int $locationId,
        ?int $warehouseId,
        int $detailEntryId,
        ?string $lotCode,
        $expirationDate,
        float $quantity,
        float $unitCost
    ) {
        $tenantId =
            TenantContext::tenantId();

        $companyId =
            TenantContext::companyId();


        if (!$warehouseId) {
            throw new \RuntimeException(
                'No se encontró el almacén para registrar el lote.'
            );
        }

        if (!$locationId) {
            throw new \RuntimeException(
                'No se encontró la ubicación para registrar el lote.'
            );
        }


        /*
         * ============================================================
         * STOCK ITEM
         * ============================================================
         *
         * StockItem ya tiene TenantScope.
         */
        $stockItem =
            StockItem::query()
                ->where('id', $stockItemId)
                ->first();

        if (!$stockItem) {
            throw new \RuntimeException(
                'El producto seleccionado no pertenece al grupo empresarial actual.'
            );
        }


        /*
         * Además debe estar habilitado para la Company
         * donde se está realizando la entrada.
         */
        $enabledForCompany =
            CompanyStockItem::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'stock_item_id',
                    $stockItemId
                )
                ->where(
                    'is_active',
                    true
                )
                ->exists();

        if (!$enabledForCompany) {
            throw new \RuntimeException(
                'El producto seleccionado no está habilitado para la empresa actual.'
            );
        }


        /*
         * ============================================================
         * WAREHOUSE
         * ============================================================
         */

        $warehouse =
            Warehouse::query()
                ->where(
                    'id',
                    $warehouseId
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->first();

        if (!$warehouse) {
            throw new \RuntimeException(
                'El almacén seleccionado no pertenece a la empresa actual.'
            );
        }


        /*
         * ============================================================
         * LOCATION
         * ============================================================
         */

        $location =
            Location::query()
                ->where(
                    'id',
                    $locationId
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'warehouse_id',
                    $warehouse->id
                )
                ->first();

        if (!$location) {
            throw new \RuntimeException(
                'La ubicación seleccionada no pertenece al almacén y empresa actuales.'
            );
        }


        /*
         * Defensa adicional.
         */
        if (
            (int) $warehouse->tenant_id !==
            (int) $tenantId
        ) {
            throw new \RuntimeException(
                'El almacén no pertenece al grupo empresarial actual.'
            );
        }

        if (
            (int) $location->tenant_id !==
            (int) $tenantId
        ) {
            throw new \RuntimeException(
                'La ubicación no pertenece al grupo empresarial actual.'
            );
        }


        $lotCode =
            trim((string) $lotCode);

        $lotCode =
            $lotCode !== ''
                ? $lotCode
                : null;


        /*
         * ============================================================
         * STOCK LOT
         * ============================================================
         *
         * tenant_id será asignado por BelongsToTenant.
         * company_id sí debe ser explícito.
         */

        return StockLot::create([
            'company_id' =>
                $companyId,

            'stock_item_id' =>
                $stockItemId,

            'location_id' =>
                $location->id,

            'warehouse_id' =>
                $warehouse->id,

            'detail_entry_id' =>
                $detailEntryId,

            'lot_code' =>
                $lotCode,

            'expiration_date' =>
                $expirationDate,

            'qty_on_hand' =>
                $quantity,

            'qty_reserved' =>
                0,

            'unit_cost' =>
                $unitCost,
        ]);
    }

    private function increaseInventoryLevel(
        int $stockItemId,
        ?int $locationId,
        ?int $warehouseId,
        float $quantity,
        float $unitCost
    ): void {
        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();

        if (!$warehouseId) {
            throw new \RuntimeException(
                'No se encontró el almacén para actualizar el inventario.'
            );
        }

        if (!$locationId) {
            throw new \RuntimeException(
                'No se encontró la ubicación para actualizar el inventario.'
            );
        }

        if ($quantity <= 0) {
            throw new \RuntimeException(
                'La cantidad ingresada debe ser mayor a cero.'
            );
        }

        /*
         * ============================================================
         * STOCK ITEM
         * ============================================================
         */
        $stockItem = StockItem::query()
            ->where('id', $stockItemId)
            ->first();

        if (!$stockItem) {
            throw new \RuntimeException(
                'El producto seleccionado no pertenece al grupo empresarial actual.'
            );
        }

        /*
         * ============================================================
         * COMPANY STOCK ITEM
         * ============================================================
         */
        $enabledForCompany = CompanyStockItem::query()
            ->where('company_id', $companyId)
            ->where('stock_item_id', $stockItemId)
            ->where('is_active', true)
            ->exists();

        if (!$enabledForCompany) {
            throw new \RuntimeException(
                'El producto seleccionado no está habilitado para la empresa actual.'
            );
        }

        /*
         * ============================================================
         * WAREHOUSE
         * ============================================================
         */
        $warehouse = Warehouse::query()
            ->where('id', $warehouseId)
            ->where('company_id', $companyId)
            ->first();

        if (!$warehouse) {
            throw new \RuntimeException(
                'El almacén seleccionado no pertenece a la empresa actual.'
            );
        }

        /*
         * ============================================================
         * LOCATION
         * ============================================================
         */
        $location = Location::query()
            ->where('id', $locationId)
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$location) {
            throw new \RuntimeException(
                'La ubicación seleccionada no pertenece al almacén y empresa actuales.'
            );
        }

        /*
         * Defensa adicional de Tenant.
         */
        if (
            (int) $warehouse->tenant_id !==
            (int) $tenantId
        ) {
            throw new \RuntimeException(
                'El almacén no pertenece al grupo empresarial actual.'
            );
        }

        if (
            (int) $location->tenant_id !==
            (int) $tenantId
        ) {
            throw new \RuntimeException(
                'La ubicación no pertenece al grupo empresarial actual.'
            );
        }

        /*
         * ============================================================
         * INVENTORY LEVEL
         * ============================================================
         *
         * Usamos lockForUpdate para evitar que dos entradas simultáneas
         * lean el mismo stock y terminen pisándose entre sí.
         */
        $inventoryLevel = InventoryLevel::query()
            ->where('company_id', $companyId)
            ->where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (!$inventoryLevel) {
            $inventoryLevel = new InventoryLevel();

            $inventoryLevel->company_id =
                $companyId;

            $inventoryLevel->stock_item_id =
                $stockItemId;

            $inventoryLevel->warehouse_id =
                $warehouseId;

            $inventoryLevel->location_id =
                $locationId;

            $inventoryLevel->qty_on_hand = 0;
            $inventoryLevel->qty_reserved = 0;
            $inventoryLevel->average_cost = 0;
            $inventoryLevel->last_cost = 0;
            $inventoryLevel->min_alert = 0;
            $inventoryLevel->max_alert = 0;
        }

        $stockAnterior =
            (float) $inventoryLevel->qty_on_hand;

        $costoAnterior =
            (float) $inventoryLevel->average_cost;

        $totalQty =
            $stockAnterior + $quantity;

        $inventoryLevel->qty_on_hand =
            $totalQty;

        $inventoryLevel->last_cost =
            $unitCost;

        if ($totalQty > 0) {
            $inventoryLevel->average_cost =
                (
                    ($stockAnterior * $costoAnterior)
                    +
                    ($quantity * $unitCost)
                )
                /
                $totalQty;
        }

        $inventoryLevel->save();
    }

    public function storeEntryScrap(Request $request)
    {
        DB::beginTransaction();
        try {
            $entry = Entry::create([
                'entry_type' => "Retacería",
                'date_entry' => Carbon::now(),
                'finance' => false
            ]);

            $itemSelected = json_decode($request->get('item'));

            if (
                !is_array($itemSelected) ||
                empty($itemSelected) ||
                empty($itemSelected[0]->id)
            ) {
                throw new \RuntimeException(
                    'No se recibió un ítem válido para generar el retazo.'
                );
            }

            $companyId = TenantContext::companyId();

            $itemOriginal = Item::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $itemSelected[0]->id)
                ->lockForUpdate()
                ->first();

            if (!$itemOriginal) {
                throw new \RuntimeException(
                    'El ítem seleccionado no pertenece a la empresa actual.'
                );
            }
            //dd($item_selected[0]->detailEntry);

            $detailEntry = DetailEntry::create([
                'entry_id' =>
                    $entry->id,

                'material_id' =>
                    $itemOriginal->material_id,

                'stock_item_id' =>
                    $itemOriginal->stock_item_id,
            ]);

            // TODO: Crear el item

            $item = Item::create([
                'company_id' =>
                    $itemOriginal->company_id,

                'detail_entry_id' =>
                    $detailEntry->id,

                'stock_item_id' =>
                    $itemOriginal->stock_item_id,

                'stock_lot_id' =>
                    $itemOriginal->stock_lot_id,

                'material_id' =>
                    $itemOriginal->material_id,

                'code' =>
                    $itemOriginal->code,

                'length' =>
                    (float) ($itemSelected[0]->length ?? 0),

                'width' =>
                    (float) ($itemSelected[0]->width ?? 0),

                'weight' =>
                    (float) ($itemSelected[0]->weight ?? 0),

                'price' =>
                    (float) ($itemSelected[0]->price ?? 0),

                'unit_cost' =>
                    (float) $itemOriginal->unit_cost,

                'typescrap_id' =>
                    $itemSelected[0]->typescrap_id ?? null,

                'warehouse_id' =>
                    $itemOriginal->warehouse_id,

                'location_id' =>
                    $itemOriginal->location_id,

                'state' =>
                    $itemOriginal->state,

                'state_item' =>
                    'scraped',
            ]);

            // TODO: Eliminar el item anterior
            //$item_deleted = Item::find($item_selected[0]->id);
            $itemOriginal->percentage = 0;
            $itemOriginal->save();
            //$item_deleted->delete();

            // TODO: Actualizar la cantidad en el material
            // TODO: Primero restar uno y luego sumar AreaReal/AreaTotal
            $material = Material::with('typeScrap')->find($item->material_id);
            $porcentaje = 0;
            if( isset($material->typeScrap) && ($material->typeScrap->id == 1 || $material->typeScrap->id == 2)  )
            {
                $porcentaje = ($item->length*$item->width)/($material->typeScrap->length*$material->typeScrap->width);
                $item->percentage = $porcentaje;
                $item->save();
            }
            // TODO: Agregamos los tubos pequeños
            if( isset($material->typeScrap) && ($material->typeScrap->id == 3 || $material->typeScrap->id == 4) )
            {
                $porcentaje = ($item->length)/($material->typeScrap->length);
                $item->percentage = $porcentaje;
                $item->save();
            }
            $material->stock_current = $material->stock_current + $porcentaje;
            $material->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por retacería guardado con éxito.'], 200);

    }

    public function show(Entry $entry)
    {
        //
    }

    public function editEntryPurchase($id)
    {
        $entry = Entry::query()
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->where('id', $id)
            ->firstOrFail();

        $suppliers = Supplier::query()
            ->orderBy('business_name')
            ->get();

        $warehouseOptions = app(\App\Services\Inventory\EntryWarehouseResolver::class)
            ->optionsForCurrentContext();

        return view(
            'entry.edit_entry_purchaseV2',
            compact(
                'entry',
                'suppliers',
                'warehouseOptions'
            )
        );
    }

    public function showEntryPurchase(Entry $entry)
    {
        $suppliers = Supplier::all();
        return view('entry.show_entry_purchase', compact('entry', 'suppliers'));
    }

    public function updateEntryPurchase(UpdateEntryPurchaseRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $entry = Entry::query()
                ->where(
                    'company_id',
                    TenantContext::companyId()
                )
                ->where(
                    'id',
                    $request->get('entry_id')
                )
                ->firstOrFail();
            if ($entry->order_purchase_id || trim((string) $entry->purchase_order) !== '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'entry_id' => 'Este ingreso pertenece a una orden. Reciba las cantidades pendientes desde Órdenes para almacén; no se pueden agregar productos ni modificarlo por esta vía.',
                ]);
            }

            $entry->referral_guide = $request->get('referral_guide');
            $entry->purchase_order = $request->get('purchase_order');
            $entry->invoice = $request->get('invoice');
            $entry->deferred_invoice = ($request->has('deferred_invoice')) ? $request->get('deferred_invoice'):'off';

            $supplierId = $request->get('supplier_id');

            if ($supplierId) {
                $supplier = Supplier::query()
                    ->where('id', $supplierId)
                    ->firstOrFail();

                $entry->supplier_id = $supplier->id;
            } else {
                $entry->supplier_id = null;
            }

            $entry->date_entry = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
            $entry->observation = $request->get('observation');
            $entry->save();

            // TODO: Tratamiento de un archivo de forma tradicional
            if (!$request->file('image')) {
                if ($entry->image == 'no_image.png' || $entry->image == null) {
                    $entry->image = 'no_image.png';
                    $entry->save();
                }
            } else {
                $path = public_path().'/images/entries/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();
                if (strtoupper($extension) != "PDF" )
                {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    //$request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf'.$entry->id . '.' .$extension;
                    $request->file('image')->move($path, $filename);
                    $entry->image = $filename;
                    $entry->save();
                }

            }

            if (!$request->file('imageOb')) {
                if ($entry->imageOb == 'no_image.png' || $entry->imageOb == null) {
                    $entry->imageOb = 'no_image.png';
                    $entry->save();
                }
            } else {
                $path = public_path().'/images/entries/observations/';
                $image = $request->file('imageOb');
                $extension = $image->getClientOriginalExtension();
                if ( strtoupper($extension) != "PDF" )
                {
                    $filename = $entry->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    $entry->imageOb = $filename;
                    $entry->save();
                } else {
                    $filename = 'pdf'.$entry->id . '.' .$extension;
                    $request->file('imageOb')->move($path, $filename);
                    $entry->imageOb = $filename;
                    $entry->save();
                }
            }

            /* SI ( En el campo factura y en (Orden Compra/Servicio) ) AND Diferente a 000
                Entonces
                SI ( Existe en la tabla creditos ) ENTONCES
                actualiza la factura en la tabla de creditos
            */
            if (!empty($entry->invoice))
            {
                if (!empty($entry->purchase_order))
                {
                    $credit = SupplierCredit::with('deadline')
                        ->where('entry_id', $entry->id)
                        ->where('state_credit', 'outstanding')->first();

                    $credit2 = SupplierCredit::with('deadline')
                        ->where('invoice', $entry->invoice)
                        ->where('state_credit', 'outstanding')->first();

                    if ( isset($credit) )
                    {
                        /*dump($credit);
                        dd("Credit encontrado");*/
                        // TODO: Analizar lo de editar de facturas
                        //$credit->delete();

                        $deadline = PaymentDeadline::find($credit->deadline->id);
                        $fecha_issue = Carbon::parse($entry->date_entry);
                        $fecha_expiration = $fecha_issue->addDays($deadline->days);
                        // TODO:poner dias
                        $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                        $credit->supplier_id = $entry->supplier_id;
                        $credit->invoice = ($this->onlyZeros($entry->invoice) == true) ? null:$entry->invoice;
                        $credit->image_invoice = $entry->image;
                        $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                        $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                        $credit->date_issue = $entry->date_entry;
                        $credit->date_expiration = $fecha_expiration;
                        $credit->days_to_expiration = $dias_to_expire;
                        $credit->code_order = $entry->purchase_order;
                        $credit->entry_id = $entry->id;
                        $credit->save();

                    } elseif( isset($credit2) ) {
                        //dump("Credit2 encontrado");
                        //dd("Credit2 encontrado");
                        $deadline = PaymentDeadline::find($credit2->deadline->id);
                        $fecha_issue = Carbon::parse($entry->date_entry);
                        $fecha_expiration = $fecha_issue->addDays($deadline->days);
                        // TODO:poner dias
                        $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                        $credit2->supplier_id = $entry->supplier_id;
                        $credit2->invoice = ($this->onlyZeros($entry->invoice) == true) ? null:$entry->invoice;
                        $credit2->image_invoice = $entry->image;
                        $credit2->total_soles = ((float)$credit2->total_soles>0) ? $entry->total:null;
                        $credit2->total_dollars = ((float)$credit2->total_dollars>0) ? $entry->total:null;
                        $credit2->date_issue = $entry->date_entry;
                        $credit2->date_expiration = $fecha_expiration;
                        $credit2->days_to_expiration = $dias_to_expire;
                        $credit2->code_order = $entry->purchase_order;
                        $credit2->entry_id = $entry->id;
                        $credit2->save();
                    } else {
                        $order_purchase = OrderPurchase::where('code', $entry->purchase_order)->first();

                        if ( isset($order_purchase) )
                        {
                            /*if ( $credit->invoice != "" || $credit->invoice != null )
                            {
                                //$credit->delete();
                                $deadline = PaymentDeadline::find($credit->deadline->id);
                                $fecha_issue = Carbon::parse($entry->date_entry);
                                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                // TODO: Poner dias
                                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                                $credit->supplier_id = $entry->supplier_id;
                                $credit->invoice = $credit->invoice.' | '.$entry->invoice;
                                $credit->image_invoice = $entry->image;
                                $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                                $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                                $credit->date_issue = $entry->date_entry;
                                $credit->date_expiration = $fecha_expiration;
                                $credit->days_to_expiration = $dias_to_expire;
                                $credit->code_order = $entry->purchase_order;
                                $credit->save();
                            } else {
                                //$credit->delete();
                                $deadline = PaymentDeadline::find($credit->deadline->id);
                                $fecha_issue = Carbon::parse($entry->date_entry);
                                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                // TODO: Poner dias
                                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
                                $credit->supplier_id = $entry->supplier_id;
                                $credit->invoice = $entry->invoice;
                                $credit->image_invoice = $entry->image;
                                $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                                $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                                $credit->date_issue = $entry->date_entry;
                                $credit->date_expiration = $fecha_expiration;
                                $credit->days_to_expiration = $dias_to_expire;
                                $credit->code_order = $entry->purchase_order;
                                $credit->save();
                            }*/
                            if ( isset($order_purchase->deadline) )
                            {
                                if ( $order_purchase->deadline->credit == 1 || $order_purchase->deadline->credit == true )
                                {
                                    $deadline = PaymentDeadline::find($order_purchase->deadline->id);
                                    $fecha_issue = Carbon::parse($entry->date_entry);
                                    $fecha_expiration = $fecha_issue->addDays($deadline->days);
                                    // TODO: Poner dias
                                    $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                                    $credit = SupplierCredit::create([
                                        'supplier_id' => $order_purchase->supplier->id,
                                        'invoice' => ($this->onlyZeros($entry->invoice) == true) ? null:$entry->invoice,
                                        'image_invoice' => $entry->image,
                                        'total_soles' => ($order_purchase->currency_order == 'PEN') ? (float)$entry->total:null,
                                        'total_dollars' => ($order_purchase->currency_order == 'USD') ? (float)$entry->total:null,
                                        'date_issue' => Carbon::parse($entry->date_entry),
                                        'order_purchase_id' => $order_purchase->id,
                                        'state_credit' => 'outstanding',
                                        'order_service_id' => null,
                                        'date_expiration' => $fecha_expiration,
                                        'days_to_expiration' => $dias_to_expire,
                                        'code_order' => $order_purchase->code,
                                        'payment_deadline_id' => $order_purchase->payment_deadline_id,
                                        'entry_id' => $entry->id
                                    ]);
                                }
                            }

                        }
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar Ingreso Almacen',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por compra modificado con éxito.'], 200);

    }

    public function destroyEntryPurchase(Entry $entry)
    {
        $begin = microtime(true);
        if ((int) $entry->company_id !== (int) TenantContext::companyId()) { abort(404); }
        $entryId = $entry->id;
        $companyId = TenantContext::companyId();
        try {
            app(EntryDeletionService::class)->delete((int) $entryId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'El ingreso o detalle no existe en la empresa actual.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors(); $first = reset($errors);
            return response()->json(['message' => $first[0] ?? 'No se pudo validar la reversión.', 'errors' => $errors], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo confirmar la eliminación. Actualice el listado antes de volver a intentarlo.'], 500);
        }
        return response()->json(['message' => 'Ingreso por compra eliminado con éxito.'], 200);
    }

    protected function syncInventoryLevelFromLots(
        int $stockItemId,
        int $warehouseId,
        int $locationId
    ): void
    {
        $companyId =
            TenantContext::companyId();

        /*
         * ============================================================
         * VALIDAR WAREHOUSE
         * ============================================================
         */

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


        /*
         * ============================================================
         * VALIDAR LOCATION
         * ============================================================
         */

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


        /*
         * ============================================================
         * SUMAR LOTES SOLO DE LA COMPANY ACTUAL
         * ============================================================
         */

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


        /*
         * ============================================================
         * INVENTORY LEVEL DE LA COMPANY ACTUAL
         * ============================================================
         */

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

    public function getJsonEntriesScrap()
    {
        /*$entries = Entry::where('entry_type', 'Retacería')
            ->orderBy('created_at', 'desc')
            ->get();*/
        $begin = microtime(true);
        $entries = Entry::with(['details' => function ($query) {
            $query->with('material')->with(['items' => function ($query) {
                //$query->where('state_item', 'scraped');
            }]);
        }])
            ->where('entry_type', 'Retacería')
            ->orderBy('created_at', 'desc')
            ->get();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener ingresos por retacería',
            'time' => $end
        ]);
        //dd(datatables($entries)->toJson());
        return datatables($entries)->toJson();
    }

    public function getEntriesPurchase()
    {
        $begin = microtime(true);

        $dateCurrent = Carbon::now('America/Lima');
        $date4MonthAgo = $dateCurrent->subMonths(2);

        $entries = Entry::with('supplier')->with(['details' => function ($query) {
            $query->with('material')->with(['items' => function ($query) {
                $query->where('state_item', 'entered')
                    ->with('typescrap')
                    ->with(['location' => function ($query) {
                        $query->with(['area', 'warehouse', 'shelf', 'level', 'container']);
                    }]);
            }]);
        }])
            ->where('entry_type', 'Por compra')
            ->where('finance', false)
            ->where('created_at', '>=', $date4MonthAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener Ingresos por Compra',
            'time' => $end
        ]);
        //dd(datatables($entries)->toJson());
        return $entries;
    }

    public function destroyDetailOfEntry($id_detail, $id_entry)
    {
        $begin = microtime(true);
        $entryId = $id_entry;
        $companyId = TenantContext::companyId();
        try {
            $deleted = app(EntryDeletionService::class)->delete((int) $entryId, (int) $id_detail);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'El ingreso o detalle no existe en la empresa actual.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors(); $first = reset($errors);
            return response()->json(['message' => $first[0] ?? 'No se pudo validar la reversión.', 'errors' => $errors], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo confirmar la eliminación. Actualice el listado antes de volver a intentarlo.'], 500);
        }
        return response()->json(['message' => $deleted['entry_deleted'] ? 'Último detalle eliminado e ingreso cerrado.' : 'Detalle de compra eliminado con éxito.',
            'entry_deleted' => $deleted['entry_deleted'], 'url' => $deleted['entry_deleted'] ? route('order.purchase.list.indexV2') : null], 200);
    }


    public function addDetailOfEntryO( Request $request, $id_entry )
    {
        $begin = microtime(true);
        //dump($request);
        $currency = app(SettingService::class)->get('finance.base_currency');

        DB::beginTransaction();
        try {
            $entry = Entry::find($id_entry);

            $items = json_decode($request->get('items'));

            // Agrupar por (material_id + date_vence)
            $grouped = []; // $grouped[materialId][dateKey] = qty
            foreach ($items as $it) {
                $materialId = (int)$it->id_material;

                $dateStr = isset($it->date_vence) ? trim((string)$it->date_vence) : '';
                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;

                if (!isset($grouped[$materialId])) $grouped[$materialId] = [];
                if (!isset($grouped[$materialId][$dateKey])) $grouped[$materialId][$dateKey] = 0;

                $grouped[$materialId][$dateKey] += (float)($it->quantity ?? 1);
            }

            // Mapa: detail_entry por (material + fecha)
            $detailEntryMap = [];

            // 1) Crear DetailEntry por grupo y sumar stock
            foreach ($grouped as $id_material => $dates) {

                $material = Material::find($id_material);
                if (!$material) {
                    throw new \Exception("Material no encontrado: " . $id_material);
                }

                foreach ($dates as $dateKey => $count) {

                    $date = ($dateKey === '__NULL__')
                        ? null
                        : Carbon::createFromFormat('d/m/Y', $dateKey);

                    $material->stock_current = $material->stock_current + $count;

                    $detail_entry = DetailEntry::create([
                        'entry_id' => $entry->id,
                        'material_id' => $id_material,
                        'ordered_quantity' => $count,
                        'entered_quantity' => $count,
                        'date_vence' => $date
                    ]);

                    $detailEntryMap[$id_material][$dateKey] = $detail_entry;

                    if ($material->perecible == 's') {
                        MaterialVencimiento::firstOrCreate([
                            'material_id' => $id_material,
                            'fecha_vencimiento' => $date
                        ]);
                    }

                    // FollowMaterial (igual que antes)
                    $follows = FollowMaterial::where('material_id', $id_material)->get();
                    if (!$follows->isEmpty()) {

                        $notification = Notification::create([
                            'content' => 'El material ' . $detail_entry->material->full_description . ' ha sido ingresado.',
                            'reason_for_creation' => 'follow_material',
                            'user_id' => Auth::user()->id,
                            'url_go' => route('follow.index')
                        ]);

                        $users = User::role(['admin', 'owner'])->get();
                        foreach ($users as $user) {
                            $followUsers = FollowMaterial::where('material_id', $detail_entry->material_id)
                                ->where('user_id', $user->id)
                                ->get();

                            if (!$followUsers->isEmpty()) {
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

                        foreach ($follows as $follow) {
                            $follow->state = 'in_warehouse';
                            $follow->save();
                        }
                    }
                }

                $material->save();
            }

            // 2) Crear Items y acumular total_detail POR detail_entry
            $totalByDetailEntryId = [];

            for ($i = 0; $i < sizeof($items); $i++) {

                $materialId = (int)$items[$i]->id_material;
                $dateStr = isset($items[$i]->date_vence) ? trim((string)$items[$i]->date_vence) : '';
                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;

                if (!isset($detailEntryMap[$materialId][$dateKey])) {
                    continue;
                }

                $detail_entry = $detailEntryMap[$materialId][$dateKey];

                if (!isset($totalByDetailEntryId[$detail_entry->id])) {
                    $totalByDetailEntryId[$detail_entry->id] = 0;
                }

                // OJO: en este loop $material debe ser el del detail_entry
                $material = $detail_entry->material;

                // ---- TU LÓGICA ORIGINAL (misma estructura) ----
                if ($currency === 'USD') {

                    if ($entry->currency_invoice === 'PEN') {

                        // Se mantiene por si lo usas para referencia
                        $precio1 = round((float)$items[$i]->price, 2) / (float)$entry->currency_compra;

                        // ✅ Ya no actualizamos Material->unit_price por "máximo"
                        $detail_entry->unit_price = round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        $priceForItem = round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }

                    } else {

                        // ✅ Ya no actualizamos Material->unit_price por "máximo"
                        $detail_entry->unit_price = (float)round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        $priceForItem = (float)round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }
                    }

                } elseif ($currency === 'PEN') {

                    if ($entry->currency_invoice === 'USD') {

                        // Se mantiene por si lo usas para referencia
                        $precio1 = round((float)$items[$i]->price, 2) * (float)$entry->currency_venta;

                        // ✅ Ya no actualizamos Material->unit_price por "máximo"
                        $detail_entry->unit_price = round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        $priceForItem = round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }

                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }

                    } else {

                        // ✅ Ya no actualizamos Material->unit_price por "máximo"
                        $detail_entry->unit_price = (float)round((float)$items[$i]->price, 2);
                        $detail_entry->save();

                        $priceForItem = (float)round((float)$items[$i]->price, 2);

                        if (isset($detail_entry->material->typeScrap)) {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => (float)$detail_entry->material->typeScrap->length,
                                    'width' => (float)$detail_entry->material->typeScrap->width,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'typescrap_id' => $detail_entry->material->typeScrap->id,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        } else {
                            if ($material->tipo_venta_id == 3) {
                                Item::create([
                                    'detail_entry_id' => $detail_entry->id,
                                    'material_id' => $detail_entry->material_id,
                                    'code' => $items[$i]->item,
                                    'length' => 0,
                                    'width' => 0,
                                    'weight' => 0,
                                    'price' => $priceForItem,
                                    'percentage' => 1,
                                    'location_id' => $items[$i]->id_location,
                                    'state' => $items[$i]->state,
                                    'state_item' => 'entered'
                                ]);
                            }
                            $totalByDetailEntryId[$detail_entry->id] += (float)$items[$i]->price;
                        }
                    }
                }
            }

            // Guardar total_detail por detail_entry
            foreach ($totalByDetailEntryId as $detailEntryId => $total) {
                DetailEntry::where('id', $detailEntryId)->update([
                    'total_detail' => round($total, 2)
                ]);
            }

            $credit = SupplierCredit::where('entry_id', $entry->id)->first();

            if (isset($credit)) {
                $credit->total_soles = ($entry->currency_invoice == 'PEN') ? (float)$entry->total : null;
                $credit->total_dollars = ($entry->currency_invoice == 'USD') ? (float)$entry->total : null;
                $credit->save();
            }

            // ===========================
            // ✅ ACTUALIZAR materials.unit_price con costo promedio (KARDEX)
            // ===========================
            $entryDate = $entry->date_entry instanceof Carbon
                ? $entry->date_entry
                : Carbon::parse($entry->date_entry);

            $affectedMaterialIds = array_map('intval', array_keys($grouped));
            $affectedMaterialIds = array_values(array_unique($affectedMaterialIds));

            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($affectedMaterialIds, $entryDate);

            foreach ($affectedMaterialIds as $mid) {
                $avg = (float) ($avgCosts[$mid] ?? 0.0);

                // Si prefieres no setear 0 cuando aún no hay historial, descomenta:
                // if ($avg <= 0) continue;

                Material::where('id', $mid)->update([
                    'unit_price' => $avg
                ]);
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Agregar detalle de ingreso',
                'time' => $end
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // TODO: Logica para actualizar
        $entry = Entry::find($id_entry);
        $orderPurchase = OrderPurchase::where('code', $entry->purchase_order)->first();

        if (!is_null($orderPurchase)) {
            $entradas = Entry::where('purchase_order', $orderPurchase->code)->get();

            if (count($entradas) > 0) {
                $details = OrderPurchaseDetail::where('order_purchase_id', $orderPurchase->id)->get();

                if (isset($details)) {
                    $flag = 1;
                    foreach ($details as $detail) {
                        $material = $detail->material_id;
                        // TODO: obtener las entradas de esa orden y material
                        $cant_material = 0;
                        foreach ($entradas as $entrada) {
                            $entry_details_sum = DetailEntry::where('entry_id', $entrada->id)
                                ->where('material_id', $material)->sum('entered_quantity');
                            $cant_material += $entry_details_sum;
                        }

                        if ($cant_material < $detail->quantity) {
                            $flag = 0;
                        }
                    }
                    if ($flag == 0) {
                        $orderPurchase->state = 0;
                        $orderPurchase->save();
                    } else {
                        $orderPurchase->state = 1;
                        $orderPurchase->save();
                    }

                } else {
                    $orderPurchase->state = 2;
                    $orderPurchase->save();
                }

            } else {
                $orderPurchase->state = 2;
                $orderPurchase->save();
            }
        }

        return response()->json(['message' => 'Detalles de compra guardados con éxito.'], 200);
    }

    public function addDetailOfEntry(Request $request, $id_entry)
    {
        $begin = microtime(true);
        $currency = app(SettingService::class)->get('finance.base_currency');

        DB::beginTransaction();
        try {
            $entry = Entry::query()
                ->where('id', $id_entry)
                ->where(
                    'company_id',
                    TenantContext::companyId()
                )
                ->lockForUpdate()
                ->firstOrFail();
            if ($entry->order_purchase_id || trim((string) $entry->purchase_order) !== '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'entry_id' => 'Este ingreso pertenece a una orden. Reciba las cantidades pendientes desde Órdenes para almacén; no se pueden agregar productos ni modificarlo por esta vía.',
                ]);
            }


            $items = app(\App\Services\Inventory\EntryDestinationValidator::class)->normalize(
                $request->get('items'),
                $request->get('entry_context_company_id'),
                $request->get('entry_context_branch_id')
            );

            /**
             * Agrupar por:
             * stock_item_id + warehouse/location + lote + fecha_vencimiento + price
             */
            $grouped = [];

            foreach ($items as $it) {
                $materialId  = (int) ($it->id_material ?? 0);
                $stockItemId = (int) ($it->stock_item_id ?? 0);
                $locationId = (int) $it->id_location;
                $warehouseId = (int) $it->warehouse_id;

                $lotCode = isset($it->material_lote) ? trim((string) $it->material_lote) : '';
                $dateStr = isset($it->date_vence) ? trim((string) $it->date_vence) : '';
                $unitPrice = (float) ($it->price ?? 0);

                if (!$stockItemId) {
                    throw new \Exception("Uno de los items no tiene stock_item_id.");
                }

                if (!$materialId) {
                    throw new \Exception("Uno de los items no tiene id_material.");
                }

                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;
                $lotKey  = ($lotCode === '') ? '__NOLOT__' : $lotCode;

                $groupKey = implode('|', [
                    $stockItemId,
                    $warehouseId ?: 'null',
                    $lotKey,
                    $dateKey,
                    number_format($unitPrice, 4, '.', '')
                ]);

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'material_id'   => $materialId,
                        'stock_item_id' => $stockItemId,
                        'location_id'   => $locationId,
                        'warehouse_id'  => $warehouseId,
                        'lot_code'      => $lotCode,
                        'date_vence'    => $dateStr,
                        'unit_price'    => $unitPrice,
                        'count'         => 0,
                    ];
                }

                $grouped[$groupKey]['count'] += (float) ($it->quantity ?? 1);
            }

            $detailEntryMap = [];
            $stockLotMap = [];

            /**
             * 1) Crear DetailEntry por grupo
             *    + inventory_levels
             *    + stock_lots
             */
            foreach ($grouped as $groupKey => $group) {
                $material = Material::find($group['material_id']);
                if (!$material) {
                    throw new \Exception("Material no encontrado: " . $group['material_id']);
                }

                $stockItem = StockItem::with('material')->find($group['stock_item_id']);
                if (!$stockItem) {
                    throw new \Exception("StockItem no encontrado: " . $group['stock_item_id']);
                }

                $enabledForCompany = CompanyStockItem::query()
                    ->where(
                        'company_id',
                        $entry->company_id
                    )
                    ->where(
                        'stock_item_id',
                        $stockItem->id
                    )
                    ->where('is_active', true)
                    ->exists();

                if (!$enabledForCompany) {
                    throw new \RuntimeException(
                        'El producto no está habilitado para la empresa del ingreso.'
                    );
                }

                if ((int) $stockItem->material_id !== (int) $material->id) {
                    throw new \RuntimeException(
                        'El StockItem no corresponde al material indicado.'
                    );
                }

                $date = null;
                if (!empty($group['date_vence']) && $group['date_vence'] !== '__NULL__') {
                    $date = Carbon::createFromFormat('d/m/Y', $group['date_vence']);
                }

                $detailEntry = DetailEntry::create([
                    'company_id' => $entry->company_id,
                    'entry_id'          => $entry->id,
                    'material_id'       => $group['material_id'], // compatibilidad temporal
                    'ordered_quantity'  => $group['count'],
                    'entered_quantity'  => $group['count'],
                    'unit_price'        => round((float) $group['unit_price'], 2),
                    'date_vence'        => $date,
                    'stock_item_id'     => $group['stock_item_id'],
                ]);

                $detailEntryMap[$groupKey] = $detailEntry;

                if ($material->perecible == 's' && $date) {
                    MaterialVencimiento::firstOrCreate([
                        'material_id' => $group['material_id'],
                        'fecha_vencimiento' => $date
                    ]);
                }

                $this->increaseInventoryLevel(
                    $group['stock_item_id'],
                    $group['location_id'],
                    $group['warehouse_id'],
                    $group['count'],
                    $group['unit_price']
                );

                $stockLot = $this->increaseStockLot(
                    $group['stock_item_id'],
                    $group['location_id'],
                    $group['warehouse_id'],
                    $detailEntry->id,
                    $group['lot_code'] ?? null,
                    !empty($group['date_vence']) ? $date : null,
                    $group['count'],
                    $group['unit_price']
                );

                $stockLotMap[$groupKey] = $stockLot;

                $follows = FollowMaterial::where('material_id', $group['material_id'])->get();
                if (!$follows->isEmpty()) {

                    $notification = Notification::create([
                        'content' => 'El material ' . $detailEntry->material->full_description . ' ha sido ingresado.',
                        'reason_for_creation' => 'follow_material',
                        'user_id' => Auth::user()->id,
                        'url_go' => route('follow.index')
                    ]);

                    $users = User::role(['admin', 'owner'])->get();
                    foreach ($users as $user) {
                        $followUsers = FollowMaterial::where('material_id', $detailEntry->material_id)
                            ->where('user_id', $user->id)
                            ->get();

                        if (!$followUsers->isEmpty()) {
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

                    foreach ($follows as $follow) {
                        $follow->state = 'in_warehouse';
                        $follow->save();
                    }
                }
            }

            /**
             * 2) Crear Items y acumular total_detail
             */
            $totalByDetailEntryId = [];

            foreach ($items as $it) {

                $materialId  = (int) ($it->id_material ?? 0);
                $stockItemId = (int) ($it->stock_item_id ?? 0);
                $locationId = (int) $it->id_location;
                $warehouseId = (int) $it->warehouse_id;

                $lotCode = isset($it->material_lote) ? trim((string) $it->material_lote) : '';
                $dateStr = isset($it->date_vence) ? trim((string) $it->date_vence) : '';
                $unitPrice = (float) ($it->price ?? 0);

                $dateKey = ($dateStr === '') ? '__NULL__' : $dateStr;
                $lotKey  = ($lotCode === '') ? '__NOLOT__' : $lotCode;

                $groupKey = implode('|', [
                    $stockItemId,
                    $warehouseId ?: 'null',
                    $lotKey,
                    $dateKey,
                    number_format($unitPrice, 4, '.', '')
                ]);

                if (!isset($detailEntryMap[$groupKey])) {
                    continue;
                }

                $detailEntry = $detailEntryMap[$groupKey];
                $stockLot = $stockLotMap[$groupKey] ?? null;
                $material = $detailEntry->material;

                if (!isset($totalByDetailEntryId[$detailEntry->id])) {
                    $totalByDetailEntryId[$detailEntry->id] = 0;
                }

                if ($currency === 'USD') {
                    $detailEntry->unit_price = round((float)$it->price, 2);
                    $detailEntry->save();
                    $priceForItem = round((float)$it->price, 2);
                } elseif ($currency === 'PEN') {
                    $detailEntry->unit_price = round((float)$it->price, 2);
                    $detailEntry->save();
                    $priceForItem = round((float)$it->price, 2);
                } else {
                    $priceForItem = round((float)$it->price, 2);
                }

                if ((int) $material->tipo_venta_id === 3) {

                    if (!$stockLot) {
                        throw new \RuntimeException(
                            'No se pudo determinar el lote asociado al ítem.'
                        );
                    }

                    if (isset($material->typeScrap)) {
                        Item::create([
                            'company_id' => $stockLot->company_id,

                            'detail_entry_id' => $detailEntry->id,
                            'stock_item_id'   => $detailEntry->stock_item_id,
                            'stock_lot_id'    => $stockLot ? $stockLot->id : null,
                            'material_id'     => $detailEntry->material_id,
                            'code'            => $it->item ?? null,
                            'length'          => (float) $material->typeScrap->length,
                            'width'           => (float) $material->typeScrap->width,
                            'weight'          => 0,
                            'price'           => $priceForItem,
                            'unit_cost'       => (float) $detailEntry->unit_price,
                            'percentage'      => 1,
                            'typescrap_id'    => $material->typeScrap->id,
                            'warehouse_id'    => $stockLot->warehouse_id,
                            'location_id'     => $stockLot->location_id,
                            'state'           => $it->state ?? 'good',
                            'state_item'      => 'entered'
                        ]);
                    } else {
                        Item::create([
                            'company_id' => $stockLot->company_id,

                            'detail_entry_id' => $detailEntry->id,
                            'stock_item_id'   => $detailEntry->stock_item_id,
                            'stock_lot_id'    => $stockLot ? $stockLot->id : null,
                            'material_id'     => $detailEntry->material_id,
                            'code'            => $it->item ?? null,
                            'length'          => 0,
                            'width'           => 0,
                            'weight'          => 0,
                            'price'           => $priceForItem,
                            'unit_cost'       => (float) $detailEntry->unit_price,
                            'percentage'      => 1,
                            'warehouse_id'    => $stockLot->warehouse_id,
                            'location_id'     => $stockLot->location_id,
                            'state'           => $it->state ?? 'good',
                            'state_item'      => 'entered'
                        ]);
                    }
                }

                $totalByDetailEntryId[$detailEntry->id] += (float) $it->price;
            }

            /**
             * 3) Guardar total_detail
             */
            foreach ($totalByDetailEntryId as $detailEntryId => $total) {
                DetailEntry::query()
                    ->where(
                        'company_id',
                        $entry->company_id
                    )
                    ->where(
                        'id',
                        $detailEntryId
                    )
                    ->update([
                        'total_detail' =>
                            round($total, 2),
                    ]);
            }

            $credit = SupplierCredit::where('entry_id', $entry->id)->first();

            if (isset($credit)) {
                $credit->total_soles = ($entry->currency_invoice == 'PEN') ? (float)$entry->total : null;
                $credit->total_dollars = ($entry->currency_invoice == 'USD') ? (float)$entry->total : null;
                $credit->save();
            }

            /**
             * 4) Recalcular costo promedio material padre
             */
            $entryDate = $entry->date_entry instanceof Carbon
                ? $entry->date_entry
                : Carbon::parse($entry->date_entry);

            $affectedStockItemIds = collect($grouped)
                ->pluck('stock_item_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values()
                ->toArray();

            $avgCosts =
                $this->inventoryCostService
                    ->getAverageCostsByStockItem(
                        $affectedStockItemIds,
                        $entryDate
                    );

            foreach ($affectedStockItemIds as $stockItemId) {
                $avg = (float) ($avgCosts[$stockItemId] ?? 0.0);

                InventoryLevel::query()
                    ->where(
                        'company_id',
                        $entry->company_id
                    )
                    ->where(
                        'stock_item_id',
                        $stockItemId
                    )
                    ->update([
                        'average_cost' => $avg,
                    ]);
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Agregar detalle de ingreso',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();
            $first = reset($errors);
            return response()->json(['message' => $first[0] ?? 'Revise los destinos del ingreso.', 'errors' => $errors], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $entry = Entry::query()
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->where('id', $id_entry)
            ->first();

        $orderPurchase = OrderPurchase::where('code', $entry->purchase_order)->first();

        if (!is_null($orderPurchase)) {
            $entradas = Entry::query()
                ->where(
                    'company_id',
                    $entry->company_id
                )
                ->where(
                    'purchase_order',
                    $orderPurchase->code
                )
                ->get();

            if (count($entradas) > 0) {
                $details = OrderPurchaseDetail::where('order_purchase_id', $orderPurchase->id)->get();

                if (isset($details)) {
                    $flag = 1;
                    foreach ($details as $detail) {
                        $material = $detail->material_id;
                        $entryDetailsSum = 0;
                        foreach ($entradas as $entrada) {
                            $entryDetailsSum = DetailEntry::query()
                                ->where(
                                    'company_id',
                                    $entry->company_id
                                )
                                ->where(
                                    'entry_id',
                                    $entrada->id
                                )
                                ->where(
                                    'material_id',
                                    $material
                                )
                                ->sum('entered_quantity');
                        }

                        if ($entryDetailsSum < $detail->quantity) {
                            $flag = 0;
                        }
                    }
                    $orderPurchase->state = $flag == 0 ? 0 : 1;
                    $orderPurchase->save();

                } else {
                    $orderPurchase->state = 2;
                    $orderPurchase->save();
                }

            } else {
                $orderPurchase->state = 2;
                $orderPurchase->save();
            }
        }

        return response()->json(['message' => 'Detalles de compra guardados con éxito.'], 200);
    }

    public function getAllOrders()
    {
        $begin = microtime(true);

        $dateCurrent = Carbon::now('America/Lima');
        $date4MonthAgo = $dateCurrent->subMonths(5);
        $estado = 1;
        $orders = OrderPurchase::with(['supplier', 'approved_user'])
            ->orderBy('created_at', 'desc')
            ->get();
            /*->filter(function ($orden) use ($estado) {
                return $orden->getStatusAttribute() != $estado;
            });*/
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener ordenes de compra',
            'time' => $end
        ]);
        return datatables($orders)->toJson();
    }

    public function listOrderPurchase()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('entry.listOrderPurchase', compact('permissions'));

    }

    public function getAllOrdersV2(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $pageNumber = max(1, (int) $pageNumber);
        $year = $request->input('year');
        $code = $request->input('code');
        $quote = $request->input('quote');
        $supplier = $request->input('supplier');
        $state = $request->input('state');
        $deliveryDate = $request->input('deliveryDate');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ( $startDate == "" || $endDate == "" )
        {
            $query = OrderPurchase::with(['supplier', 'createdUser'])
                ->where('type', 'n')
                ->orderBy('date_order', 'desc');
        } else {
            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = OrderPurchase::with(['supplier', 'createdUser'])
                ->where('type', 'n')
                ->whereDate('date_order', '>=', $fechaInicio)
                ->whereDate('date_order', '<=', $fechaFinal)
                ->orderBy('date_order', 'desc');
        }

        if ($year != "") {
            $query->whereYear('date_order', $year);
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($quote != "") {
            $query->where('observation', 'LIKE', '%'.$quote.'%');
        }

        if ($supplier != "") {
            $query->where('supplier_id', $supplier);

        }

        if ($deliveryDate != "") {
            $fecha = Carbon::createFromFormat('d/m/Y', $deliveryDate);
            $query->whereDate('date_arrival', $fecha);
        }

        $summaries = [];
        $matchingIds = [];
        $summaryService = app(\App\Services\Purchases\NormalPurchaseReceiptSummary::class);
        if ($state !== null && $state !== '') {
            foreach ((clone $query)->pluck('id') as $orderId) {
                $receipt = $summaryService->forOrder((int) $orderId);
                $summaries[$orderId] = $receipt;
                $filterValue = $receipt['state'] === null ? 'review' : (string) $receipt['state'];
                if ($state === null || $state === '' || (string) $state === $filterValue) { $matchingIds[] = $orderId; }
            }
            $query->whereIn('id', $matchingIds);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = $totalFilteredRecords ? ($pageNumber - 1) * $perPage + 1 : 0;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $orders = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($query);

        $array = [];

        foreach ( $orders as $order )
        {
            $receipt = $summaries[$order->id] ?? $summaryService->forOrder((int) $order->id);
            $state = $receipt['state'] === null ? 'review' : (string) $receipt['state'];
            $badge = $state === '1' ? 'bg-success' : ($state === '2' ? 'bg-primary' : 'bg-warning');
            $stateText = '<span class="badge '.$badge.'">'.$receipt['state_text'].'</span>';

            $type = "";
            $typeText = "";
            if ( $order->type == 'n' ) {
                $type = 'n';
                $typeText = '<span class="badge bg-primary">Orden Normal</span>';
            } elseif ( $order->type == 'e' ){
                $type = 'e';
                $typeText = '<span class="badge bg-success">Orden Express</span>';
            }

            array_push($array, [
                "id" => $order->id,
                "year" => ( $order->date_order == null || $order->date_order == "") ? '':$order->date_order->year,
                "code" => ($order->code == null || $order->code == "") ? '': $order->code,
                "date_order" => ($order->date_order == null || $order->date_order == "") ? '': $order->date_order->format('d/m/Y'),
                "date_arrival" => ($order->date_arrival == null || $order->date_arrival == "") ? '': $order->date_arrival->format('d/m/Y'),
                "observation" => $order->observation,
                "supplier" => optional($order->supplier)->business_name ?? '',
                "created_user" => optional($order->createdUser)->name ?? 'No registrado / usuario no disponible',
                "currency" => ($order->currency_order == null || $order->currency_order == "") ? '': $order->currency_order,
                "total" => $order->total,
                "type" => $type,
                "typeText" => $typeText,
                "state" => $state,
                "stateText" => $stateText,
                "status" => $receipt['state'],
                "can_receive" => $receipt['can_receive'],
                "receipt_errors" => $receipt['errors'],
                "regularize" => $order->regularize
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

    public function listOrderPurchaseV2()
    {
        $user = Auth::user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        $registros = OrderPurchase::where('type', 'n')->whereNotNull('date_order')->get();

        $arrayYears = $registros->pluck('date_order')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arraySuppliers = Supplier::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled

        $arrayStates = [
            ["value" => "1", "display" => "COMPLETAS"],
            ["value" => "0", "display" => "INCOMPLETAS"],
            ["value" => "2", "display" => "POR INGRESAR"],
            ["value" => "review", "display" => "REQUIERE REVISIÓN"]
        ];

        return view('entry.listOrderPurchaseV2', compact('permissions', 'arrayYears', 'arraySuppliers', 'arrayStates'));

    }

    public function updateStateOrderPurchase()
    {
        abort(405, 'Los estados se consultan desde las recepciones; no se actualizan mediante esta ruta GET.');
    }

    public function createEntryOrder($id)
    {
        $orderPurchase = OrderPurchase::query()->where('type', 'n')->with(['supplier', 'details.stockItem', 'details.material'])->findOrFail($id);
        $receiptSummary = app(NormalPurchaseReceiptSummary::class)->forOrder((int) $orderPurchase->id);
        $warehouseContext = app(EntryWarehouseResolver::class)->optionsForCurrentContext();
        $receiptKey = (string) Str::uuid();
        return view('entry.create_entry_purchase_order', compact('orderPurchase', 'receiptSummary', 'warehouseContext', 'receiptKey'));
    }

    public function storeEntryPurchaseOrder(StoreEntryPurchaseOrderRequest $request)
    {
        $request->validated();
        $request->validate([
            'purchase_order_id' => 'required|integer|min:1',
            'receipt_key' => 'required|uuid',
            'date_invoice' => 'required|date_format:d/m/Y',
            'entry_context_tenant_id' => 'required|integer',
            'entry_context_company_id' => 'required|integer',
            'entry_context_branch_id' => 'required|integer',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'imageOb' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'observation' => 'nullable|string|max:2000',
        ]);
        if ((int) $request->input('entry_context_tenant_id') !== TenantContext::tenantId()
            || (int) $request->input('entry_context_company_id') !== TenantContext::companyId()
            || (int) $request->input('entry_context_branch_id') !== TenantContext::branchId()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Cambió la empresa o el local. Abra nuevamente la recepción.']);
        }
        $fingerprintData = $request->only(['purchase_order_id', 'date_invoice', 'invoice', 'referral_guide',
            'deferred_invoice', 'observation', 'items', 'entry_context_tenant_id', 'entry_context_company_id', 'entry_context_branch_id']);
        foreach (['image', 'imageOb'] as $field) {
            $fingerprintData[$field] = $request->file($field) ? hash_file('sha256', $request->file($field)->getRealPath()) : null;
        }
        $fingerprint = hash('sha256', json_encode($fingerprintData));
        $writtenPaths = [];
        try {
            $saved = DB::transaction(function () use ($request, $fingerprint, &$writtenPaths) {
                $order = OrderPurchase::query()->where('type', 'n')->lockForUpdate()->findOrFail($request->input('purchase_order_id'));
                $existing = Entry::withTrashed()->where('tenant_id', TenantContext::tenantId())
                    ->where('company_id', TenantContext::companyId())->where('purchase_receipt_key', $request->input('receipt_key'))->first();
                if ($existing) {
                    if ((int) $existing->order_purchase_id !== (int) $order->id || $existing->trashed() || $existing->state_annulled) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Esta recepción ya fue procesada y requiere revisión.']);
                    }
                    if (!hash_equals((string) $existing->purchase_receipt_hash, $fingerprint)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Esta recepción ya fue guardada con otros datos. Revise el ingreso registrado y abra otra recepción para lo pendiente.']);
                    }
                    return ['id' => $existing->id, 'replayed' => true];
                }
                $lines = OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $summaryService = app(NormalPurchaseReceiptSummary::class);
                $summary = $summaryService->forOrder((int) $order->id);
                $items = app(\App\Services\Purchases\NormalPurchaseReceiptInput::class)->normalize($request->input('items'), $summary);
                $items = app(\App\Services\Inventory\EntryDestinationValidator::class)->normalize(
                    json_encode($items), $request->input('entry_context_company_id'), $request->input('entry_context_branch_id')
                );
                if (!in_array($order->currency_order, ['PEN', 'USD'], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'La moneda de la orden debe ser PEN o USD.']);
                }
                if (trim((string) $order->code) === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'La orden requiere un código válido antes de recibirla.']);
                }
                $supplier = Supplier::query()->where('tenant_id', TenantContext::tenantId())->findOrFail($order->supplier_id);
                // Todas las comprobaciones de producto y cantidad preceden a las escrituras de stock.
                foreach ($items as $item) {
                    $line = $lines->get($item->order_purchase_detail_id);
                    $stock = StockItem::query()->where('is_active', true)->findOrFail($line->stock_item_id);
                    $material = Material::query()->findOrFail($stock->material_id);
                    if ((int) $material->id !== (int) $line->material_id
                        || !CompanyStockItem::query()->where('company_id', TenantContext::companyId())->where('stock_item_id', $stock->id)->where('is_active', true)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Un producto no corresponde a la orden o no está habilitado en la empresa.']);
                    }
                    if (!is_numeric($line->price) || !is_finite((float) $line->price) || (float) $line->price < 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'La orden tiene un precio inválido.']);
                    }
                    if ((int) $material->tipo_venta_id === 3 && floor($item->quantity) != $item->quantity) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Un producto controlado por unidades requiere cantidades enteras.']);
                    }
                    if (strlen($item->material_lote) > 255) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'El lote es demasiado largo.']);
                    }
                    if ($item->date_vence !== '') {
                        $v = \Illuminate\Support\Facades\Validator::make(['date' => $item->date_vence], ['date' => 'required|date_format:Y-m-d']);
                        $v->validate();
                    }
                    $item->material = $material;
                    $item->stock = $stock;
                    $item->line = $line;
                }
                $entry = new Entry();
                $entry->fill([
                    'tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId(),
                    'purchase_order' => $order->code, 'supplier_id' => $supplier->id,
                    'invoice' => $request->input('invoice'), 'referral_guide' => $request->input('referral_guide'),
                    'date_entry' => Carbon::createFromFormat('d/m/Y', $request->input('date_invoice'))->startOfDay(),
                    'deferred_invoice' => $request->boolean('deferred_invoice') ? 'on' : 'off',
                    'currency_invoice' => $order->currency_order, 'currency_compra' => $order->currency_compra,
                    'currency_venta' => $order->currency_venta, 'entry_type' => 'Por compra', 'finance' => false,
                    'observation' => $request->input('observation'), 'image' => 'no_image.png', 'imageOb' => 'no_image.png',
                ]);
                $entry->order_purchase_id = $order->id;
                $entry->purchase_receipt_key = $request->input('receipt_key');
                $entry->purchase_receipt_hash = $fingerprint;
                $entry->save();
                foreach (['image' => '/images/entries/', 'imageOb' => '/images/entries/observations/'] as $field => $folder) {
                    if (!$request->file($field)) { continue; }
                    $file = $request->file($field);
                    $pdf = strtolower($file->getClientOriginalExtension()) === 'pdf';
                    $name = ($pdf ? 'pdf' : '').$entry->id.($pdf ? '.pdf' : '.JPG');
                    $destination = public_path().$folder;
                    $path = $destination.$name;
                    if (!is_dir($destination) || file_exists($path)) { throw new \RuntimeException('No se puede guardar el archivo del ingreso.'); }
                    $writtenPaths[] = $path;
                    if ($pdf) { $file->move($destination, $name); }
                    else { Image::make($file)->orientate()->save($path, 80, 'JPG'); }
                    $entry->$field = $name;
                }
                $entry->save();
                $total = 0; $stockIds = [];
                foreach ($items as $item) {
                    $line = $item->line; $material = $item->material;
                    $quantity = (float) $item->quantity; $price = (float) $line->price;
                    $date = $item->date_vence === '' ? null : Carbon::createFromFormat('Y-m-d', $item->date_vence)->startOfDay();
                    $detail = new DetailEntry();
                    $detail->fill([
                        'tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId(),
                        'entry_id' => $entry->id, 'material_id' => $line->material_id, 'stock_item_id' => $line->stock_item_id,
                        'ordered_quantity' => $line->quantity, 'entered_quantity' => $quantity,
                        'unit_price' => $price, 'total_detail' => round($quantity * $price, 2), 'date_vence' => $date,
                    ]);
                    $detail->order_purchase_detail_id = $line->id;
                    $detail->save();
                    $this->increaseInventoryLevel((int) $line->stock_item_id, (int) $item->id_location, (int) $item->warehouse_id, $quantity, $price);
                    $lot = $this->increaseStockLot((int) $line->stock_item_id, (int) $item->id_location, (int) $item->warehouse_id,
                        (int) $detail->id, $item->material_lote ?: null, $date, $quantity, $price);
                    if ($material->perecible === 's' && $date) {
                        MaterialVencimiento::firstOrCreate(['material_id' => $material->id, 'fecha_vencimiento' => $date]);
                    }
                    if ((int) $material->tipo_venta_id === 3) {
                        if (!$lot) { throw new \RuntimeException('No se pudo registrar el lote.'); }
                        for ($unit = 0; $unit < (int) $quantity; $unit++) {
                            $scrap = $material->typeScrap;
                            Item::create([
                                'tenant_id' => TenantContext::tenantId(), 'company_id' => TenantContext::companyId(),
                                'detail_entry_id' => $detail->id, 'stock_item_id' => $line->stock_item_id, 'stock_lot_id' => $lot->id,
                                'material_id' => $line->material_id, 'code' => 'RC-'.$entry->id.'-'.$detail->id.'-'.($unit + 1),
                                'length' => $scrap ? $scrap->length : 0, 'width' => $scrap ? $scrap->width : 0, 'weight' => 0,
                                'price' => $price, 'unit_cost' => $price, 'percentage' => 1,
                                'typescrap_id' => $scrap ? $scrap->id : null,
                                'warehouse_id' => $item->warehouse_id, 'location_id' => $item->id_location,
                                'state' => 'good', 'state_item' => 'entered',
                            ]);
                        }
                    }
                    $total += (float) $detail->total_detail;
                    $stockIds[] = (int) $line->stock_item_id;
                }
                $after = $summaryService->forOrder((int) $order->id);
                if ($after['errors']) { throw new \RuntimeException(implode(' ', $after['errors'])); }
                foreach ($after['lines'] as $lineSummary) {
                    $tracking = MaterialOrder::query()->where('order_purchase_detail_id', $lineSummary['order_purchase_detail_id'])->lockForUpdate()->get();
                    if ($tracking->count() > 1) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Una línea tiene seguimientos duplicados y requiere conciliación.']);
                    }
                    if ($tracking->isEmpty()) {
                        MaterialOrder::create(['order_purchase_detail_id' => $lineSummary['order_purchase_detail_id'],
                            'material_id' => $lineSummary['material_id'], 'stock_item_id' => $lineSummary['stock_item_id'],
                            'quantity_request' => $lineSummary['ordered'], 'quantity_entered' => $lineSummary['received']]);
                    } else {
                        $row = $tracking->first();
                        if ((int) $row->material_id !== $lineSummary['material_id'] || (string) $row->stock_item_id !== (string) $lineSummary['stock_item_id']) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'El seguimiento no corresponde al producto de la línea.']);
                        }
                        $row->quantity_entered = $lineSummary['received']; $row->save();
                    }
                    DetailEntry::query()->where('entry_id', $entry->id)->where('company_id', TenantContext::companyId())
                        ->where('order_purchase_detail_id', $lineSummary['order_purchase_detail_id'])
                        ->update(['isComplete' => $lineSummary['pending'] === '0.00']);
                }
                $order->state = $after['state'];
                if ($after['state'] === 1) { $order->status_order = 'pick_up'; }
                $order->save();
                $stockIds = array_values(array_unique($stockIds));
                $costs = $this->inventoryCostService->getAverageCostsByStockItem($stockIds, $entry->date_entry);
                foreach ($stockIds as $stockId) {
                    InventoryLevel::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                        ->where('stock_item_id', $stockId)->update(['average_cost' => (float) ($costs[$stockId] ?? 0)]);
                }
                $users = app(\App\Services\Purchases\NormalPurchaseGuard::class)->users()->get();
                foreach (array_unique(array_map(function ($item) { return $item->id_material; }, $items)) as $materialId) {
                    $followers = FollowMaterial::query()->where('material_id', $materialId)->whereIn('user_id', $users->pluck('id'))->get();
                    if ($followers->isEmpty()) { continue; }
                    $material = Material::findOrFail($materialId);
                    $notification = Notification::create(['content' => 'El material '.$material->full_description.' ha sido ingresado.',
                        'reason_for_creation' => 'follow_material', 'user_id' => Auth::id(), 'url_go' => route('follow.index')]);
                    foreach ($users->whereIn('id', $followers->pluck('user_id')->all()) as $user) {
                        foreach ($user->roles as $role) {
                            NotificationUser::create(['notification_id' => $notification->id, 'role_id' => $role->id, 'user_id' => $user->id,
                                'read' => false, 'date_read' => null, 'date_delete' => null]);
                        }
                    }
                    foreach ($followers as $follow) { $follow->state = 'in_warehouse'; $follow->save(); }
                }
                $deadline = $order->deadline;
                if ($deadline && $deadline->credit) {
                    $expiration = $entry->date_entry->copy()->addDays($deadline->days);
                    SupplierCredit::create([
                        'supplier_id' => $supplier->id, 'entry_id' => $entry->id, 'order_purchase_id' => $order->id,
                        'invoice' => $this->onlyZeros($entry->invoice) ? null : $entry->invoice, 'image_invoice' => $entry->image,
                        'total_soles' => $order->currency_order === 'PEN' ? round($total, 2) : null,
                        'total_dollars' => $order->currency_order === 'USD' ? round($total, 2) : null,
                        'date_issue' => $entry->date_entry, 'date_expiration' => $expiration,
                        'days_to_expiration' => $expiration->diffInDays(Carbon::now('America/Lima')),
                        'code_order' => $order->code, 'payment_deadline_id' => $deadline->id, 'state_credit' => 'outstanding',
                    ]);
                }
                return ['id' => $entry->id, 'replayed' => false];
            });
        } catch (\Throwable $exception) {
            foreach ($writtenPaths as $path) { if (is_file($path)) { @unlink($path); } }
            throw $exception;
        }
        return response()->json(['message' => $saved['replayed'] ? 'Esta recepción ya estaba guardada.' : 'Ingreso guardado correctamente.',
            'entry_id' => $saved['id'], 'url' => route('order.purchase.list.indexV2')]);
    }

    function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getOrderPurchaseComplete($order)
    {
        $entry = Entry::where('purchase_order', $order)
            ->get();

        $order_purchase = OrderPurchase::where('code', $order)->first();

        if ( count($entry) > 0 )
        {
            $details = OrderPurchaseDetail::where('order_purchase_id', $order_purchase->id)->get();

            if (isset($details))
            {
                foreach ($details as $detail)
                {
                    $material = $detail->material_id;
                    // TODO: obtener las entradas de esa orden y material
                    $cant_material = 0;
                    foreach ( $entry as $entrada )
                    {
                        $entry_details_sum = DetailEntry::where('entry_id', $entrada->id)
                            ->where('material_id', $material)->sum('entered_quantity');
                        $cant_material += $entry_details_sum;
                    }


                    if ($cant_material < $detail->quantity)
                    {
                        // TODO: Esto significa que esta incompleta
                        return 0;
                    }
                }
                // TODO: Esto significa que esta completa
                return 1;
            }
            // TODO: Esto significa que esta por ingresar
            return 2;
        }
        // TODO: Esto significa que esta por ingresar
        return 2;
        /*if ( isset($order) )
        {
            $details = DetailEntry::where('entry_id', $order->id)->get();
            if (isset($details))
            {
                foreach ($details as $detail)
                {
                    if ($detail->isComplete == false)
                    {
                        return 0;
                    }
                }
                return 1;
            }
            return 2;
        }
        return 2;*/

    }

    public function regularizeAutoOrderEntryPurchase( $entry_id )
    {
        $begin = microtime(true);
        $entry = Entry::find($entry_id);
        $details = DetailEntry::where('entry_id', $entry_id)->get();
        //dd($entry);

        $suppliers = Supplier::all();
        $quotesRaised = Quote::where('raise_status', 1)
            ->where('state_active', 'open')->get();

        $users = User::all();

        // TODO: WITH TRASHED
        $maxCode = OrderPurchase::withTrashed()->max('id');
        $maxId = $maxCode + 1;
        //$maxCode = OrderPurchase::max('code');
        //$maxId = (int)substr($maxCode,3) + 1;
        $length = 5;
        $codeOrder = 'OC-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'AutoRegularizar Vista de orden de compra',
            'time' => $end
        ]);
        return view('orderPurchase.regularizeEntryPurchase', compact('entry', 'details', 'suppliers', 'users', 'codeOrder', 'payment_deadlines', 'quotesRaised'));

    }

    public function regularizeEntryToOrderPurchase(StoreOrderPurchaseRequest $request)
    {
        $begin = microtime(true);
        //dd($request);
        $validated = $request->validated();

        $fecha = ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        DB::beginTransaction();
        try {
            $maxCode = OrderPurchase::withTrashed()->max('id');
            $maxId = $maxCode + 1;
            $length = 5;
            //$codeOrder = 'OC-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $orderPurchase = OrderPurchase::create([
                'code' => '',
                'quote_supplier' => $request->get('quote_supplier'),
                'payment_deadline_id' => ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null,
                'supplier_id' => ($request->has('supplier_id')) ? $request->get('supplier_id') : null,
                'date_arrival' => ($request->has('date_arrival')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_arrival')) : Carbon::now(),
                'date_order' => ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now(),
                'approved_by' => ($request->has('approved_by')) ? $request->get('approved_by') : null,
                'payment_condition' => ($request->has('purchase_condition')) ? $request->get('purchase_condition') : '',
                'currency_order' => ($request->get('state') === 'true') ? 'PEN': 'USD',
                'currency_compra' => $tipoCambioSunat->precioCompra,
                'currency_venta' => $tipoCambioSunat->precioVenta,
                'observation' => $request->get('observation'),
                'igv' => $request->get('taxes_send'),
                'total' => $request->get('total_send'),
                'type' => 'n',
                'regularize' => ($request->get('regularize') === 'true') ? 'r': 'nr',
                'status_order' => 'pick_up',
                'quote_id' => ($request->has('quote_id')) ? $request->get('quote_id') : null,

            ]);

            $codeOrder = '';
            if ( $maxId < $orderPurchase->id ){
                $codeOrder = 'OC-'.str_pad($orderPurchase->id,$length,"0", STR_PAD_LEFT);
                $orderPurchase->code = $codeOrder;
                $orderPurchase->save();
            } else {
                $codeOrder = 'OC-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $orderPurchase->code = $codeOrder;
                $orderPurchase->save();
            }

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $orderPurchaseDetail = OrderPurchaseDetail::create([
                    'order_purchase_id' => $orderPurchase->id,
                    'material_id' => $items[$i]->id_material,
                    'quantity' => (float) $items[$i]->quantity,
                    'price' => (float) $items[$i]->price,
                    'total_detail' => (float) $items[$i]->total,
                ]);

                // TODO: Revisamos si hay un material en seguimiento y creamos
                // TODO: la notificacion y cambiamos el estado
                $follows = FollowMaterial::where('material_id', $orderPurchaseDetail->material_id)
                    ->get();
                if ( !$follows->isEmpty() )
                {
                    // TODO: Creamos notificacion y cambiamos el estado
                    // Crear notificacion
                    $notification = Notification::create([
                        'content' => 'El material ' . $orderPurchaseDetail->material->full_description . ' ha sido pedido.',
                        'reason_for_creation' => 'follow_material',
                        'user_id' => Auth::user()->id,
                        'url_go' => route('follow.index')
                    ]);

                    // Roles adecuados para recibir esta notificación admin, logistica
                    $users = User::role(['admin', 'operator'])->get();
                    foreach ( $users as $user )
                    {
                        $followUsers = FollowMaterial::where('material_id', $orderPurchaseDetail->material_id)
                            ->where('user_id', $user->id)
                            ->get();
                        if ( !$followUsers->isEmpty() )
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
                    foreach ( $follows as $follow )
                    {
                        $follow->state = 'in_order';
                        $follow->save();
                    }
                }

                $total = $orderPurchaseDetail->total_detail;
                $subtotal = $total / 1.18;
                $igv = $total - $subtotal;
                $orderPurchaseDetail->igv = $igv;
                $orderPurchaseDetail->save();

                MaterialOrder::create([
                    'order_purchase_detail_id' => $orderPurchaseDetail->id,
                    'material_id' => $orderPurchaseDetail->material_id,
                    'quantity_request' => $orderPurchaseDetail->quantity,
                    'quantity_entered' => $orderPurchaseDetail->quantity
                ]);
            }

            // Si el plazo indica credito, se crea el credito


            // TODO: Actualizamos la entrada
            $entry = Entry::find($request->get('entry_id'));
            $entry->purchase_order = $orderPurchase->code;
            $entry->save();

            if ( isset($orderPurchase->deadline) )
            {
                if ( $orderPurchase->deadline->credit == 1 || $orderPurchase->deadline->credit == true )
                {
                    $deadline = PaymentDeadline::find($orderPurchase->deadline->id);
                    $fecha_issue = Carbon::parse($entry->date_entry);
                    $fecha_expiration = $fecha_issue->addDays($deadline->days);
                    // TODO: Poner dias
                    $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                    $credit = SupplierCredit::create([
                        'supplier_id' => $orderPurchase->supplier->id,
                        'invoice' => ($this->onlyZeros($entry->invoice)) ? null:$entry->invoice,
                        'image_invoice' => $entry->image,
                        'total_soles' => ($orderPurchase->currency_order == 'PEN') ? $entry->total:null,
                        'total_dollars' => ($orderPurchase->currency_order == 'USD') ? $entry->total:null,
                        'date_issue' => $entry->date_entry,
                        'order_purchase_id' => $orderPurchase->id,
                        'state_credit' => 'outstanding',
                        'order_service_id' => null,
                        'date_expiration' => $fecha_expiration,
                        'days_to_expiration' => $dias_to_expire,
                        'code_order' => $orderPurchase->code,
                        'payment_deadline_id' => $orderPurchase->payment_deadline_id
                    ]);
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'AutoRegularizar Post de orden de compra',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Su orden de compra con el código '.$codeOrder.' se guardó con éxito.', 'url'=>route('invoice.index')], 200);

    }

    public function reportMaterialEntries()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('entry.reportMaterialEntries', compact('permissions'));
    }

    public function getJsonMaterialsInEntry()
    {
        $begin = microtime(true);
        $materials = Material::where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'code' => $material->code]);
        }
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener lista de materiales que tienen ingreso',
            'time' => $end
        ]);

        return $array;
    }

    public function getJsonEntriesOfMaterial( $id_material )
    {
        $begin = microtime(true);

        $dateCurrent = Carbon::now('America/Lima');
        $date4MonthAgo = $dateCurrent->subMonths(4);

        $entryDetails = DetailEntry::where('material_id', '=', $id_material)
            //->where('created_at', '>=', $date4MonthAgo)
            //->where('entry_type', 'Por compra')
            ->get();
        $entries = [];
        foreach ($entryDetails as $entryDetail) {
            $entry = Entry::with(['supplier'])->find($entryDetail->entry_id);
            if ( $entry->entry_type != 'Retacería' )
            {
                array_push($entries, [
                    'entry' => $entry->id,
                    'guide' => $entry->referral_guide,
                    'order' => $entry->purchase_order,
                    'invoice' => $entry->invoice,
                    'supplier' => ( $entry->supplier == null ) ? 'Sin proveedor':$entry->supplier->business_name,
                    'date' => $entry->date_entry,
                    'quantity' => (float)$entryDetail->entered_quantity,
                ]);
            }

        }

        $new_arr2 = array();
        foreach($entries as $item) {
            if(isset($new_arr2[$item['entry']])) {
                $new_arr2[ $item['entry']]['quantity'] += (float)$item['quantity'];
                continue;
            }

            $new_arr2[$item['entry']] = $item;
        }

        $centries_final = array_values($new_arr2);
        //dump($outputs);
        //$result = array_values( array_unique($outputs) );
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener ingresos de materiales',
            'time' => $end
        ]);
        return $centries_final;
    }

    public function showExtraDocumentEntryPurchase( $entry_id )
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $entry = Entry::find($entry_id);

        $imagesInvoices = [];
        $imagesGuides = [];
        $imagesObservations = [];

        $imagenesFactura = EntryImage::where('entry_id', $entry->id)
            ->where('type', 'i')->get();
        $imagenesGuias = EntryImage::where('entry_id', $entry->id)
            ->where('type', 'g')->get();
        $imagenesObs = EntryImage::where('entry_id', $entry->id)
            ->where('type', 'o')->get();

        if ($imagenesFactura->count() > 0)
        {
            $imagesInvoices = $imagenesFactura;
        }
        if ($imagenesGuias->count() > 0)
        {
            $imagesGuides = $imagenesGuias;
        }
        if ($imagenesObs->count() > 0)
        {
            $imagesObservations = $imagenesObs;
        }
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver imagenes extras de entradas',
            'time' => $end
        ]);
        //dump($quote);
        return view('entry.editImages', compact('entry','permissions', 'imagesInvoices', 'imagesGuides', 'imagesObservations'));

    }

    public function updateImage(Request $request, $image)
    {
        $begin = microtime(true);
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            $id = $request->get('image_id');
            $code = $request->get('image_code');

            $image = EntryImage::find($id);
            $image->code = $code;
            $image->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Imagen Entrada modificada ',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Imagen modificada con éxito'], 200);

    }

    public function deleteImage(Request $request, $image)
    {
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            $id = $request->get('image_id');

            $imagen = EntryImage::find($id);

            $image_path = public_path().'/images/entries/extras/'.$imagen->image;
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

    public function saveImages(Request $request, $entry_id)
    {
        $begin = microtime(true);
        //dd($request->get('image_id'));
        DB::beginTransaction();
        try {
            // TODO: Tratamiento de las imagenes de las entradas

            $images = $request->images;
            $descriptions = $request->codeimages;
            $types = $request->types;

            if ( count($images) != 0 && count($descriptions) != 0 )
            {
                foreach ( $images as $key => $image )
                {
                    $path = public_path().'/images/entries/extras/';
                    $img = $image;

                    $extension = $img->getClientOriginalExtension();
                    //$filename = $entry->id . '.' . $extension;
                    if ( strtoupper($extension) != "PDF" )
                    {
                        $filename = $entry_id .'_'. $this->generateRandomString(20). '.JPG';
                        $imgQuote = Image::make($img);
                        $imgQuote->orientate();
                        $imgQuote->save($path.$filename, 80, 'JPG');

                        EntryImage::create([
                            'entry_id' => $entry_id,
                            'code' => $descriptions[$key],
                            'image' => $filename,
                            'type' => $types[$key],
                            'type_file' => 'img',
                        ]);
                    } else {
                        $filename = 'pdf'.$entry_id .'_'. $this->generateRandomString(20) . '.' .$extension;
                        $img->move($path, $filename);

                        EntryImage::create([
                            'entry_id' => $entry_id,
                            'code' => $descriptions[$key],
                            'image' => $filename,
                            'type' => $types[$key],
                            'type_file' => 'pdf'
                        ]);
                    }

                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar images entries POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Imágenes guardadas con éxito'], 200);

    }

    public function onlyZeros($cadena) {
        $cadenaSinGuiones = str_replace('-', '', $cadena); // Eliminar los guiones

        if (!ctype_digit($cadenaSinGuiones)) {
            return false; // La cadena contiene caracteres que no son dígitos
        }

        if ($cadenaSinGuiones !== str_repeat('0', strlen($cadenaSinGuiones))) {
            return false; // La cadena no está formada solo por ceros
        }

        return true; // La cadena está formada solo por ceros
    }

    public function indexEntryPurchaseReport(){
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('entry.index_entry_purchase_report', compact('permissions'));
    }

    public function getJsonEntriesPurchaseReport(Request $request){
        $begin = microtime(true);
    
        $startMonthsAgo = $request->get('start');
        $endMonthsAgo = $request->get('end');
    
        // Verificar si startMonthsAgo o endMonthsAgo son nulos
        if (is_null($startMonthsAgo) || is_null($endMonthsAgo)) {
            return response()->json(['error' => 'Ambos campos de fechas son obligatorios.'], 400);
        }
        
        $startDate = Carbon::createFromFormat('d/m/Y', $startMonthsAgo)->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d/m/Y', $endMonthsAgo)->format('Y-m-d');
    
        $entries = Entry::with('supplier')
            ->where('entry_type', 'Por compra')
            ->where('finance', false)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get();
    
        $end = microtime(true) - $begin;
    
        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener ingresos por compra en un rango de fechas personalizado',
            'time' => $end
        ]);
    
        return datatables($entries)->toJson();
        
    }

    public function exportEntriesAlmacen()
    {
        //dd($request);
        $start = $_GET['start'];
        $end = $_GET['end'];
        $type = $_GET['typeEntry'];
        //dump($start);
        //dump($end);
        $array = [];
        $dates = '';

        if ( $start == '' || $end == '' )
        {
            //dump('Descargar todos');
            $dates = 'REPORTE DE ENTRADAS TOTALES';
            $entries = [];
            switch ($type) {
                case 1: //Todas
                    $dates = $dates . " POR COMPRA E INVENTARIO ";
                    $entries = Entry::with('supplier', 'details')
                        ->whereIn('entry_type', ['Por compra','Inventario'])
                        ->where('finance', false)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 2: // Por Compra
                    $dates = $dates . " POR COMPRA";
                    $entries = Entry::with('supplier', 'details')
                        ->where('entry_type', 'Por compra')
                        ->where('finance', false)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 3: // Por Inventario
                    $dates = $dates . " POR INVENTARIO ";
                    $entries = Entry::with('supplier', 'details')
                        ->where('entry_type', 'Inventario')
                        ->where('finance', false)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $entries as $entry )
            {
                foreach ( $entry->details as $detail )
                {
                    array_push($array, [
                        'tipo' => $entry->entry_type,
                        'orden' => $entry->purchase_order,
                        'factura' => $entry->invoice,
                        'proveedor' => ($entry->supplier_id != null) ? $entry->supplier->business_name:"Sin Provedor",
                        'fecha' => ($entry->date_entry != null) ? $entry->date_entry->format("d/m/Y"):"Sin fecha",
                        'observaciones' => $entry->observation,
                        'moneda' => $entry->currency_invoice,
                        'codigo' => $detail->material->code,
                        'material' => $detail->material->full_name,
                        'cantidad' => $detail->entered_quantity,
                        'precio' => $detail->unit_price,
                        'total_precio' => ($detail->total_detail == null) ? round($detail->unit_price*$detail->entered_quantity, 2):$detail->total_detail,
                    ]);
                }

            }


        } else {
            $date_start = Carbon::createFromFormat('d/m/Y', $start);
            $end_start = Carbon::createFromFormat('d/m/Y', $end);

            $dates = 'REPORTE DE ENTRADAS';
            $entries = [];
            switch ($type) {
                case 1: //Todas
                    $dates = $dates . " POR COMPRA E INVENTARIO DEL ".$start." AL ".$end;
                    $entries = Entry::with('supplier', 'details')
                        ->whereIn('entry_type', ['Por compra','Inventario'])
                        ->whereDate('date_entry', '>=', $date_start)
                        ->whereDate('date_entry', '<=', $end_start)
                        ->where('finance', false)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 2: // Por Compra
                    $dates = $dates . " POR COMPRA DEL ".$start." AL ".$end;
                    $entries = Entry::with('supplier', 'details')
                        ->where('entry_type', 'Por compra')
                        ->where('finance', false)
                        ->whereDate('date_entry', '>=', $date_start)
                        ->whereDate('date_entry', '<=', $end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 3: // Por Inventario
                    $dates = $dates . " POR INVENTARIO DEL ".$start." AL ".$end;
                    $entries = Entry::with('supplier', 'details')
                        ->where('entry_type', 'Inventario')
                        ->where('finance', false)
                        ->whereDate('date_entry', '>=', $date_start)
                        ->whereDate('date_entry', '<=', $end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $entries as $entry )
            {
                foreach ( $entry->details as $detail )
                {
                    array_push($array, [
                        'tipo' => $entry->entry_type,
                        'orden' => $entry->purchase_order,
                        'factura' => $entry->invoice,
                        'proveedor' => ($entry->supplier_id != null) ? $entry->supplier->business_name:"Sin Provedor",
                        'fecha' => ($entry->date_entry != null) ? $entry->date_entry->format("d/m/Y"):"Sin fecha",
                        'observaciones' => $entry->observation,
                        'moneda' => $entry->currency_invoice,
                        'codigo' => $detail->material->code,
                        'material' => $detail->material->full_name,
                        'cantidad' => $detail->entered_quantity,
                        'precio' => $detail->unit_price,
                        'total_precio' => ($detail->total_detail == null) ? round($detail->unit_price*$detail->entered_quantity, 2):$detail->total_detail,
                    ]);
                }

            }

        }
        return (new EntriesReportExcelDownload($array, $dates))->download('reporteEntradasCompraInventario.xlsx');

    }

    public function getTipoDeCambio($fechaFormato)
    {
        // Datos
        $token = env('TOKEN_DOLLAR');
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
            "precioCompra"=> 3.738,
            "precioVenta"=> 3.746,
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
        return $tipoCambio;
    }
}

