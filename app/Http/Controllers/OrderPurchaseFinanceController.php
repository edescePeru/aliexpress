<?php

namespace App\Http\Controllers;

use App\Audit;
use App\DetailEntry;
use App\Entry;
use App\Http\Requests\StoreOrderPurchaseFinanceRequest;
use App\Http\Requests\UpdateOrderPurchaseFinanceRequest;
use App\OrderPurchaseFinance;
use App\OrderPurchaseFinanceDetail;
use App\PaymentDeadline;
use App\Quote;
use App\Services\TipoCambioService;
use App\Supplier;
use App\SupplierAccount;
use App\SupplierCredit;
use App\UnitMeasure;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;

class OrderPurchaseFinanceController extends Controller
{
    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
        $this->middleware(function ($request, $next) {
            $tenant = \App\Support\TenantContext::tenantIdOrNull();
            $company = \App\Support\TenantContext::companyIdOrNull();
            abort_unless($tenant && $company && \App\Company::query()
                ->where('tenant_id', $tenant)->whereKey($company)->exists(),
                403, 'Selecciona una empresa válida para operar órdenes financieras.');
            return $next($request);
        });
    }

    public function indexOrderPurchaseFinance()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderPurchaseFinance.indexOrderFinance', compact('permissions'));
    }

    public function createOrderPurchaseFinance()
    {
        $begin = microtime(true);
        $suppliers = Supplier::where('tenant_id', \App\Support\TenantContext::tenantId())->get();
        $quotesRaised = Quote::where('tenant_id', \App\Support\TenantContext::tenantId())
            ->where('company_id', \App\Support\TenantContext::companyId())
            ->where('raise_status', 1)
            ->where('state_active', 'open')->get();
        $users = User::query()
            ->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereHas('companies', function ($query) {
                $query->where('companies.id', \App\Support\TenantContext::companyId())
                    ->where('company_user.is_active', true);
            })->get();

        // TODO: WITH TRASHED
        $maxCode = OrderPurchaseFinance::withTrashed()->max('id');
        $maxId = $maxCode + 1;
        //$maxCode = OrderPurchase::max('code');
        //$maxId = (int)substr($maxCode,3) + 1;
        $length = 5;
        $codeOrder = 'OCF-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable()->get();

        $unitMeasures = UnitMeasure::where('tenant_id', \App\Support\TenantContext::tenantId())->select(['id', 'description'])->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Crear Orden compra Normal VISTA',
            'time' => $end
        ]);
        return view('orderPurchaseFinance.createOrderFinance', compact('users', 'codeOrder', 'suppliers', 'payment_deadlines', 'unitMeasures', 'quotesRaised'));

    }

    public function storeOrderPurchaseFinance(StoreOrderPurchaseFinanceRequest $request)
    {
        \App\Services\Purchases\FinancePurchaseInput::validate($request);
        $begin = microtime(true);
        $validated = $request->validated();

        $fecha = ($request->filled('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        DB::beginTransaction();
        try {
            $maxId = OrderPurchaseFinance::withTrashed()->max('id')+1;
            $length = 5;

            $orderPurchaseFinance = OrderPurchaseFinance::create([
                'code' => '',
                'supplier_id' => ($request->has('supplier_id')) ? $request->get('supplier_id') : null,
                'date_delivery' => ($request->filled('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now(),
                'date_order' => ($request->filled('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now(),
                'approved_by' => ($request->has('approved_by')) ? $request->get('approved_by') : null,
                'payment_condition' => ($request->has('service_condition')) ? $request->get('service_condition') : '',
                'currency_order' => ($request->get('currency') === 'true') ? 'PEN': 'USD',
                'currency_compra' => $tipoCambioSunat->precioCompra,
                'currency_venta' => $tipoCambioSunat->precioVenta,
                'igv' => $request->get('taxes_send'),
                'total' => $request->get('total_send'),
                'observation' => $request->get('observation'),
                'quote_supplier' => $request->get('quote_supplier'),
                'regularize' => ($request->get('regularize') === 'true') ? 'r':'nr',
                'payment_deadline_id' => ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null,
                'quote_id' => ($request->has('quote_id')) ? $request->get('quote_id') : null,

            ]);

            $codeOrder = 'OCF-'.str_pad($orderPurchaseFinance->id, 5, '0', STR_PAD_LEFT);
            $orderPurchaseFinance->code = $codeOrder;
            $orderPurchaseFinance->save();

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $orderPurchaseFinanceDetail = OrderPurchaseFinanceDetail::create([
                    'order_purchase_finance_id' => $orderPurchaseFinance->id,
                    'material' => $items[$i]->service,
                    'unit' => $items[$i]->unit,
                    'quantity' => (float) $items[$i]->quantity,
                    'price' => (float) $items[$i]->price,
                    'total_detail' => (float) $items[$i]->total,
                ]);

                $total = $orderPurchaseFinanceDetail->total_detail;
                $subtotal = $total / 1.18;
                $igv = $total - $subtotal;
                $orderPurchaseFinanceDetail->igv = $igv;
                $orderPurchaseFinanceDetail->save();

            }

            // Si el plazo indica credito, se crea el credito
            /*if ( isset($orderPurchaseFinance->deadline) )
            {
                if ( $orderPurchaseFinance->deadline->credit == 1 || $orderPurchaseFinance->deadline->credit == true )
                {
                    $deadline = PaymentDeadline::find($orderPurchaseFinance->deadline->id);
                    //$fecha_issue = Carbon::parse($orderService->date_order);
                    //$fecha_expiration = $fecha_issue->addDays($deadline->days);
                    // TODO: Poner dias
                    //$dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                    $credit = SupplierCredit::create([
                        'supplier_id' => $orderPurchaseFinance->supplier->id,
                        'total_soles' => ($orderPurchaseFinance->currency_order == 'PEN') ? $orderPurchaseFinance->total:null,
                        'total_dollars' => ($orderPurchaseFinance->currency_order == 'USD') ? $orderPurchaseFinance->total:null,
                        //'date_issue' => $orderService->date_order,
                        'order_purchase_id' => null,
                        'state_credit' => 'outstanding',
                        'order_service_id' => null,
                        'order_purchase_finance_id' => $orderPurchaseFinance->id,
                        //'date_expiration' => $fecha_expiration,
                        //'days_to_expiration' => $dias_to_expire,
                        'code_order' => $orderPurchaseFinance->code,
                        'payment_deadline_id' => $orderPurchaseFinance->payment_deadline_id
                    ]);
                }
            }*/

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar Orden Purchase Finance',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de compra de finanzas '.$codeOrder.' guardada con éxito.'], 200);

    }

    public function getAllOrderFinance()
    {
        $orders = OrderPurchaseFinance::with(['supplier', 'approved_user'])
            /*->where('regularize', 'nr')*/
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function editOrderPurchaseFinance($id)
    {
        $begin = microtime(true);
        $suppliers = Supplier::where('tenant_id', \App\Support\TenantContext::tenantId())->get();
        $quotesRaised = Quote::where('tenant_id', \App\Support\TenantContext::tenantId())
            ->where('company_id', \App\Support\TenantContext::companyId())
            ->where('raise_status', 1)
            ->where('state_active', 'open')->get();
        $users = User::query()
            ->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereHas('companies', function ($query) {
                $query->where('companies.id', \App\Support\TenantContext::companyId())
                    ->where('company_user.is_active', true);
            })->get();
        $unitMeasures = UnitMeasure::where('tenant_id', \App\Support\TenantContext::tenantId())->select(['id', 'description'])->get();

        $order = OrderPurchaseFinance::with(['supplier', 'approved_user'])->findOrFail($id);
        $details = OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)->get();

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable(OrderPurchaseFinance::query()->where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->whereKey($id)->value('payment_deadline_id'))->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Editar Orden de compra finance VISTA',
            'time' => $end
        ]);
        return view('orderPurchaseFinance.editOrderPurchaseFinance', compact('order', 'details', 'suppliers', 'users', 'unitMeasures', 'payment_deadlines', 'quotesRaised'));

    }

    public function updateOrderPurchaseFinance(StoreOrderPurchaseFinanceRequest $request)
    {
        $request->validate(['order_id' => 'required|integer|min:1']);
        $currentOrder = OrderPurchaseFinance::findOrFail($request->input('order_id'));
        \App\Services\Purchases\FinancePurchaseInput::validate($request, $currentOrder);
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $orderFinance = OrderPurchaseFinance::lockForUpdate()->findOrFail($request->get('order_id'));
            \App\Services\Purchases\FinancePurchaseInput::validate($request, $orderFinance);
            $orderFinance->supplier_id = ($request->has('supplier_id')) ? $request->get('supplier_id') : null;
            $orderFinance->date_delivery = ($request->filled('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now();
            $orderFinance->date_order = ($request->filled('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
            $orderFinance->approved_by = ($request->has('approved_by')) ? $request->get('approved_by') : null;
            $orderFinance->payment_condition = ($request->has('service_condition')) ? $request->get('service_condition') : '';
            $orderFinance->currency_order = ($request->get('state') === 'true') ? 'PEN': 'USD';
            $orderFinance->igv = (float) $request->get('taxes_send');
            $orderFinance->total = (float) $request->get('total_send');
            $orderFinance->observation = $request->get('observation');
            $orderFinance->quote_supplier = $request->get('quote_supplier');
            $orderFinance->regularize = ($request->get('regularize') === 'true') ? 'r': 'nr';
            $orderFinance->payment_deadline_id = ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null;
            $orderFinance->quote_id = ($request->has('quote_id')) ? $request->get('quote_id') : null;
            $orderFinance->save();

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                if ($items[$i]->detail_id === '')
                {
                    $orderFinanceDetail = OrderPurchaseFinanceDetail::create([
                        'order_purchase_finance_id' => $orderFinance->id,
                        'material' => $items[$i]->service,
                        'unit' => $items[$i]->unit,
                        'quantity' => (float) $items[$i]->quantity,
                        'price' => (float) $items[$i]->price,
                        'total_detail' => (float) $items[$i]->total,
                    ]);

                    $total = round($orderFinanceDetail->total_detail, 2);
                    $subtotal = round($total / 1.18, 2);
                    $igv = round($total - $subtotal, 2);
                    $orderFinanceDetail->igv = $igv;
                    $orderFinanceDetail->save();
                }

            }
            // Si la orden de servicio se modifica, el credito tambien se modificara
            /*$credit = SupplierCredit::where('order_purchase_finance_id', $orderFinance->id)
                ->where('state_credit', 'outstanding')->first();
            if ( isset($credit) )
            {
                $deadline = PaymentDeadline::find($credit->deadline->id);
                //$fecha_issue = Carbon::parse($orderService->date_order);
                //$fecha_expiration = $fecha_issue->addDays($deadline->days);
                //$dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                $credit->supplier_id = $orderFinance->supplier->id;
                $credit->total_soles = ($orderFinance->currency_order == 'PEN') ? $orderFinance->total:null;
                $credit->total_dollars = ($orderFinance->currency_order == 'USD') ? $orderFinance->total:null;
                //$credit->date_issue = $orderService->date_order;
                $credit->code_order = $orderFinance->code;
                //$credit->date_expiration = $fecha_expiration;
                //$credit->days_to_expiration = $dias_to_expire;
                $credit->payment_deadline_id = $orderFinance->payment_deadline_id;
                $credit->save();
            }*/

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar Orden compra de Finanzas',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de compra de Finanzas modificada con éxito.'], 200);

    }

    public function destroyFinanceDetail($idDetail)
    {
        DB::transaction(function () use ($idDetail) {
            $parentId = OrderPurchaseFinanceDetail::whereKey($idDetail)->value('order_purchase_finance_id');
            abort_unless($parentId, 404);
            $order = OrderPurchaseFinance::lockForUpdate()->findOrFail($parentId);
            $detail = OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)
                ->lockForUpdate()->findOrFail($idDetail);
            $order->igv = round((float) $order->igv - (float) $detail->igv, 2);
            $order->total = round((float) $order->total - (float) $detail->total_detail, 2);
            $detail->deletion_batch = null;
            $detail->save();
            $detail->delete();
            $order->save();
            Audit::create(['user_id' => Auth::user()->id, 'action' => 'Eliminar detalle de orden financiera', 'time' => 0]);
        });
        return response()->json(['message' => 'Detalle de orden eliminado con éxito.'], 200);
    }

    public function updateFinanceDetail(Request $request, $detail_id)
    {
        \Illuminate\Support\Facades\Validator::make($request->all(), ['items' => 'required|string|json'])->validate();
        $rows = json_decode($request->input('items'), true);
        \Illuminate\Support\Facades\Validator::make(['rows' => $rows], [
            'rows' => 'required|array|size:1', 'rows.0' => 'required|array',
            'rows.0.quantity' => 'required|numeric|min:0.01',
            'rows.0.price' => 'required|numeric|min:0',
            'rows.0.total' => 'required|numeric|min:0',
            'rows.0.detail_id' => 'nullable|integer|min:1',
        ])->validate();
        $row = $rows[0];
        if (!empty($row['detail_id']) && (string) $row['detail_id'] !== (string) $detail_id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'El detalle enviado no coincide con la ruta.']);
        }
        DB::transaction(function () use ($detail_id, $row) {
            $parentId = OrderPurchaseFinanceDetail::whereKey($detail_id)->value('order_purchase_finance_id');
            abort_unless($parentId, 404);
            $order = OrderPurchaseFinance::lockForUpdate()->findOrFail($parentId);
            $detail = OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)
                ->lockForUpdate()->findOrFail($detail_id);
            $oldTotal = (float) $detail->total_detail;
            $oldIgv = (float) $detail->igv;
            $detail->quantity = round((float) $row['quantity'], 2);
            $detail->price = round((float) $row['price'], 2);
            $detail->total_detail = round((float) $row['total'], 2);
            $detail->igv = round($detail->total_detail - round($detail->total_detail / 1.18, 2), 2);
            $detail->save();
            $order->igv = round((float) $order->igv - $oldIgv + $detail->igv, 2);
            $order->total = round((float) $order->total - $oldTotal + $detail->total_detail, 2);
            $order->save();
            Audit::create(['user_id' => Auth::user()->id, 'action' => 'Modificar detalle de orden financiera', 'time' => 0]);
        });
        return response()->json(['message' => 'Detalle de orden modificado con éxito.'], 200);
    }

    public function showOrderPurchaseFinance($id)
    {
        $begin = microtime(true);
        $suppliers = Supplier::where('tenant_id', \App\Support\TenantContext::tenantId())->get();
        $users = User::query()
            ->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereHas('companies', function ($query) {
                $query->where('companies.id', \App\Support\TenantContext::companyId())
                    ->where('company_user.is_active', true);
            })->get();

        $order = OrderPurchaseFinance::with(['supplier', 'approved_user', 'deadline'])->findOrFail($id);
        $details = OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver Orden de compra finanzass',
            'time' => $end
        ]);
        return view('orderPurchaseFinance.showOrderPurchaseFinance', compact('order', 'details', 'suppliers', 'users'));

    }

    public function destroyOrderPurchaseFinance($order_id)
    {
        return DB::transaction(function () use ($order_id) {
            $begin = microtime(true);
            $order = OrderPurchaseFinance::lockForUpdate()->findOrFail($order_id);
            $batch = (string) \Illuminate\Support\Str::uuid();
            $details = OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)
                ->lockForUpdate()->get();
            foreach ($details as $detail) {
                $detail->deletion_batch = $batch;
                $detail->save();
                $detail->delete();
            }
            $order->deletion_batch = $batch;
            $order->save();
            $order->delete();
            Audit::create(['user_id' => Auth::user()->id, 'action' => 'Eliminar orden financiera', 'time' => microtime(true) - $begin]);
            return response()->json(['message' => 'Orden de compra de finanzas eliminada con éxito.'], 200);
        });
    }

    public function changeStatusOrderPurchaseFinance($order_id, $status)
    {
        DB::beginTransaction();
        try {

            $orderPurchase = OrderPurchaseFinance::lockForUpdate()->findOrFail($order_id);
            $orderPurchase->status_order = $status;
            $orderPurchase->save();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Estado modificado.'], 200);

    }

    public function indexOrderPurchaseFinanceDelete()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderPurchaseFinance.indexFinanceDeleted', compact('permissions'));
    }

    public function getOrderDeleteFinance()
    {
        $orders = OrderPurchaseFinance::onlyTrashed()
            ->with(['supplier', 'approved_user'])
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function showOrderPurchaseFinanceDelete($id)
    {
        $suppliers = Supplier::where('tenant_id', \App\Support\TenantContext::tenantId())->get();
        $users = User::query()
            ->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereHas('companies', function ($query) {
                $query->where('companies.id', \App\Support\TenantContext::companyId())
                    ->where('company_user.is_active', true);
            })->get();

        $order = OrderPurchaseFinance::withTrashed()
            ->with(['supplier', 'approved_user', 'deadline'])->findOrFail($id);
        $details = OrderPurchaseFinanceDetail::withTrashed()
            ->where('order_purchase_finance_id', $order->id)
            ->get();

        return view('orderPurchaseFinance.showOrderPurchaseDeleteFinance', compact('order', 'details', 'suppliers', 'users'));

    }

    public function printOrderPurchaseFinanceDelete($id)
    {
        $service_order = null;
        $service_order = OrderPurchaseFinance::withTrashed()
            ->with('approved_user')
            ->with('deadline')
            ->with(['details'])
            ->where('id', $id)->firstOrFail();

        $length = 5;
        $codeOrder = ''.str_pad($id,$length,"0", STR_PAD_LEFT);

        $accounts = SupplierAccount::with('bank')
            ->where('supplier_id', $service_order->supplier_id)->get();

        $view = view('exports.orderFinance', compact('service_order','codeOrder', 'accounts'));

        $pdf = PDF::loadHTML($view);

        $name = 'Orden_de_compra_finanzas_ ' . $service_order->id . '.pdf';

        return $pdf->stream($name);
    }

    public function printOrderPurchaseFinance($id)
    {
        $purchase_order = null;
        $service_order = OrderPurchaseFinance::with('approved_user')
            ->with('deadline')
            ->with(['details'])
            ->where('id', $id)->firstOrFail();

        $length = 5;
        $codeOrder = ''.str_pad($id,$length,"0", STR_PAD_LEFT);

        $accounts = SupplierAccount::with('bank')
            ->where('supplier_id', $service_order->supplier_id)->get();

        $view = view('exports.orderFinance', compact('service_order','codeOrder', 'accounts'));

        $pdf = PDF::loadHTML($view);

        $name = 'Orden_de_compra_finanzas_ ' . $service_order->id . '.pdf';

        return $pdf->stream($name);
    }

    public function restoreOrderPurchaseFinanceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $begin = microtime(true);
            $order = OrderPurchaseFinance::onlyTrashed()->lockForUpdate()->findOrFail($id);
            if (!$order->deletion_batch) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'order' => 'Esta eliminación es anterior al control de restauración. Requiere revisar sus detalles antes de restaurar.',
                ]);
            }
            $batch = $order->deletion_batch;
            $details = OrderPurchaseFinanceDetail::onlyTrashed()
                ->where('order_purchase_finance_id', $order->id)
                ->where('deletion_batch', $batch)->lockForUpdate()->get();
            $order->restore();
            foreach ($details as $detail) {
                $detail->restore();
                $detail->deletion_batch = null;
                $detail->save();
            }
            $order->deletion_batch = null;
            $order->save();
            Audit::create(['user_id' => Auth::user()->id, 'action' => 'Restaurar orden financiera con sus detalles asociados', 'time' => microtime(true) - $begin]);
            return response()->json(['message' => 'Orden financiera restaurada con sus detalles asociados.'], 200);
        });
    }

    public function indexOrderPurchaseFinanceRegularize()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderPurchaseFinance.indexRegularizeFinance', compact('permissions'));
    }

    public function getAllOrderRegularizeFinance()
    {
        $orders = OrderPurchaseFinance::with(['supplier', 'approved_user'])
            ->where('regularize', 'r')
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($orders)->toJson();
    }

    public function indexOrderPurchaseFinanceLost()
    {
        //$orders = OrderPurchase::with(['supplier', 'approved_user'])->get();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('orderPurchaseFinance.indexFinanceLost', compact('permissions'));
    }

    public function getAllOrderPurchaseFinanceLost()
    {
        $begin = microtime(true);
        $orders = OrderPurchaseFinance::withTrashed()
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
                $codeOrder = 'OCF-'.str_pad($iterator,5,"0", STR_PAD_LEFT);
                array_push($lost, ['code'=>$codeOrder]);
                $iterator++;
            }
            $iterator++;
        }
        //dd($lost);

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener Orden de compras finanzas Perdidas',
            'time' => $end
        ]);
        return datatables($lost)->toJson();
    }

    public function regularizeAutoOrderEntryPurchaseFinance( $entry_id )
    {
        $begin = microtime(true);
        $entry = Entry::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->where('finance', true)->findOrFail($entry_id);
        $suppliers = Supplier::where('tenant_id', \App\Support\TenantContext::tenantId())->get();
        $quotesRaised = Quote::where('tenant_id', \App\Support\TenantContext::tenantId())
            ->where('company_id', \App\Support\TenantContext::companyId())
            ->where('raise_status', 1)
            ->where('state_active', 'open')->get();
        $users = User::query()
            ->where('tenant_id', \App\Support\TenantContext::tenantId())
            ->whereHas('companies', function ($query) {
                $query->where('companies.id', \App\Support\TenantContext::companyId())
                    ->where('company_user.is_active', true);
            })->get();

        $unitMeasures = UnitMeasure::where('tenant_id', \App\Support\TenantContext::tenantId())->select(['id', 'description'])->get();

        $maxId = OrderPurchaseFinance::withTrashed()->max('id')+1;
        $length = 5;
        $codeOrder = 'OCF-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

        $payment_deadlines = PaymentDeadline::where('type', 'purchases')->selectable()->get();

        $details = DetailEntry::where('entry_id', $entry_id)->get();
        //dd($entry);

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Regularizar Orden de compra de finanzas VISTA',
            'time' => $end
        ]);
        return view('orderPurchaseFinance.regularizeAutoEntryPurchaseFinance', compact('entry', 'details', 'suppliers', 'users', 'unitMeasures', 'codeOrder', 'payment_deadlines', 'quotesRaised'));
    }

    public function regularizeEntryToOrderPurchaseFinance(Request $request)
    {
        \App\Services\Purchases\FinancePurchaseInput::validate($request, null, true);
        $begin = microtime(true);
        $fecha = ($request->filled('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now();
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        DB::beginTransaction();
        try {
            $entry = Entry::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->where('finance', true)->lockForUpdate()->findOrFail($request->get('entry_id'));
            if (trim((string) $entry->purchase_order) !== '') {
                throw \Illuminate\Validation\ValidationException::withMessages(['entry_id' => 'Esta entrada ya tiene una orden de compra asociada.']);
            }
            $maxId = OrderPurchaseFinance::withTrashed()->max('id')+1;
            $length = 5;
            //$codeOrder = 'OS-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);

            $orderPurchaseFinance = OrderPurchaseFinance::create([
                'code' => '',
                'quote_supplier' => $request->get('quote_supplier'),
                'payment_deadline_id' => ($request->has('payment_deadline_id')) ? $request->get('payment_deadline_id') : null,
                'supplier_id' => ($request->has('supplier_id')) ? $request->get('supplier_id') : null,
                'date_delivery' => ($request->filled('date_delivery')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_delivery')) : Carbon::now(),
                'date_order' => ($request->filled('date_order')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_order')) : Carbon::now(),
                'approved_by' => ($request->has('approved_by')) ? $request->get('approved_by') : null,
                'payment_condition' => ($request->has('service_condition')) ? $request->get('service_condition') : '',
                'currency_order' => ($request->get('state') === 'true') ? 'PEN': 'USD',
                'currency_compra' => $tipoCambioSunat->precioCompra,
                'currency_venta' => $tipoCambioSunat->precioVenta,
                'observation' => $request->get('observation'),
                'igv' => $request->get('taxes_send'),
                'total' => $request->get('total_send'),
                'regularize' => ($request->get('regularize') === 'true') ? 'r':'nr',
                'quote_id' => ($request->has('quote_id')) ? $request->get('quote_id') : null,

            ]);

            $codeOrder = 'OCF-'.str_pad($orderPurchaseFinance->id, 5, '0', STR_PAD_LEFT);
            $orderPurchaseFinance->code = $codeOrder;
            $orderPurchaseFinance->save();

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $orderPurchaseFinanceDetail = OrderPurchaseFinanceDetail::create([
                    'order_purchase_finance_id' => $orderPurchaseFinance->id,
                    'material' => $items[$i]->service,
                    'unit' => $items[$i]->unit,
                    'quantity' => (float) $items[$i]->quantity,
                    'price' => (float) $items[$i]->price,
                    'total_detail' => (float) $items[$i]->total,
                ]);

                $total = $orderPurchaseFinanceDetail->total_detail;
                $subtotal = $total / 1.18;
                $igv = $total - $subtotal;
                $orderPurchaseFinanceDetail->igv = $igv;
                $orderPurchaseFinanceDetail->save();

            }

            // TODO: Modificamos la orden de servicio

            $orderPurchaseFinance->invoice = $entry->invoice;
            $orderPurchaseFinance->referral_guide = $entry->referral_guide;
            $orderPurchaseFinance->date_invoice = $entry->date_entry;
            $orderPurchaseFinance->save();

            $entry->purchase_order = $orderPurchaseFinance->code;
            $entry->save();

            // TODO: Tratamiento de imagenes
            /*if ($entry->image != null)
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

            }*/


            // Si el plazo indica credito, se crea el credito
            if ( isset($orderPurchaseFinance->deadline) )
            {
                if ( $orderPurchaseFinance->deadline->credit == 1 || $orderPurchaseFinance->deadline->credit == true )
                {
                    $deadline = PaymentDeadline::find($orderPurchaseFinance->deadline->id);
                    $fecha_issue = Carbon::parse($entry->date_entry);
                    $fecha_expiration = $fecha_issue->addDays($deadline->days);
                    // TODO: Poner dias
                    $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));

                    $credit = SupplierCredit::create([
                        'supplier_id' => $orderPurchaseFinance->supplier->id,
                        'total_soles' => ($orderPurchaseFinance->currency_order == 'PEN') ? $orderPurchaseFinance->total:null,
                        'total_dollars' => ($orderPurchaseFinance->currency_order == 'USD') ? $orderPurchaseFinance->total:null,
                        'date_issue' => $entry->date_entry,
                        'order_purchase_id' => null,
                        'state_credit' => 'outstanding',
                        'order_service_id' => null,
                        'order_purchase_finance_id' => $orderPurchaseFinance->id,
                        'date_expiration' => $fecha_expiration,
                        'days_to_expiration' => $dias_to_expire,
                        'code_order' => $orderPurchaseFinance->code,
                        'payment_deadline_id' => $orderPurchaseFinance->payment_deadline_id,
                        'entry_id' => $entry->id
                    ]);
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Regularizar Orden De Compra Finanza POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Orden de compra finanzas '.$orderPurchaseFinance->code.' guardada con éxito.', 'url' => route('report.invoice.finance.sin.orden')], 200);

    }

    public function getTipoDeCambio($fechaFormato)
    {
        // Datos
        //$token = 'apis-token-8651.OrHQT9azFQteF-IhmcLXP0W2MkemnPNX';
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
