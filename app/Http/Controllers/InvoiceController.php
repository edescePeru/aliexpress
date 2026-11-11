<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Services\FinanceInvoiceContext;
use App\Services\FinanceInvoiceAmounts;
use App\Support\TenantContext;
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
        $categories = CategoryInvoice::forCurrentCompany()->orderBy('name')->get();

        $context = app(FinanceInvoiceContext::class);
        $sessionBoxes = $context->sessions()->groupBy('cash_box_id')->havingRaw('COUNT(*) = 1')->select('cash_box_id');
        $cashBoxes = $context->boxes()->whereIn('id', $sessionBoxes)->orderBy('position')
            ->get(['id', 'name', 'type', 'uses_subtypes', 'currency']);
        $subtypes = $context->subtypes()->orderBy('position')->get(['id', 'name', 'cash_box_id']);

        return view('invoice.create_invoice', compact('suppliers', 'unitMeasures', 'categories', 'cashBoxes','subtypes'));
    }

    private function financeEntries()
    {
        return Entry::query()->where(app(FinanceInvoiceContext::class)->ids())->where('finance', true);
    }

    private function lockFinanceCategory($id)
    {
        return $id ? CategoryInvoice::forCurrentCompany()->lockForUpdate()->findOrFail($id)->id : null;
    }

    public function storeInvoice(StoreInvoiceRequest $request)
    {
        $begin = microtime(true);
        //dd($request->get('deferred_invoice'));
        $validated = $request->validated();
        $context = app(FinanceInvoiceContext::class);
        $lines = app(FinanceInvoiceAmounts::class)->lines($validated['items']);
        $amount = array_sum(array_map(function ($line) {
            return (int) round($line['total_detail'] * 100);
        }, $lines)) / 100;

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
            [$cashBox, $subtype, $cashRegister] = $context->payment(
                $validated['pv_cash_box_id'], $validated['pv_cash_box_subtype_id'] ?? null, $tipoMoneda
            );
            $categoryId = $this->lockFinanceCategory($validated['category_invoice_id'] ?? null);
            $entry = Entry::create($context->ids() + [
                'purchase_order' => null,
                'invoice' => $request->get('invoice'),
                'deferred_invoice' => 'off',
                'supplier_id' => $request->get('supplier_id'),
                'entry_type' => 'Por compra',
                'type_order' => $request->get('type_order'),
                'date_entry' => Carbon::createFromFormat('d/m/Y', $request->get('date_invoice')),
                'finance' => true,
                'currency_invoice' => $tipoMoneda,
                'currency_compra' => (float) $precioCompra,
                'currency_venta' => (float) $precioVenta,
                'observation' => $request->get('observation'),
                'category_invoice_id' => $categoryId,
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

            foreach ($lines as $line) {
                DetailEntry::create($context->ids() + ['entry_id' => $entry->id] + $line);
            }

            CashMovement::create($context->ids() + [
                'branch_id' => TenantContext::branchId(),
                'user_id' => Auth::id(),
                'cash_box_id' => $cashBox->id,
                'cash_register_id' => $cashRegister->id,
                'cash_box_subtype_id' => $subtype ? $subtype->id : null,
                'entry_id' => $entry->id,
                'type' => 'expense',
                'amount' => $amount,
                'description' => 'Pago de factura/servicio - ' . $cashBox->name,
                'observation' => 'Factura: ' . $entry->invoice,
                'regularize' => true,
            ]);
            $cashRegister->current_balance = bcsub((string) $cashRegister->current_balance, (string) $amount, 2);
            $cashRegister->total_expenses = bcadd((string) $cashRegister->total_expenses, (string) $amount, 2);
            $cashRegister->save();
            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar factura finanza',
                'time' => $end
            ]);

            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Factura por compra/servicio guardada con éxito.'], 200);

    }

    public function getJsonInvoices()
    {
        $begin = microtime(true);
        $dateCurrent = Carbon::now('America/Lima');
        $entries = $this->financeEntries()->with(['supplier', 'details'])->with('category_invoice')
            //->where('date_entry', '>=', $date4MonthAgo)
            ->where('entry_type', 'Por compra')
            ->where('state_annulled', 0)
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

    public function getJsonInvoicesAnnulled()
    {
        $begin = microtime(true);
        $dateCurrent = Carbon::now('America/Lima');
        $entries = $this->financeEntries()->with(['supplier', 'details'])->with('category_invoice')
            //->where('date_entry', '>=', $date4MonthAgo)
            ->where('entry_type', 'Por compra')
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
            ->where(app(FinanceInvoiceContext::class)->ids())
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

        $entries = $this->financeEntries()->with(['supplier', 'details'])
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
        $this->financeEntries()->findOrFail($entry->id);
        $suppliers = Supplier::all();
        $unitMeasures = UnitMeasure::all();
        $categories = CategoryInvoice::forCurrentCompany()->orderBy('name')->get();
        return view('invoice.show_invoice', compact('entry', 'suppliers', 'categories', 'unitMeasures'));
    }

    public function editInvoice(Entry $entry)
    {
        $this->financeEntries()->findOrFail($entry->id);
        $suppliers = Supplier::all();
        $unitMeasures = UnitMeasure::all();
        $categories = CategoryInvoice::forCurrentCompany()->orderBy('name')->get();
        return view('invoice.edit_invoice', compact('entry', 'suppliers', 'categories', 'unitMeasures'));
    }

    public function updateInvoice(UpdateInvoiceRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        $items = json_decode((string) $request->get('items', '[]'), true);
        if (!is_array($items) || collect($items)->contains(function ($item) {
            return !is_array($item) || (int) ($item['old'] ?? 0) !== 1;
        })) {
            return response()->json(['message' => 'Para agregar líneas, anule la factura y registre otra.'], 422);
        }
        DB::beginTransaction();
        try {
            $entry = $this->financeEntries()->where('state_annulled', false)->lockForUpdate()->findOrFail($request->get('entry_id'));
            $entry->purchase_order = $request->get('purchase_order');
            $entry->invoice = $request->get('invoice');
            $entry->deferred_invoice = ($request->get('deferred_invoice') === 'true') ? 'on': 'off';
            $entry->supplier_id = $request->get('supplier_id');
            $entry->date_entry = Carbon::createFromFormat('d/m/Y', $request->get('date_invoice'));
            $entry->type_order = $request->get('type_order');
            $entry->observation = $request->get('observation');
            $entry->category_invoice_id = $this->lockFinanceCategory($validated['category_invoice_id'] ?? null);
            $currency = $request->get('currency_invoice') === 'true' ? 'USD' : 'PEN';
            if ($currency !== $entry->currency_invoice) {
                throw new \RuntimeException('Para cambiar la moneda, anule la factura y registre otra.');
            }
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
        return response()->json(['message' => 'Para cambiar los importes, anule la factura financiera y registre otra.'], 422);
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
            $entry = $this->financeEntries()->lockForUpdate()->findOrFail($id);

            if ((int) $entry->finance !== 1) {
                throw new \Exception('Solo se pueden anular facturas financieras desde esta opción.');
            }

            if ((int) ($entry->state_annulled ?? 0) === 1) {
                throw new \Exception('La factura ya fue anulada previamente.');
            }

            $context = app(FinanceInvoiceContext::class);
            $movements = CashMovement::where('entry_id', $entry->id)->where('type', 'expense')
                ->lockForUpdate()->get();
            if ($movements->count() !== 1) {
                throw new \RuntimeException('El egreso de la factura requiere conciliación.');
            }
            $movement = $movements->first();
            if (!$movement->regularize || (int) $movement->company_id !== (int) $entry->company_id
                || (int) $movement->branch_id !== (int) TenantContext::branchId()
                || CashMovement::where('cash_movement_origin_id', $movement->id)->where('type', 'income')->exists()) {
                throw new \RuntimeException('Anule desde el local del egreso. Si falta contexto o ya existe una reversión, concilie primero.');
            }
            [$box, $subtype, $register] = $context->payment(
                $movement->cash_box_id, $movement->cash_box_subtype_id, $entry->currency_invoice
            );
            CashMovement::create($context->ids() + [
                'branch_id' => TenantContext::branchId(),
                'user_id' => Auth::id(),
                'cash_box_id' => $box->id,
                'cash_register_id' => $register->id,
                'cash_box_subtype_id' => $subtype ? $subtype->id : null,
                'cash_movement_origin_id' => $movement->id,
                'entry_id' => $entry->id,
                'type' => 'income',
                'amount' => $movement->amount,
                'description' => 'Reversión de egreso por anulación de factura financiera',
                'regularize' => true,
            ]);
            $register->current_balance = bcadd((string) $register->current_balance, (string) $movement->amount, 2);
            $register->total_incomes = bcadd((string) $register->total_incomes, (string) $movement->amount, 2);
            $register->save();

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
        $entries = $this->financeEntries()->with(['supplier', 'details'])
            ->with('category_invoice')
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
        $entries = $this->financeEntries()->with(['supplier', 'details'])
            ->with('category_invoice')
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
            $invoices = $this->financeEntries()->with(['supplier', 'details'])
                ->with('category_invoice')
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
                    'category' => optional($invoice->category_invoice)->name ?? 'No tiene',
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
            $invoices = $this->financeEntries()->with(['supplier', 'details'])
                ->with('category_invoice')
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
                    'category' => optional($invoice->category_invoice)->name ?? 'No tiene',
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
