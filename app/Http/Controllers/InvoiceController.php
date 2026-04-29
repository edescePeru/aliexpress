<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\CategoryInvoice;
use App\DetailEntry;
use App\Entry;
use App\Exports\InvoicesFinanceExport;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Item;
use App\Material;
use App\OrderService;
use App\PaymentDeadline;
use App\Services\TipoCambioService;
use App\Supplier;
use App\SupplierCredit;
use App\UnitMeasure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    protected $tipoCambioService;

    public function __construct(TipoCambioService $tipoCambioService)
    {
        $this->tipoCambioService = $tipoCambioService;
    }

    public function indexInvoices()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('invoice.index_invoice', compact('permissions'));
    }

    public function indexInvoicesAnnulled()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('invoice.index_invoice_annulled', compact('permissions'));
    }

    public function createInvoice()
    {
        $suppliers = Supplier::all();
        $unitMeasures = UnitMeasure::all();
        $categories = CategoryInvoice::all();

        // ✅ NUEVO: Cajas + subtipos (igual que PV)
        $cashBoxes = CashBox::where('is_active', 1)
            ->orderBy('position')
            ->get()
            ->map(function ($b) {
                return [
                    'id'            => (int) $b->id,
                    'name'          => (string) $b->name,
                    'type'          => (string) $b->type,
                    'uses_subtypes' => (bool) $b->uses_subtypes,
                ];
            })
            ->values();

        $subtypes = CashBoxSubtype::whereNull('cash_box_id')
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
            ->map(function ($s) {
                return [
                    'id'   => (int) $s->id,
                    'name' => (string) $s->name,
                ];
            })
            ->values();

        return view('invoice.create_invoice', compact('suppliers', 'unitMeasures', 'categories', 'cashBoxes','subtypes'));
    }

    public function storeInvoice(StoreInvoiceRequest $request)
    {
        $begin = microtime(true);
        //dd($request->get('deferred_invoice'));
        $validated = $request->validated();

        $fecha = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
        $fechaFormato = $fecha->format('Y-m-d');
        //$response = $this->getTipoDeCambio($fechaFormato);

        //$tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);

        $precioCompra = 1;
        $precioVenta = 1;

        $tipoMoneda = $request->has('currency_invoice') ? 'USD' : 'PEN';

        if ( $tipoMoneda == 'USD' ) {
            $tipoCambioSunat = $this->obtenerTipoCambio($fechaFormato);
            $precioCompra = (float) $tipoCambioSunat->precioCompra;
            $precioVenta = (float) $tipoCambioSunat->precioVenta;
        }

        DB::beginTransaction();
        try {
            $entry = Entry::create([
                'purchase_order' => $request->get('purchase_order'),
                'invoice' => $request->get('invoice'),
                'deferred_invoice' => 'off',
                'supplier_id' => $request->get('supplier_id'),
                'entry_type' => $request->get('entry_type'),
                'type_order' => $request->get('type_order'),
                'date_entry' => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance' => true,
                'currency_invoice' => $tipoMoneda,
                'currency_compra' => (float) $precioCompra,
                'currency_venta' => (float) $precioVenta,
                'observation' => $request->get('observation'),
                'category_invoice_id' => $request->get('category_invoice_id'),
            ]);

            // TODO: Tratamiento de un archivo de forma tradicional
            if (!$request->file('image')) {
                $entry->image = 'no_image.png';
                $entry->save();
            } else {
                $path = public_path().'/images/entries/';
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

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

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                $detail_entry = DetailEntry::create([
                    'entry_id' => $entry->id,
                    'material_name' => $items[$i]->material,
                    'ordered_quantity' => $items[$i]->quantity,
                    'entered_quantity' => $items[$i]->quantity,
                    'unit_price' => (float) round((float)$items[$i]->price,2),
                    'material_unit' => $items[$i]->unit,
                    'total_detail' => (float) $items[$i]->total
                ]);
            }

            if ( $entry->invoice != '' || $entry->invoice != null )
            {
                if ( $entry->purchase_order != '' || $entry->purchase_order != null )
                {
                    $credit = SupplierCredit::with('deadline')
                        ->where('code_order', $entry->purchase_order)
                        ->where('state_credit', 'outstanding')->first();

                    if ( isset($credit) )
                    {
                        //$credit->delete();
                        $deadline = PaymentDeadline::find($credit->deadline->id);
                        $fecha_issue = Carbon::parse($entry->date_entry);
                        $fecha_expiration = $fecha_issue->addDays($deadline->days);
                        $credit->supplier_id = $entry->date_entry;
                        $credit->invoice = $entry->invoice;
                        $credit->image_invoice = $entry->image;
                        $credit->total_soles = ((float)$credit->total_soles>0) ? $entry->total:null;
                        $credit->total_dollars = ((float)$credit->total_dollars>0) ? $entry->total:null;
                        $credit->date_issue = $entry->date_entry;
                        $credit->days_to_expiration = $fecha_expiration;
                        $credit->code_order = $entry->purchase_order;
                        $credit->save();

                    }
                }
            }

            $end = microtime(true) - $begin;

            // ===========================
            // MOVIMIENTO DE CAJA / BANCO
            // ===========================
            $cashBoxId = $request->input('pv_cash_box_id');
            $cashBoxSubtypeId = $request->input('pv_cash_box_subtype_id');

            if (!$cashBoxId) {
                throw new \Exception('Seleccione una caja.');
            }

            $cashBox = CashBox::find($cashBoxId);

            if (!$cashBox || !(bool) $cashBox->is_active) {
                throw new \Exception('La caja seleccionada no es válida o está inactiva.');
            }

            $needSubtype = ($cashBox->type === 'bank' && (bool) $cashBox->uses_subtypes);

            if ($needSubtype) {
                if (!$cashBoxSubtypeId) {
                    throw new \Exception('Seleccione el canal/subtipo.');
                }

                $validSubtype = CashBoxSubtype::whereNull('cash_box_id')
                    ->where('is_active', 1)
                    ->where('id', (int) $cashBoxSubtypeId)
                    ->first();

                if (!$validSubtype) {
                    throw new \Exception('El subtipo seleccionado no es válido o está inactivo.');
                }
            } else {
                $cashBoxSubtypeId = null;
            }

            $cashRegisterQuery = CashRegister::where('cash_box_id', $cashBox->id)
                ->where('user_id', Auth::id())
                ->where('status', 1)
                ->latest();

            if ($needSubtype && $cashBoxSubtypeId && Schema::hasColumn('cash_registers', 'cash_box_subtype_id')) {
                $cashRegisterQuery->where('cash_box_subtype_id', (int) $cashBoxSubtypeId);
            }

            $cashRegister = $cashRegisterQuery->first();

            if (!$cashRegister) {
                throw new \Exception('No hay caja abierta para la caja/canal seleccionado.');
            }

            $amount = (float) $entry->total;

            if ($amount <= 0) {
                $amount = collect($items)->sum(function ($item) {
                    return (float) ($item->total ?? 0);
                });
            }

            $regularize = 1;

            /*if ($cashBox->type === 'bank' && (int) $cashBox->uses_subtypes === 1) {
                $subtype = CashBoxSubtype::findOrFail($cashBoxSubtypeId);
                $regularize = $subtype->is_deferred ? 0 : 1;
            }*/

            $description = 'Pago de factura/servicio - ' . ($cashBox->name ?? 'Caja');

            if ($needSubtype && $cashBoxSubtypeId) {
                $sub = CashBoxSubtype::find((int) $cashBoxSubtypeId);
                if ($sub) {
                    $description .= ' (' . $sub->name . ')';
                }
            }

            $movement = [
                'cash_register_id' => $cashRegister->id,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $description,
                'observation' => $entry->invoice ? 'Factura: ' . $entry->invoice : 'Factura financiera',
                'entry_id' => $entry->id,
                'sale_id' => null,
                'regularize' => $regularize,
            ];

            if (!is_null($cashBoxSubtypeId)) {
                $movement['cash_box_subtype_id'] = (int) $cashBoxSubtypeId;
            }

            CashMovement::create($movement);

            if ($regularize == 1) {
                $cashRegister->current_balance -= $amount;
                $cashRegister->total_expenses += $amount;
                $cashRegister->save();
            }


            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar factura finanza',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Factura por compra/servicio guardada con éxito.'], 200);

    }

    public function getJsonInvoices()
    {
        $begin = microtime(true);
        $dateCurrent = Carbon::now('America/Lima');
        $entries = Entry::with('supplier')->with('category_invoice')
            //->where('date_entry', '>=', $date4MonthAgo)
            ->where('entry_type', 'Por compra')
            ->where('finance', 1)
            ->where('state_annulled', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        $orderServices = OrderService::with('supplier')
            ->where('regularize', 'r')
            ->orderBy('created_at', 'desc')
            ->get();
        $array = [];
        foreach ( $entries as $entry )
        {
            array_push($array, $entry);
        }
        foreach ( $orderServices as $orderService )
        {
            array_push($array, $orderService);
        }
        //dd(datatables($entries)->toJson());
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener facturas finanzas',
            'time' => $end
        ]);
        return datatables($array)->toJson();
    }

    public function getJsonInvoicesAnnulled()
    {
        $begin = microtime(true);
        $dateCurrent = Carbon::now('America/Lima');
        $entries = Entry::with('supplier')->with('category_invoice')
            //->where('date_entry', '>=', $date4MonthAgo)
            ->where('entry_type', 'Por compra')
            ->where('finance', 1)
            ->where('state_annulled', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        $array = [];
        foreach ( $entries as $entry )
        {
            array_push($array, $entry);
        }

        //dd(datatables($entries)->toJson());
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener facturas finanzas',
            'time' => $end
        ]);
        return datatables($array)->toJson();
    }

    public function getInvoiceById( $id )
    {
        $entry = Entry::with('supplier')->with(['details' => function ($query) {
            $query->with('material');
        }])
            ->where('id', $id)
            ->get();
        return json_encode($entry);

    }

    public function getServiceById( $id )
    {
        /*$entry = Entry::with('supplier')->with(['details' => function ($query) {
            $query->with('material');
        }])
            ->where('id', $id)
            ->get();*/

        $service = OrderService::with('supplier')->with('details')
            ->where('id', $id)
            ->get();
        return json_encode($service);

    }

    public function getInvoices()
    {
        $begin = microtime(true);

        $entries = Entry::with('supplier')
            ->with(['details' => function ($query) {
            $query->with('material')->with(['items' => function ($query) {
                $query->where('state_item', 'entered')
                    ->with('typescrap')
                    ->with(['location' => function ($query) {
                        $query->with(['area', 'warehouse', 'shelf', 'level', 'container']);
                    }]);
            }]);
        }])
            ->with('category_invoice')
            ->where('entry_type', 'Por compra')
            ->orderBy('created_at', 'desc')
            ->get();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener facturas',
            'time' => $end
        ]);
        //dd(datatables($entries)->toJson());
        return $entries;
    }

    public function verInvoice(Entry $entry)
    {
        $suppliers = Supplier::all();
        $unitMeasures = UnitMeasure::all();
        $categories = CategoryInvoice::all();
        return view('invoice.show_invoice', compact('entry', 'suppliers', 'categories', 'unitMeasures'));
    }

    public function editInvoice(Entry $entry)
    {
        $suppliers = Supplier::all();
        $unitMeasures = UnitMeasure::all();
        $categories = CategoryInvoice::all();
        return view('invoice.edit_invoice', compact('entry', 'suppliers', 'categories', 'unitMeasures'));
    }

    public function updateInvoice(UpdateInvoiceRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $entry = Entry::find($request->get('entry_id'));
            $entry->purchase_order = $request->get('purchase_order');
            $entry->invoice = $request->get('invoice');
            $entry->deferred_invoice = ($request->get('deferred_invoice') === 'true') ? 'on': 'off';
            $entry->supplier_id = $request->get('supplier_id');
            $entry->date_entry = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
            $entry->type_order = $request->get('type_order');
            $entry->observation = $request->get('observation');
            $entry->category_invoice_id = $request->get('category_invoice_id');
            $entry->currency_invoice = ($request->get('currency_invoice') === 'true') ? 'USD': 'PEN';
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

            $items = json_decode($request->get('items'));

            for ( $i=0; $i<sizeof($items); $i++ )
            {
                if ( $items[$i]->old == 0 )
                {
                    $detail_entry = DetailEntry::create([
                        'entry_id' => $entry->id,
                        'material_name' => $items[$i]->material,
                        'ordered_quantity' => $items[$i]->quantity,
                        'entered_quantity' => $items[$i]->quantity,
                        'unit_price' => (float) round((float)$items[$i]->price,2),
                        'material_unit' => $items[$i]->unit,
                        'total_detail' => (float) $items[$i]->total
                    ]);
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificacion de facturas finanzas',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Factura por compra/servicio modificada con éxito.'], 200);

    }

    public function destroyDetailInvoice(Request $request, $idDetail)
    {
        DB::beginTransaction();
        try {
            $detail = DetailEntry::find($idDetail);

            $detail->delete();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Detalle de factura eliminado.'], 200);

    }

    public function destroyInvoiceO( Request $request, $id )
    {
        DB::beginTransaction();
        try {
            $entry = Entry::find($id);

            foreach ( $entry->details as $detail )
            {
                $detail->delete();
            }

            $entry->delete();

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Factura eliminada.'], 200);

    }

    public function destroyInvoice(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $entry = Entry::with('details')->findOrFail($id);

            if ((int) $entry->finance !== 1) {
                throw new \Exception('Solo se pueden anular facturas financieras desde esta opción.');
            }

            if ((int) ($entry->state_annulled ?? 0) === 1) {
                throw new \Exception('La factura ya fue anulada previamente.');
            }

            $movements = CashMovement::where('entry_id', $entry->id)
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                if ($movement->type === 'expense') {
                    CashMovement::create([
                        'cash_register_id' => $movement->cash_register_id,
                        'entry_id' => $entry->id,
                        'sale_id' => null,
                        'type' => 'income',
                        'amount' => $movement->amount,
                        'description' => 'Reversión de egreso por anulación de factura financiera',
                        'observation' => 'Anulación de factura: ' . ($entry->invoice ?? ''),
                        'regularize' => $movement->regularize,
                        'cash_box_subtype_id' => $movement->cash_box_subtype_id,
                    ]);

                    if ((int) $movement->regularize === 1) {
                        $cashRegister = CashRegister::find($movement->cash_register_id);

                        if ($cashRegister) {
                            $cashRegister->current_balance += (float) $movement->amount;
                            $cashRegister->total_incomes += (float) $movement->amount;
                            $cashRegister->total_expenses -= (float) $movement->amount;
                            $cashRegister->save();
                        }
                    }
                }
            }

            $fecha = Carbon::now()->format('d/m/Y');

            $entry->observation = trim(($entry->observation ?? '') . " | ANULADA EL DÍA {$fecha}");
            $entry->state_annulled = 1;
            $entry->save();

            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Anular factura financiera',
                'time' => 0
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Factura financiera anulada correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getJsonInvoicesFinance()
    {
        $begin = microtime(true);
        $entries = Entry::with('supplier')
            ->with('category_invoice')
            ->where('finance', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        $array = [];
        foreach ( $entries as $entry )
        {
            array_push($array, $entry);
        }
        //dd(datatables($entries)->toJson());
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener facturas finanzas',
            'time' => $end
        ]);
        return datatables($array)->toJson();
    }

    public function reportInvoiceFinance()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('invoice.report_invoice', compact('permissions'));
    }

    public function reportInvoiceFinanceSinOrden()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('invoice.report_invoice_sin_orden', compact('permissions'));
    }

    public function getJsonInvoicesFinanceSinOrden()
    {
        $begin = microtime(true);
        $entries = Entry::with('supplier')
            ->with('category_invoice')
            ->where('finance', 1)
            ->where('purchase_order', '=',null)
            ->whereIn('type_order', ['purchase', 'service'])
            ->orderBy('created_at', 'desc')
            ->get();

        //dd(datatables($entries)->toJson());
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Obtener facturas finanzas',
            'time' => $end
        ]);
        return datatables($entries)->toJson();
    }

    public function exportInvoices()
    {
        $begin = microtime(true);
        //dd($request);
        $start = $_GET['start'];
        $end = $_GET['end'];;
        //dump($start);
        //dump($end);
        $invoices_array = [];
        $dates = '';

        if ( $start == '' || $end == '' )
        {
            //dump('Descargar todos');
            $dates = 'TOTALES';
            $invoices = Entry::with('supplier')
                ->with('category_invoice')
                ->where('finance', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ( $invoices as $invoice )
            {
                $date_entry = Carbon::createFromFormat('Y-m-d H:i:s', $invoice->date_entry)->format('d-m-Y');
                array_push($invoices_array, [
                    'date' => $date_entry,
                    'order' => ($invoice->purchase_order != null) ? $invoice->purchase_order:'No tiene',
                    'invoice' => $invoice->invoice,
                    'type_order' => ($invoice->type_order == 'purchase' || $invoice->type_order == null) ? 'Por compra':'Por servicio',
                    'supplier' => ($invoice->supplier_id != null) ? $invoice->supplier->business_name:'No tiene',
                    'category' => ($invoice->category_invoice_id != null) ? $invoice->category_invoice->name:'No tiene',
                    'currency' => $invoice->currency_invoice,
                    'subtotal' => $invoice->sub_total,
                    'taxes' => $invoice->taxes,
                    'total' => $invoice->total,
                ]);
            }


        } else {
            $date_start = Carbon::createFromFormat('d/m/Y', $start);
            $end_start = Carbon::createFromFormat('d/m/Y', $end);

            $dates = 'DEL '. $start .' AL '. $end;
            $invoices = Entry::with('supplier')
                ->with('category_invoice')
                ->where('finance', 1)
                ->whereDate('date_entry', '>=',$date_start)
                ->whereDate('date_entry', '<=',$end_start)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ( $invoices as $invoice )
            {
                $date_entry = Carbon::createFromFormat('Y-m-d H:i:s', $invoice->date_entry)->format('d-m-Y');
                array_push($invoices_array, [
                    'date' => $date_entry,
                    'order' => ($invoice->purchase_order != null) ? $invoice->purchase_order:'No tiene',
                    'invoice' => $invoice->invoice,
                    'type_order' => ($invoice->type_order == 'purchase' || $invoice->type_order == null) ? 'Por compra':'Por servicio',
                    'supplier' => ($invoice->supplier_id != null) ? $invoice->supplier->business_name:'No tiene',
                    'category' => ($invoice->category_invoice_id != null) ? $invoice->category_invoice->name:'No tiene',
                    'currency' => $invoice->currency_invoice,
                    'subtotal' => $invoice->sub_total,
                    'taxes' => $invoice->taxes,
                    'total' => $invoice->total,
                ]);
            }

            //dump($date_start);
            //dump($end_start);
        }
        //dump($invoices_array);
        //dd('Fechas');
        //return response()->json(['message' => 'Reporte descargado correctamente.'], 200);
        //(new UsersExport)->download('users.xlsx');
        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Reporte Excel facturas finanzas',
            'time' => $end
        ]);
        return (new InvoicesFinanceExport($invoices_array, $dates))->download('facturasFinanzas.xlsx');

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
