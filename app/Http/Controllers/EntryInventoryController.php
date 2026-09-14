<?php

namespace App\Http\Controllers;

use App\Audit;
use App\DataGeneral;
use App\DetailEntry;
use App\Entry;
use App\EntryImage;
use App\FollowMaterial;
use App\Http\Requests\StoreEntryPurchaseOrderRequest;
use App\Http\Requests\StoreEntryInventoryRequest;
use App\Http\Requests\StoreOrderPurchaseRequest;
use App\Http\Requests\UpdateEntryInventoryRequest;
use App\Item;
use App\Material;
use App\MaterialOrder;
use App\Notification;
use App\NotificationUser;
use App\OrderPurchase;
use App\OrderPurchaseDetail;
use App\PaymentDeadline;
use App\Quote;
use App\Services\SettingService;
use App\Services\TipoCambioService;
use App\Supplier;
use App\SupplierCredit;
use App\Typescrap;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Barryvdh\DomPDF\Facade as PDF;

class EntryInventoryController extends Controller
{
    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
    }

    public function indexEntryInventory()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('entry.index_entry_inventory', compact('permissions'));
    }

    public function createEntryInventory()
    {
        $suppliers = Supplier::all();
    return view('entry.create_entry_inventory', compact('suppliers'));
    }


    public function storeEntryInventory(StoreEntryInventoryRequest $request)
    {
        //dd(json_decode($request->get('items')));
        $begin = microtime(true);
        //dd($request->get('deferred_invoice'));
        $validated = $request->validated();

        $fecha = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $precioCompra = 1;
        $precioVenta = 1;

        $currency = app(SettingService::class)->get('finance.base_currency');

        //$tipoMoneda = ($request->has('currency_invoice')) ? 'USD':'PEN';
        if ($currency === 'USD') {
            $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
            $precioCompra = (float) $tipoCambioSunat->precioCompra;
            $precioVenta = (float) $tipoCambioSunat->precioVenta;
        }


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
                'referral_guide' => null,
                'purchase_order' => null,
                'invoice' => null,
                'deferred_invoice' => 'off',
                'currency_invoice' => 'USD',
                'supplier_id' => null,
                'entry_type' => $request->get('entry_type'),
                'date_entry' => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance' => false,
                'currency_compra' => (float) $precioCompra,
                'currency_venta' => (float) $precioVenta,
                'observation' => $request->get('observation'),
            ]);



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
                ]);

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
                    //dump($detail_entry->material_id == $items[$i]->id_material);
                    //dump($detail_entry->material_id);
                    //dump($items[$i]->id_material);
                    //dd();
                    if( $detail_entry->material_id == $items[$i]->id_material )
                    {

                        $price = (float)$detail_entry->material->list_price;

                        //dd($detail_entry->material->materialType);

                        if ( isset($detail_entry->material->typeScrap) )
                        {
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
                        } else {
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
                        


                    }
                }
                

            }


            /* SI ( En el campo factura y en (Orden Compra/Servicio) ) AND Diferente a 000
                Entonces
                SI ( Existe en la tabla creditos ) ENTONCES
                actualiza la factura en la tabla de creditos
            */


            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Crear Ingreso Almacen Inventario',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por inventario guardado con éxito.'], 200);

    }

    public function editEntryInventory(Entry $entry)
    {
        $suppliers = Supplier::all();
        return view('entry.edit_entry_inventory', compact('entry', 'suppliers'));
    }

    public function updateEntryInventory(UpdateEntryInventoryRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $entry = Entry::find($request->get('entry_id'));
            $entry->referral_guide = null;
            $entry->purchase_order = null;
            $entry->invoice = null;
            $entry->deferred_invoice ='off';
            $entry->supplier_id = null;
            $entry->date_entry = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
            $entry->observation = $request->get('observation');
            $entry->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar Ingreso Almacen Inventario',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por Inventario modificado con éxito.'], 200);

    }



    public function destroyEntryInventory(Entry $entry)
    {
        $begin = microtime(true);
        DB::beginTransaction();
        try {
            if ( $entry->entry_type === 'Inventario' )
            {
                $details_entry = $entry->details;

                foreach ( $details_entry as $detail )
                {
                    $items = Item::where('detail_entry_id', $detail->id)
                        ->whereIn('state_item', ['reserved','exited'])
                        ->get();
                    if (!isset($items))
                    {
                        return response()->json(['message' => 'Lo sentimos, no se puede eliminar la entrada porque hay items reservados o en salida.'], 422);
                    }

                }

                $order_purchase = OrderPurchase::where('code', $entry->purchase_order)->first();

                foreach ( $details_entry as $detail )
                {
                    $material = Material::find($detail->material_id);
                    $material->stock_current = $material->stock_current - $detail->entered_quantity;
                    $material->save();

                    if ( !is_null( $order_purchase ) )
                    {
                        // TODO: Modificamos los material orders
                        $order_purchase_detail = OrderPurchaseDetail::where('order_purchase_id', $order_purchase->id)
                            ->where('material_id', $material->id)->first();

                        $material_orders = MaterialOrder::where('order_purchase_detail_id', $order_purchase_detail->id)->get();
                        if (isset($material_orders))
                        {
                            foreach ( $material_orders as $material_order )
                            {
                                $material_order->quantity_entered = 0;
                                $material_order->save();
                            }
                        }

                    }

                    $items = Item::where('detail_entry_id', $detail->id)->get();
                    foreach ( $items as $item )
                    {
                        $item->delete();
                    }

                    $detail->delete();
                }


                $entry->delete();
            }



            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Eliminar Ingreso Almacen',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Ingreso por inventario eliminado con éxito.'], 200);

    }

    public function getJsonEntriesInventory()
    {
        $begin = microtime(true);

        $dateCurrent = Carbon::now('America/Lima');
        $date4MonthAgo = $dateCurrent->subMonths(2);

        $entries = Entry::with('supplier')
            ->where('entry_type', 'Inventario')
            ->where('finance', false)
            ->where('created_at', '>=', $date4MonthAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener ingresos por inventario ',
            'time' => $end
        ]);
        return datatables($entries)->toJson();
    }

    public function getTipoDeCambio($fechaFormato)
    {
        // Datos
        //$token = 'apis-token-8651.OrHQT9azFQteF-IhmcLXP0W2MkemnPNX';
        $token = env('TOKEN_DOLLAR');
        $fecha = $fechaFormato;

        // Iniciar llamada a API
        $curl = curl_init();

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
        }

        //curl_close($curl);
        // Datos listos para usar
        $tipoCambioSbs = json_encode($response);
        //var_dump($tipoCambioSbs);
        return $response;
    }

    public function obtenerTipoCambio($fechaFormato)
    {
        $tipoCambio = $this->tipoCambioService->obtenerPorFecha($fechaFormato);
        return $tipoCambio;
    }
}
