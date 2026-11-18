<?php

namespace App\Http\Controllers;

use App\Audit;
use App\DetailEntry;
use App\Entry;
use App\Http\Requests\StoreOrderServiceRequest;
use App\OrderService;
use App\OrderServiceDetail;
use App\PaymentDeadline;
use App\Services\TipoCambioService;
use App\Supplier;
use App\SupplierAccount;
use App\SupplierCredit;
use App\UnitMeasure;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade as PDF;
use Intervention\Image\Facades\Image;

class OrderServiceController extends Controller
{
    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
    }

    public function indexOrderServices()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.indexOrderService', compact('permissions'));

    }

    public function listOrderServices()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.listOrderService', compact('permissions'));

    }

    public function createOrderServices()
    {
        $suppliers = Supplier::all();
        $users = User::all();

        $unitMeasures = UnitMeasure::select(['id', 'description'])->get();

        $maxId = OrderService::withTrashed()->max('id')+1;
        $length = 5;
        $codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable()->get();

        return view('orderService.createOrderService', compact('users', 'codeOrder', 'suppliers', 'unitMeasures', 'payment_deadlines'));

    }

    public function storeOrderServices(StoreOrderServiceRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $fecha = ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        DB::beginTransaction();
        try {
            $maxId = OrderService::withTrashed()->max('id')+1;
            $length = 5;
            //$codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $orderService = OrderService::create([
                'code' => '',
                'quote_supplier' => $request->get('quote_supplier'),
                'payment_deadline_id' => ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null,
                'supplier_id' => ($request->has('supplier_id')) ? $request->get('supplier_id') : null,
                'date_delivery' => ($request->has('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now(),
                'date_order' => ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now(),
                'approved_by' => ($request->has('approved_by')) ? $request->get('approved_by') : null,
                'payment_condition' => ($request->has('service_condition')) ? $request->get('service_condition') : '',
                'currency_order' => ($request->get('state') === 'true') ? 'PEN': 'USD',
                'currency_compra' => $tipoCambioSunat->precioCompra,
                'currency_venta' => $tipoCambioSunat->precioVenta,
                'observation' => $request->get('observation'),
                'igv' => $request->get('taxes_send'),
                'total' => $request->get('total_send'),
                'regularize' => ($request->get('regularize') === 'true') ? 'r':'nr',
            ]);

            $codeOrder = '';
            if ( $maxId < $orderService->id ){
                $codeOrder = 'OS-'.str_pad($orderService->id,$length,"0", STR_PAD_LEFT);
                $orderService->code = $codeOrder;
                $orderService->save();
            } else {
                $codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $orderService->code = $codeOrder;
                $orderService->save();
            }

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $orderServiceDetail = OrderServiceDetail::create([
                    'order_service_id' => $orderService->id,
                    'service' => $items[$i]->service,
                    'unit' => $items[$i]->unit,
                    'quantity' => (float) $items[$i]->quantity,
                    'price' => (float) $items[$i]->price,
                    'total_detail' => (float) $items[$i]->total,
                ]);

                $total = $orderServiceDetail->total_detail;
                $subtotal = $total / 1.18;
                $igv = $total - $subtotal;
                $orderServiceDetail->igv = $igv;
                $orderServiceDetail->save();

            }

            // Si el plazo indica credito, se crea el credito
            /*if ( isset($orderService->deadline) )
            {
                if ( $orderService->deadline->credit == 1 || $orderService->deadline->credit == true )
                {
                    $deadline = PaymentDeadline::find($orderService->deadline->id);
                    //$fecha_issue = Carbon::parse($orderService->date_order);
                    //$fecha_expiration = $fecha_issue->addDays($deadline->days);
                    // TODO: Poner dias
                    //$dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                    $credit = SupplierCredit::create([
                        'supplier_id' => $orderService->supplier->id,
                        'total_soles' => ($orderService->currency_order == 'PEN') ? $orderService->total:null,
                        'total_dollars' => ($orderService->currency_order == 'USD') ? $orderService->total:null,
                        //'date_issue' => $orderService->date_order,
                        'order_purchase_id' => null,
                        'state_credit' => 'outstanding',
                        'order_service_id' => $orderService->id,
                        //'date_expiration' => $fecha_expiration,
                        //'days_to_expiration' => $dias_to_expire,
                        'code_order' => $orderService->code,
                        'payment_deadline_id' => $orderService->payment_deadline_id
                    ]);
                }
            }*/

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar Orden Servicio',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de servicio '.$codeOrder.' guardada con éxito.'], 200);

    }

    public function showOrderService($id)
    {
        $begin = microtime(true);
        $suppliers = Supplier::all();
        $users = User::all();

        $order = OrderService::with(['supplier', 'approved_user', 'deadline'])->find($id);
        $details = OrderServiceDetail::where('order_service_id', $order->id)->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver Orden Servicio',
            'time' => $end
        ]);
        return view('orderService.showOrderService', compact('order', 'details', 'suppliers', 'users'));

    }

    public function editOrderService($id)
    {
        $begin = microtime(true);
        $suppliers = Supplier::all();
        $users = User::all();
        $unitMeasures = UnitMeasure::select(['id', 'description'])->get();

        $order = OrderService::with(['supplier', 'approved_user'])->find($id);
        $details = OrderServiceDetail::where('order_service_id', $order->id)->get();

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable(OrderService::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Editar Orden Servicio VISTA',
            'time' => $end
        ]);
        return view('orderService.editOrderService', compact('order', 'details', 'suppliers', 'users', 'unitMeasures', 'payment_deadlines'));

    }

    public function updateOrderService(StoreOrderServiceRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $orderService = OrderService::find($request->get('order_id'));
            $orderService->supplier_id = ($request->has('supplier_id')) ? $request->get('supplier_id') : null;
            $orderService->payment_deadline_id = ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null;
            $orderService->date_delivery = ($request->has('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now();
            $orderService->date_order = ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
            $orderService->approved_by = ($request->has('approved_by')) ? $request->get('approved_by') : null;
            $orderService->payment_condition = ($request->has('service_condition')) ? $request->get('service_condition') : '';
            $orderService->currency_order = ($request->get('state') === 'true') ? 'PEN': 'USD';
            $orderService->regularize = ($request->get('regularize') === 'true') ? 'r': 'nr';
            $orderService->observation = $request->get('observation');
            $orderService->quote_supplier = $request->get('quote_supplier');
            $orderService->igv = (float) $request->get('taxes_send');
            $orderService->total = (float) $request->get('total_send');
            $orderService->save();

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                if ($items[$i]->detail_id === '')
                {
                    $orderServiceDetail = OrderServiceDetail::create([
                        'order_service_id' => $orderService->id,
                        'service' => $items[$i]->service,
                        'unit' => $items[$i]->unit,
                        'quantity' => (float) $items[$i]->quantity,
                        'price' => (float) $items[$i]->price,
                    ]);

                    $total = round($orderServiceDetail->quantity*$orderServiceDetail->price, 2);
                    $subtotal = round($total / 1.18, 2);
                    $igv = round($total - $subtotal, 2);
                    $orderServiceDetail->igv = $igv;
                    $orderServiceDetail->save();
                }

            }
            // Si la orden de servicio se modifica, el credito tambien se modificara
            /*$credit = SupplierCredit::where('order_service_id', $orderService->id)
                ->where('state_credit', 'outstanding')->first();
            if ( isset($credit) )
            {
                $deadline = PaymentDeadline::find($credit->deadline->id);
                //$fecha_issue = Carbon::parse($orderService->date_order);
                //$fecha_expiration = $fecha_issue->addDays($deadline->days);
                //$dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                $credit->supplier_id = $orderService->supplier->id;
                $credit->total_soles = ($orderService->currency_order == 'PEN') ? $orderService->total:null;
                $credit->total_dollars = ($orderService->currency_order == 'USD') ? $orderService->total:null;
                //$credit->date_issue = $orderService->date_order;
                $credit->code_order = $orderService->code;
                //$credit->date_expiration = $fecha_expiration;
                //$credit->days_to_expiration = $dias_to_expire;
                $credit->payment_deadline_id = $orderService->payment_deadline_id;
                $credit->save();
            }*/

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar Orden Servicio',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de servicio modificada con éxito.'], 200);

    }

    public function destroyOrderService($order_id)
    {
        $begin = microtime(true);
        $orderService = OrderService::find($order_id);
        $details = OrderServiceDetail::where('order_service_id', $orderService->id)->get();
        foreach ( $details as $detail )
        {
            $detail->delete();
        }

        // Si la orden de servicio se elimina, y el credito es pendiente se debe eliminar
        /*$credit = SupplierCredit::where('order_service_id', $orderService->id)
            ->where('state_credit', 'outstanding')->first();
        if ( isset($credit) )
        {
            $credit->delete();
        }*/

        $orderService->delete();
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Eliminar Orden Servicio',
            'time' => $end
        ]);
        return response()->json(['message' => 'Orden de servicio eliminada con éxito.'], 200);

    }

    public function getAllOrderService()
    {
        $orders = OrderService::with(['supplier', 'approved_user'])
            /*->where('regularize', 'nr')*/
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function printOrderService($id)
    {
        $service_order = null;
        $service_order = OrderService::with('approved_user')
            ->with('deadline')
            ->with('details')
            ->where('id', $id)->first();

        $length = 5;
        $codeOrder = ''.str_pad($id,$length,"0", STR_PAD_LEFT);

        $accounts = SupplierAccount::with('bank')
            ->where('supplier_id', $service_order->supplier_id)->get();

        $view = view('exports.orderServiceV2', compact('service_order', 'codeOrder', 'accounts'));

        $pdf = PDF::loadHTML($view);

        $name = 'Orden_de_servicio_ ' . $service_order->id . '.pdf';

        return $pdf->stream($name);
    }

    public function updateDetail(Request $request, $detail_id)
    {
        $begin = microtime(true);
        DB::beginTransaction();
        try {
            $detail = OrderServiceDetail::find($detail_id);
            $orderService = OrderService::find($detail->order_service_id);

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {

                $total_last = $detail->price*$detail->quantity;
                $igv_last = $detail->igv;

                $quantity = (float) $items[$i]->quantity;
                $price = (float) $items[$i]->price;

                $total = round($quantity*$price, 2);
                $subtotal = round($total / 1.18, 2);
                $igv = $total - $subtotal;

                $detail->quantity = round($quantity, 2);
                $detail->price = round($price, 2);
                $detail->igv = round($igv,2);
                $detail->save();

                $orderService->igv = round(($orderService->igv - $igv_last),2);
                $orderService->total = round(($orderService->total - $total_last),2);
                $orderService->save();

                $orderService->igv = round(($orderService->igv + $igv),2);
                $orderService->total = round(($orderService->total + $total),2);

                $orderService->save();

                // Si la orden de compra express se modifica, el credito tambien se modificara
                /*$credit = SupplierCredit::where('order_service_id', $orderService->id)
                    ->where('state_credit', 'outstanding')->first();
                if ( isset($credit) )
                {
                    $credit->total_soles = ($orderService->currency_order == 'PEN') ? $orderService->total:null;
                    $credit->total_dollars = ($orderService->currency_order == 'USD') ? $orderService->total:null;
                    $credit->save();
                }*/
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar Orden Servicio Detalle',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Detalle de servicio modificado con éxito.'], 200);

    }

    public function destroyDetail($idDetail)
    {
        $begin = microtime(true);
        DB::beginTransaction();
        try {
            $detail = OrderServiceDetail::find($idDetail);
            $orderService = OrderService::find($detail->order_service_id);
            $orderService->igv = $orderService->igv - $detail->igv;
            $orderService->total = $orderService->total - ($detail->quantity*$detail->price);
            $orderService->save();

            // Si la orden de compra express se modifica, el credito tambien se modificara
            /*$credit = SupplierCredit::where('order_service_id', $orderService->id)
                ->where('state_credit', 'outstanding')->first();
            if ( isset($credit) )
            {
                $credit->total_soles = ($orderService->currency_order == 'PEN') ? $orderService->total:null;
                $credit->total_dollars = ($orderService->currency_order == 'USD') ? $orderService->total:null;
                $credit->save();
            }*/

            $detail->delete();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Eliminar Orden Servicio Detalle',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Detalle de orden eliminado con éxito.'], 200);

    }

    public function regularizeOrderService($id)
    {
        $suppliers = Supplier::all();
        $users = User::all();

        $order = OrderService::with(['supplier', 'approved_user', 'deadline'])->find($id);
        $details = OrderServiceDetail::where('order_service_id', $order->id)->get();

        return view('orderService.regularizeOrderService', compact('order', 'details', 'suppliers', 'users'));

    }

    public function regularizePostOrderService( Request $request )
    {
        $begin = microtime(true);
        DB::beginTransaction();
        try {
            $orderService = OrderService::find($request->get('service_order_id'));
            $orderService->deferred_invoice = ($request->get('deferred_invoice') === 'true') ? 'on': 'off';
            $orderService->observation = $request->get('observation');
            $orderService->date_invoice = ($request->has('date_invoice')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')) : Carbon::now();
            $orderService->referral_guide = $request->get('referral_guide');
            $orderService->invoice = $request->get('invoice');
            $orderService->regularize = 'r';
            $orderService->save();

            if (!$request->file('image')) {
                if ($orderService->image_invoice == 'no_image.png' || $orderService->image_invoice == null) {
                    $orderService->image_invoice = 'no_image.png';
                    $orderService->save();
                }
            } else {
                $path = public_path().'/images/orderServices/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

                if ( strtoupper($extension) != "PDF")
                {
                    $filename = $orderService->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    //$request->file('image')->move($path, $filename);
                    $orderService->image_invoice = $filename;
                    $orderService->save();
                } else {
                    $filename = 'pdf'.$orderService->id . '.' .$extension;
                    $request->file('image')->move($path, $filename);
                    $orderService->image_invoice = $filename;
                    $orderService->save();
                }

                //$filename = $entry->id . '.' . $extension;
                //$filename = $orderService->id . '.jpg';
                //$img = Image::make($image);
                //$img->orientate();
                //$img->save($path.$filename, 80, 'jpg');
                //$request->file('image')->move($path, $filename);
                //$orderService->image_invoice = $filename;
                //$orderService->save();
            }

            if (!$request->file('imageOb')) {
                if ($orderService->image_observation == 'no_image.png' || $orderService->image_observation == null) {
                    $orderService->image_observation = 'no_image.png';
                    $orderService->save();
                }
            } else {
                $path = public_path().'/images/orderServices/observations/';
                $image = $request->file('imageOb');
                $extension = $image->getClientOriginalExtension();

                if ( strtoupper($extension) != "PDF" )
                {
                    $filename = $orderService->id . '.JPG';
                    $img = Image::make($image);
                    $img->orientate();
                    $img->save($path.$filename, 80, 'JPG');
                    //$request->file('image')->move($path, $filename);
                    $orderService->image_observation = $filename;
                    $orderService->save();
                } else {
                    $filename = 'pdf'.$orderService->id . '.' .$extension;
                    $request->file('imageOb')->move($path, $filename);
                    $orderService->image_observation = $filename;
                    $orderService->save();
                }

                //$filename = $orderService->id . '.jpg';
                //$img = Image::make($image);
                //$img->orientate();
                //$img->save($path.$filename, 80, 'jpg');
                //$request->file('image')->move($path, $filename);
                //$orderService->image_observation = $filename;
                //$orderService->save();
            }

            /*$credit = SupplierCredit::where('order_service_id', $orderService->id)
                ->where('state_credit', 'outstanding')->first();
            if ( isset($credit) )
            {
                $deadline = PaymentDeadline::find($credit->deadline->id);
                $fecha_issue = Carbon::parse($orderService->date_invoice);
                $fecha_expiration = $fecha_issue->addDays($deadline->days);
                $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                $credit->supplier_id = $orderService->supplier->id;
                $credit->total_soles = ($orderService->currency_order == 'PEN') ? $orderService->total:null;
                $credit->total_dollars = ($orderService->currency_order == 'USD') ? $orderService->total:null;
                $credit->date_issue = $orderService->date_invoice;
                $credit->code_order = $orderService->code;
                $credit->date_expiration = $fecha_expiration;
                $credit->days_to_expiration = $dias_to_expire;
                $credit->payment_deadline_id = $orderService->payment_deadline_id;
                $credit->invoice = $orderService->invoice;
                $credit->image_invoice = $orderService->image_invoice ;
                $credit->save();
            }*/

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Regularizar Orden Servicio POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de servicio modificada con éxito.'], 200);

    }

    public function indexServices()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.indexService', compact('permissions'));

    }

    public function getAllOrderRegularizeService()
    {
        $orders = OrderService::with(['supplier', 'approved_user'])
            ->where('regularize', 'r')
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function regularizeAutoOrderEntryService( $entry_id )
    {
        $begin = microtime(true);
        $entry = Entry::find($entry_id);
        $suppliers = Supplier::all();
        $users = User::all();

        $unitMeasures = UnitMeasure::select(['id', 'description'])->get();

        $maxId = OrderService::withTrashed()->max('id')+1;
        $length = 5;
        $codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable()->get();

        $details = DetailEntry::where('entry_id', $entry_id)->get();
        //dd($entry);

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Regularizar Orden Servicio VISTA',
            'time' => $end
        ]);
        return view('orderService.regularizeAutoEntryService', compact('entry', 'details', 'suppliers', 'users', 'unitMeasures', 'codeOrder', 'payment_deadlines'));
    }

    public function regularizeEntryToOrderService(Request $request)
    {
        $begin = microtime(true);

        $fecha = ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        DB::beginTransaction();
        try {
            $maxId = OrderService::withTrashed()->max('id')+1;
            $length = 5;
            //$codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $orderService = OrderService::create([
                'code' => '',
                'quote_supplier' => $request->get('quote_supplier'),
                'payment_deadline_id' => ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null,
                'supplier_id' => ($request->has('supplier_id')) ? $request->get('supplier_id') : null,
                'date_delivery' => ($request->has('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now(),
                'date_order' => ($request->has('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now(),
                'approved_by' => ($request->has('approved_by')) ? $request->get('approved_by') : null,
                'payment_condition' => ($request->has('service_condition')) ? $request->get('service_condition') : '',
                'currency_order' => ($request->get('state') === 'true') ? 'PEN': 'USD',
                'currency_compra' => $tipoCambioSunat->precioCompra,
                'currency_venta' => $tipoCambioSunat->precioVenta,
                'observation' => $request->get('observation'),
                'igv' => $request->get('taxes_send'),
                'total' => $request->get('total_send'),
                'regularize' => ($request->get('regularize') === 'true') ? 'r':'nr',
            ]);

            if ( $maxId < $orderService->id ){
                $codeOrder = 'OS-'.str_pad($orderService->id,$length,"0", STR_PAD_LEFT);
                $orderService->code = $codeOrder;
                $orderService->save();
            } else {
                $codeOrder2 = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $orderService->code = $codeOrder2;
                $orderService->save();
            }

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $orderServiceDetail = OrderServiceDetail::create([
                    'order_service_id' => $orderService->id,
                    'service' => $items[$i]->service,
                    'unit' => $items[$i]->unit,
                    'quantity' => (float) $items[$i]->quantity,
                    'price' => (float) $items[$i]->price,
                    'total_detail' => (float) $items[$i]->total,
                ]);

                $total = $orderServiceDetail->total_detail;
                $subtotal = $total / 1.18;
                $igv = $total - $subtotal;
                $orderServiceDetail->igv = $igv;
                $orderServiceDetail->save();

            }

            // TODO: Modificamos la orden de servicio
            $entry = Entry::find($request->get('entry_id'));

            $orderService->invoice = $entry->invoice;
            $orderService->referral_guide = $entry->referral_guide;
            $orderService->date_invoice = $entry->date_entry;
            $orderService->save();

            $entry->purchase_order = $orderService->code;
            $entry->save();

            // TODO: Tratamiento de imagenes
            if ($entry->image != null)
            {
                if ( $entry->image != 'no_image.png' )
                {
                    $nombre = $entry->image;
                    $imagen = public_path().'/images/entries/'.$nombre;
                    $ruta = public_path().'/images/orderServices/';
                    $extension = substr($nombre, -3);
                    //$filename = $entry->id . '.' . $extension;
                    if (file_exists($imagen)) {
                        if ( strtoupper($extension) != "PDF" )
                        {
                            $filename = $orderService->id . '.JPG';
                            $img = Image::make($imagen);
                            $img->orientate();
                            $img->save($ruta.$filename, 80, 'JPG');
                            //$request->file('image')->move($path, $filename);
                            $orderService->image_invoice = $filename;
                            $orderService->save();
                        } else {
                            $filename = 'pdf'.$orderService->id . '.' .$extension;
                            $destino = $ruta.$filename;
                            copy($imagen, $destino);
                            //$request->file('image')->move($path, $filename);
                            $orderService->image_invoice = $filename;
                            $orderService->save();
                        }
                    }
                }

            }

            if ($entry->imageOb != null)
            {
                if ( $entry->imageOb != 'no_image.png' )
                {
                    $nombre = $entry->imageOb;
                    $imagen = public_path().'/images/entries/observations/'.$nombre;
                    $ruta = public_path().'/images/orderServices/observations/';
                    $extension = substr($nombre, -3);
                    //$filename = $entry->id . '.' . $extension;
                    if (file_exists($imagen)) {
                        if ( strtoupper($extension) != "PDF" )
                        {
                            $filename = $orderService->id . '.JPG';
                            $img = Image::make($imagen);
                            $img->orientate();
                            $img->save($ruta.$filename, 80, 'JPG');
                            //$request->file('image')->move($path, $filename);
                            $orderService->image_observation = $filename;
                            $orderService->save();
                        } else {
                            $filename = 'pdf'.$orderService->id . '.' .$extension;
                            $destino = $ruta.$filename;
                            copy($imagen, $destino);
                            //$request->file('image')->move($path, $filename);
                            $orderService->image_observation = $filename;
                            $orderService->save();
                        }
                    }
                }

            }


            // Si el plazo indica credito, se crea el credito
            if ( isset($orderService->deadline) )
            {
                if ( $orderService->deadline->credit == 1 || $orderService->deadline->credit == true )
                {
                    $deadline = PaymentDeadline::find($orderService->deadline->id);
                    $fecha_issue = Carbon::parse($entry->date_entry);
                    $fecha_expiration = $fecha_issue->addDays($deadline->days);
                    // TODO: Poner dias
                    $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                    $credit = SupplierCredit::create([
                        'supplier_id' => $orderService->supplier->id,
                        'invoice' => ($this->onlyZeros($entry->invoice) == true) ? null:$entry->invoice,
                        'image_invoice' => $entry->image,
                        'total_soles' => ($orderService->currency_order == 'PEN') ? $orderService->total:null,
                        'total_dollars' => ($orderService->currency_order == 'USD') ? $orderService->total:null,
                        'date_issue' => $entry->date_entry,
                        'order_purchase_id' => null,
                        'state_credit' => 'outstanding',
                        'order_service_id' => $orderService->id,
                        'date_expiration' => $fecha_expiration,
                        'days_to_expiration' => $dias_to_expire,
                        'code_order' => $orderService->code,
                        'payment_deadline_id' => $orderService->payment_deadline_id,
                        'entry_id' => $entry->id
                    ]);
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Regularizar Orden Servicio POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de servicio '.$orderService->code.' guardada con éxito.', 'url' => route('invoice.index')], 200);

    }

    public function indexOrderServiceRegularize()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.indexRegularize', compact('permissions'));
    }

    public function getAllOrderRegularize()
    {
        $orders = OrderService::with(['supplier', 'approved_user'])
            ->where('regularize', 'r')
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function indexOrderServiceDeleted()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.indexDeleted', compact('permissions'));
    }

    public function getAllOrderDeleted()
    {
        $orders = OrderService::onlyTrashed()
            ->with(['supplier', 'approved_user'])
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function indexOrderServiceLost()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderService.indexLost', compact('permissions'));
    }

    public function getAllOrderLost()
    {
        $begin = microtime(true);
        $orders = OrderService::withTrashed()
            ->pluck('code')->toArray();
        //dump($orders);
        $ids = [];
        for ($i=0; $i< count($orders); $i++)
        {
            $id = (int) substr( $orders[$i], 3 );
            array_push($ids, $id);
        }
        //dump($ids);
        $lost = [];
        $iterator = 1;
        for ( $j=0; $j< count($ids); ++$j )
        {
            while( $iterator < $ids[$j] )
            {
                $codeOrder = 'OS-'.str_pad($iterator,5,"0", STR_PAD_LEFT);
                array_push($lost, ['code'=>$codeOrder]);
                $iterator++;
            }
            $iterator++;
        }
        //dd($lost);

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener Orden Servicio Perdidas',
            'time' => $end
        ]);
        return datatables($lost)->toJson();
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
        //$tipoCambioSbs = json_decode($response);
        //var_dump($tipoCambioSbs);
        $responseObject = json_encode($response);
        return $responseObject;
        //return $response;
    }

    public function obtenerTipoCambio($fechaFormato)
    {
        $tipoCambio = $this->tipoCambioService->obtenerPorFecha($fechaFormato);
        return $tipoCambio;
    }
}
