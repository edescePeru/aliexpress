<?php

namespace App\Http\Controllers;

use App\CreditPay;
use App\DataGeneral;
use App\DateDimension;
use App\Entry;
use App\Exports\CreditsReportExcelExport;
use App\OrderPurchase;
use App\OrderPurchaseFinance;
use App\OrderService;
use App\SupplierCredit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Services\SettingService;

class SupplierCreditController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:control_credit')->only(['indexCredits', 'getOnlyInvoicesPurchase', 'getOnlyCreditsSupplier', 'indexInvoicesPending', 'getInvoicesPending', 'getSummaryDeudaPending', 'getInvoiceForExpire', 'getAmountInvoiceCurrentMonth', 'getAmountInvoiceGeneral', 'exportCreditsExcel']);
        $this->middleware('permission:control_credit|edit_credit|pay_credit|nopay_credit')->only(['getCreditById']);
        $this->middleware('permission:control_credit|pay_credit|nopay_credit')->only(['getPaysCredit']);
        $this->middleware('permission:edit_credit')->only(['addInvoiceToCredit', 'update', 'addDaysCredit']);
        $this->middleware('permission:pay_credit')->only(['paid', 'savePaysCredit']);
        $this->middleware('permission:nopay_credit')->only(['cancelPayCredit', 'deletePayCredit']);
        $this->middleware('permission:pay_credit|nopay_credit')->only(['changeStatusCredit']);
        $this->middleware(function ($request, $next) {
            $tenant = \App\Support\TenantContext::tenantIdOrNull();
            $company = \App\Support\TenantContext::companyIdOrNull();
            abort_unless($tenant && $company && \App\Company::where('tenant_id', $tenant)->whereKey($company)->exists(), 403, 'Selecciona una empresa válida para consultar créditos.');
            return $next($request);
        });
    }

    public function indexCredits()
    {
        $user = Auth::user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return view('credit.indexSupplierCredit', compact('permissions'));
    }

    public function getOnlyInvoicesPurchase()
    {
        $entries = Entry::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->with('supplier')
            ->doesntHave('credit')
            ->where('state_annulled', false)
            ->where('type_order', 'purchase')
            ->orderBy('created_at', 'desc')
            ->get();
        return datatables($entries)->toJson();
    }

    public function getOnlyCreditsSupplier()
    {
        $warning = app(SettingService::class)->get('supplier_credit.expiration_warning_days');
        $credits = SupplierCredit::with(['supplier', 'purchase', 'service', 'deadline'])
            ->orderBy('created_at', 'desc')->get();
        foreach ($credits as $credit) {
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, $warning);
        }
        return datatables($credits)->toJson();
    }

    public function addInvoiceToCredit($idEntry)
    {
        $result = DB::transaction(function () use ($idEntry) {
            $entry = Entry::where('tenant_id', \App\Support\TenantContext::tenantId())
                ->where('company_id', \App\Support\TenantContext::companyId())
                ->where('type_order', 'purchase')->lockForUpdate()->findOrFail($idEntry);
            if ($entry->state_annulled) {
                throw \Illuminate\Validation\ValidationException::withMessages(['entry' => 'La entrada está anulada.']);
            }
            $existing = SupplierCredit::where('entry_id', $entry->id)->lockForUpdate()->first();
            if ($existing) { return ['credit' => $existing, 'created' => false]; }
            if (!$entry->supplier_id || !\App\Supplier::whereKey($entry->supplier_id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['entry' => 'La entrada requiere un proveedor disponible en este tenant.']);
            }
            if (!in_array($entry->currency_invoice, ['PEN', 'USD'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['entry' => 'La moneda de la entrada debe ser PEN o USD.']);
            }
            // Evitar crear otra deuda sobre una orden con crédito aún sin entrada asociada.
            if ($entry->order_purchase_id && SupplierCredit::where('order_purchase_id', $entry->order_purchase_id)->whereNull('entry_id')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['entry' => 'La orden ya tiene un crédito sin entrada asociada. Revisa su vínculo antes de crear otra deuda.']);
            }
            $total = $entry->total;
            $credit = SupplierCredit::create([
                'supplier_id' => $entry->supplier_id, 'entry_id' => $entry->id,
                'invoice' => $entry->invoice, 'image_invoice' => $entry->image,
                'total_soles' => $entry->currency_invoice === 'PEN' ? $total : null,
                'total_dollars' => $entry->currency_invoice === 'USD' ? $total : null,
                'date_issue' => $entry->date_entry,
            ]);
            return ['credit' => $credit, 'created' => true];
        });
        return response()->json([
            'message' => $result['created'] ? 'Factura agregada con éxito.' : 'La factura ya estaba asociada a un crédito.',
            'credit_id' => $result['credit']->id, 'created' => $result['created'],
        ], 200);
    }

    public function getCreditById( $credit_id )
    {
        $credit = SupplierCredit::with('supplier')
            ->with('purchase')
            ->with('service')
            ->with('deadline')
            ->findOrFail($credit_id);

        return response()->json(['credit' => $credit], 200);
    }

    public function edit(Credit $credit)
    {
        //
    }

    public function update(Request $request)
    {
        \Illuminate\Support\Facades\Validator::make($request->all(), [
            'credit_id' => 'required|integer|min:1',
            'date_issue' => 'required|date_format:d/m/Y',
            'date_expiration' => 'required|date_format:d/m/Y|after_or_equal:date_issue',
            'observation' => 'nullable|string',
        ])->validate();
        $warning = app(SettingService::class)->get('supplier_credit.expiration_warning_days');
        DB::transaction(function () use ($request, $warning) {
            $credit = SupplierCredit::lockForUpdate()->findOrFail($request->input('credit_id'));
            $this->requireCreditInvoice($credit);
            $credit->date_issue = Carbon::createFromFormat('d/m/Y', $request->input('date_issue'), 'America/Lima')->toDateString();
            $credit->date_expiration = Carbon::createFromFormat('d/m/Y', $request->input('date_expiration'), 'America/Lima')->toDateString();
            $credit->observation = $request->input('observation');
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, $warning);
            $credit->save();
        });
        return response()->json(['message' => 'Crédito modificado correctamente'], 200);
    }

    public function paid(Request $request)
    {
        \Illuminate\Support\Facades\Validator::make($request->all(), [
            'credit_id' => 'required|integer|min:1', 'date_paid' => 'required|date_format:d/m/Y',
            'observation2' => 'nullable|string', 'image_paid' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ])->validate();
        $createdFile = null;
        try {
            DB::transaction(function () use ($request, &$createdFile) {
                $credit = SupplierCredit::lockForUpdate()->findOrFail($request->input('credit_id'));
                $this->requireCreditInvoice($credit);
                if (!$credit->date_expiration) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'El crédito requiere fecha de vencimiento.']);
                }
                if ($credit->state_credit === 'paid_out') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'El crédito ya tiene una marca de pago.']);
                }
                $credit->date_paid = Carbon::createFromFormat('d/m/Y', $request->input('date_paid'), 'America/Lima')->toDateString();
                $credit->observation_extra = $request->input('observation2');
                $credit->state_credit = 'paid_out';
                if ($request->hasFile('image_paid')) {
                    $file = $request->file('image_paid');
                    $directory = public_path('images/credits');
                    if (!is_dir($directory)) { \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true); }
                    $pdf = $file->getMimeType() === 'application/pdf';
                    $filename = (string) \Illuminate\Support\Str::uuid().($pdf ? '.pdf' : '.jpg');
                    $createdFile = $directory.DIRECTORY_SEPARATOR.$filename;
                    if ($pdf) { $file->move($directory, $filename); }
                    else { Image::make($file)->orientate()->save($createdFile, 80, 'jpg'); }
                    $credit->image_paid = $filename;
                }
                $credit->save();
            });
        } catch (\Throwable $e) {
            if ($createdFile && is_file($createdFile)) { \Illuminate\Support\Facades\File::delete($createdFile); }
            throw $e;
        }
        return response()->json(['message' => 'Crédito pagado correctamente'], 200);
    }

    public function cancelPayCredit($idCredit)
    {
        $warning = app(SettingService::class)->get('supplier_credit.expiration_warning_days');
        DB::transaction(function () use ($idCredit, $warning) {
            $credit = SupplierCredit::lockForUpdate()->findOrFail($idCredit);
            $this->requireCreditInvoice($credit);
            if (!$credit->date_expiration || $credit->state_credit !== 'paid_out') {
                throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'No existe una marca de pago con vencimiento que anular.']);
            }
            if ($credit->state_pay === 'canceled') {
                throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'Primero revierte la marca de estado cancelado; ambas marcas deben revisarse por separado.']);
            }
            // Conservar archivo y referencia documental; no borrar abonos ni fabricar un egreso.
            $credit->date_paid = null;
            $credit->observation_extra = '';
            $credit->state_credit = 'outstanding';
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, $warning);
            $credit->save();
        });
        return response()->json(['message' => 'Marca de pago anulada correctamente'], 200);
    }

    public function indexInvoicesPending()
    {
        $user = Auth::user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        $years = DateDimension::distinct()->get(['year']);

        return view('credit.indexInvoicesPending', compact('permissions', 'years'));

    }

    public function getInvoicesPending()
    {
        $expirationWarningDays = app(SettingService::class)->get('supplier_credit.expiration_warning_days');

        $credits = SupplierCredit::with('supplier')
            ->with('purchase')
            ->with('service')
            ->with('deadline')
            ->orderBy('date_issue', 'desc')
            ->get();

        foreach ($credits as $credit) {
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, $expirationWarningDays);
        }

        $arrayOrders = [];

        foreach ( $credits as $credit )
        {
            $balance = \App\Services\Purchases\SupplierCreditBalance::forCredit($credit);
            $pagos = $balance['paid'];
            $url = $this->supplierCreditDocumentUrl($credit);

            array_push($arrayOrders, [
                "id" => $credit->id,
                'url' => $url,
                "stateCredit" => $credit->state_credit,
                "order" => substr(trim((string) $credit->code_order), 0, 2),
                "correlativo" => trim((string) $credit->code_order),
                "proveedor" => !$credit->supplier ? 'Sin proveedor':$credit->supplier->business_name,
                "moneda" => ($credit->total_soles != null) ? 'Soles':'Dólares',
                "condicion" => !$credit->deadline ? 'Sin condición':$credit->deadline->description,
                "montoDolares" => ($credit->total_dollars == null) ? '':$credit->total_dollars,
                "montoSoles" => ($credit->total_soles == null) ? '':$credit->total_soles,
                "adelanto" => $pagos,
                "legacyAdvancePendingReview" => $balance['legacy_review'],
                "deudaActualDolares" => $balance['currency'] === 'USD' ? $balance['balance'] : '',
                "deudaActualSoles" => $balance['currency'] === 'PEN' ? $balance['balance'] : '',
                "deudaActual" => $balance['balance'],
                "factura" => ($credit->invoice == null) ? 'PENDIENTE':$credit->invoice,
                "fechaEmision" => ($credit->date_issue == null) ? '': $credit->date_issue->format('d/m/Y'),
                "fechaVencimiento" => ($credit->date_expiration == null) ? '': $credit->date_expiration->format('d/m/Y'),
                "estado" => ($credit->days_to_expiration === null) ? "": $credit->days_to_expiration ." DÍAS",
                "estadoPago" => $credit->state_pay,
                "fechaPago" => ($credit->date_paid == null) ? '': $credit->date_paid->format('d/m/Y'),
                "observaciones" => ($credit->observation == null) ? "": $credit->observation
            ]);
        }


        return datatables($arrayOrders)->toJson();
    }

    public function getPaysCredit( $credit_id )
    {
        $pays = CreditPay::where('supplier_credit_id', $credit_id)
            ->orderBy('date_pay', 'DESC')
            ->get();

        $arrayPays = [];
        foreach ( $pays as $pay )
        {
            array_push($arrayPays, [
                "id" => $pay->id,
                "type" => ($pay->image == null) ? 'img':substr($pay->image,  -3),
                "monto" => $pay->amount,
                "fecha" => ($pay->date_pay == null) ? '':$pay->date_pay->format('d/m/Y'),
                "comprobante" => ($pay->image == null) ? 'no_image.png':$pay->image
            ]);
        }

        return response()->json([
            "pays" => $arrayPays
        ]);
    }

    public function savePaysCredit(Request $request, $credit_id)
    {
        if ($request->input('fechaPago') === '') { $request->merge(['fechaPago' => null]); }
        \Illuminate\Support\Facades\Validator::make($request->all(), [
            'montoPago' => ['required', 'numeric', 'min:0.01', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'fechaPago' => ['nullable', 'date_format:d/m/Y'],
            'comprobantePago' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png'],
        ])->validate();
        $createdFile = null;
        try {
            $credit = DB::transaction(function () use ($request, $credit_id, &$createdFile) {
                $credit = SupplierCredit::lockForUpdate()->findOrFail($credit_id);
                $pay = CreditPay::create([
                    'supplier_credit_id' => $credit->id,
                    'amount' => $request->input('montoPago'),
                    'date_pay' => $request->filled('fechaPago') ? Carbon::createFromFormat('d/m/Y', $request->input('fechaPago'))->toDateString() : null,
                ]);
                if ($request->hasFile('comprobantePago')) {
                    $file = $request->file('comprobantePago');
                    $directory = public_path('images/credits/pays');
                    if (!is_dir($directory)) { \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true); }
                    $pdf = $file->getMimeType() === 'application/pdf';
                    $filename = (string) \Illuminate\Support\Str::uuid().($pdf ? '.pdf' : '.jpg');
                    $createdFile = $directory.DIRECTORY_SEPARATOR.$filename;
                    if ($pdf) { $file->move($directory, $filename); }
                    else { Image::make($file)->orientate()->save($createdFile, 80, 'jpg'); }
                    $pay->image = $filename;
                    $pay->save();
                }
                return $credit;
            });
        } catch (\Throwable $e) {
            // Únicamente el archivo nuevo de esta petición; nunca archivos históricos.
            if ($createdFile && is_file($createdFile)) { \Illuminate\Support\Facades\File::delete($createdFile); }
            throw $e;
        }
        return response()->json(['message' => 'Pago registrado con éxito.', 'credit' => $credit], 200);
    }

    public function deletePayCredit(Request $request, $credit_pay_id)
    {
        $credit = DB::transaction(function () use ($credit_pay_id) {
            $parentId = CreditPay::whereKey($credit_pay_id)->value('supplier_credit_id');
            abort_unless($parentId, 404);
            $credit = SupplierCredit::lockForUpdate()->findOrFail($parentId);
            $pay = CreditPay::where('supplier_credit_id', $credit->id)->lockForUpdate()->findOrFail($credit_pay_id);
            $pay->delete();
            return $credit;
        });
        return response()->json(['message' => 'Pago eliminado con éxito.', 'credit' => $credit], 200);
    }

    public function addDaysCredit(Request $request, $credit_id)
    {
        $warning = app(SettingService::class)->get('supplier_credit.expiration_warning_days');
        $credit = DB::transaction(function () use ($credit_id, $warning) {
            $credit = SupplierCredit::lockForUpdate()->findOrFail($credit_id);
            if (!$credit->date_expiration || $credit->state_credit === 'paid_out' || $credit->state_pay === 'canceled') {
                throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'El crédito requiere fecha de vencimiento y no debe estar marcado pagado.']);
            }
            $credit->date_expiration = $credit->date_expiration->copy()->addDays(7)->toDateString();
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, $warning);
            $credit->save();
            return $credit;
        });
        return response()->json(['message' => 'Se agregaron 7 días más a la fecha de expiración.', 'credit' => $credit], 200);
    }

    public function getSummaryDeudaPending()
    {
        // Marcas manuales no sustituyen dinero registrado en el historial de abonos.
        $credits = SupplierCredit::query()->get();
        $pen = 0; $usd = 0; $legacy = 0;
        foreach ($credits as $credit) {
            $value = \App\Services\Purchases\SupplierCreditBalance::forCredit($credit);
            $cents = (int) round($value['balance'] * 100);
            if ($value['currency'] === 'PEN') { $pen += $cents; } else { $usd += $cents; }
            if ($value['legacy_review']) { $legacy++; }
        }
        return response()->json([
            'deudaSoles' => $pen / 100, 'deudaDolares' => $usd / 100,
            'legacyAdvancePendingReviewCount' => $legacy,
            'balanceSource' => 'credit_pays',
        ], 200);
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

    public function changeStatusCredit($credit_id, $status)
    {
        \Illuminate\Support\Facades\Validator::make(['status' => $status], [
            'status' => 'required|in:pending,pending50,canceled',
        ])->validate();
        return DB::transaction(function () use ($credit_id, $status) {
            $credit = SupplierCredit::lockForUpdate()->findOrFail($credit_id);
            $before = $credit->state_pay;
            if ($before !== $status) {
                // Salir de cancelado o volver a pendiente revierte una marca de pago.
                if ($before === 'canceled' || $status === 'pending') {
                    abort_unless(Auth::user()->can('nopay_credit'), 403);
                }
                // Marcar parcial/cancelado requiere permiso de pago.
                if ($status === 'pending50' || $status === 'canceled') {
                    abort_unless(Auth::user()->can('pay_credit'), 403);
                }
                $credit->state_pay = $status;
                $credit->save();
            }
            return response()->json(['message' => 'Estado modificado.'], 200);
        });
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

    public function getInvoiceForExpire()
    {
        $warning = app(SettingService::class)->get('supplier_credit.expiration_warning_days');
        $credits = SupplierCredit::with('supplier')->whereNotNull('date_expiration')->get();
        $array = [];
        foreach ($credits as $credit) {
            $value = \App\Services\Purchases\SupplierCreditExpiration::evaluate($credit, $warning);
            if (!$value['eligible'] || $value['days'] < 0 || $value['days'] >= max(0, (int) $warning)) { continue; }
            $array[] = [
                'orden' => $credit->code_order,
                'proveedor' => $credit->supplier ? $credit->supplier->business_name : 'No tiene',
                'factura' => $credit->invoice === null ? 'No tiene' : $credit->invoice,
                'fecha_vencimiento' => $credit->date_expiration->format('d/m/Y'),
                'vence_en' => $value['days'].' DÍAS',
            ];
        }
        return response()->json(['credits' => $array]);
    }

    public function getAmountInvoiceCurrentMonth()
    {
        $fecha_actual = Carbon::now("America/Lima");
        $currentYear = $fecha_actual->year;
        $currentMonth = $fecha_actual->month;

        $supplier_credits = SupplierCredit::whereYear('date_issue', $currentYear)
            ->whereMonth('date_issue', $currentMonth)
            ->get();

        $amountSoles = 0;
        $amountDollars = 0;

        foreach ($supplier_credits as $credit)
        {
            $amountSoles = $amountSoles + (($credit->total_soles == null) ? 0: $credit->total_soles);
            $amountDollars = $amountDollars + (($credit->total_dollars == null) ? 0: $credit->total_dollars);
        }

        return response()->json([
            "amountSoles" => $amountSoles,
            "amountDolares" => $amountDollars
        ]);

    }

    public function getAmountInvoiceGeneral()
    {
        $filters = \Illuminate\Support\Facades\Validator::make(request()->query(), [
            'year' => 'required|integer|between:1,9999',
            'month' => 'required|integer|between:1,12',
        ])->validate();
        $year = $filters['year'];
        $month = $filters['month'];

        $supplier_credits = SupplierCredit::whereYear('date_issue', $year)
            ->whereMonth('date_issue', $month)
            ->get();

        $amountSoles = 0;
        $amountDollars = 0;

        foreach ($supplier_credits as $credit)
        {
            $amountSoles = $amountSoles + (($credit->total_soles == null) ? 0: $credit->total_soles);
            $amountDollars = $amountDollars + (($credit->total_dollars == null) ? 0: $credit->total_dollars);
        }

        return response()->json([
            "amountSoles" => $amountSoles,
            "amountDolares" => $amountDollars
        ]);

    }

    public function exportCreditsExcel()
    {
        //dd($request);
        $filters = \Illuminate\Support\Facades\Validator::make(request()->query(), [
            'start' => 'required|date_format:d/m/Y',
            'end' => 'required|date_format:d/m/Y|after_or_equal:start',
        ])->validate();
        $start = $filters['start'];
        $end = $filters['end'];

        $date_start = Carbon::createFromFormat('d/m/Y', $start);
        $end_start = Carbon::createFromFormat('d/m/Y', $end);

        $dates = 'CREDITOS DEL '. $start .' AL '. $end;
        $arrayOrders = [];

        $credits = SupplierCredit::with('supplier')
            ->with('purchase')
            ->with('service')
            ->with('finance')
            ->with('deadline')
            ->whereDate('date_issue', '>=',$date_start)
            ->whereDate('date_issue', '<=',$end_start)
            ->orderBy('date_issue', 'desc')
            ->get();


        foreach ( $credits as $credit )
        {
            \App\Services\Purchases\SupplierCreditExpiration::project($credit, app(SettingService::class)->get('supplier_credit.expiration_warning_days'));
            $balance = \App\Services\Purchases\SupplierCreditBalance::forCredit($credit);
            $pagos = $balance['paid'];

            if ( $credit->state_pay == 'pending' )
            {
                $estadopago = "PENDIENTE";
            } elseif ($credit->state_pay == 'pending50') {
                $estadopago = "PENDIENTE/CANCELADO 50%";
            } else {
                $estadopago = "CANCELADO";
            }
            array_push($arrayOrders, [
                "id" => $credit->id,
                "stateCredit" => $credit->state_credit,
                "order" => substr(trim((string) $credit->code_order), 0, 2),
                "correlativo" => trim((string) $credit->code_order),
                "proveedor" => !$credit->supplier ? 'Sin proveedor':$credit->supplier->business_name,
                "moneda" => ($credit->total_soles != null) ? 'Soles':'Dólares',
                "condicion" => !$credit->deadline ? 'Sin condición':$credit->deadline->description,
                "montoDolares" => ($credit->total_dollars == null) ? '':$credit->total_dollars,
                "montoSoles" => ($credit->total_soles == null) ? '':$credit->total_soles,
                "adelanto" => $pagos,
                "legacyAdvancePendingReview" => $balance['legacy_review'],
                "deudaActualDolares" => $balance['currency'] === 'USD' ? $balance['balance'] : '',
                "deudaActualSoles" => $balance['currency'] === 'PEN' ? $balance['balance'] : '',
                "deudaActual" => $balance['balance'],
                "factura" => ($credit->invoice == null) ? 'PENDIENTE':$credit->invoice,
                "fechaEmision" => ($credit->date_issue == null) ? '': $credit->date_issue->format('d/m/Y'),
                "fechaVencimiento" => ($credit->date_expiration == null) ? '': $credit->date_expiration->format('d/m/Y'),
                "estado" => ($credit->days_to_expiration === null) ? "": $credit->days_to_expiration ." DÍAS",
                "estadoPago" => $estadopago,
                "fechaPago" => ($credit->date_paid == null) ? '': $credit->date_paid->format('d/m/Y'),
                "observaciones" => ($credit->observation == null) ? "": $credit->observation
            ]);
        }

        //dump($invoices_array);
        //dd('Fechas');
        //return response()->json(['message' => 'Reporte descargado correctamente.'], 200);
        //(new UsersExport)->download('users.xlsx');
        return (new CreditsReportExcelExport($arrayOrders, $dates))->download('reporteCreditos.xlsx');

    }

    private function requireCreditInvoice($credit)
    {
        if (trim((string) $credit->invoice) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['credit' => 'El crédito no tiene una factura asociada.']);
        }
    }

    private function supplierCreditDocumentUrl($credit)
    {
        $tenant = \App\Support\TenantContext::tenantId();
        $company = \App\Support\TenantContext::companyId();
        if ($credit->entry_id) {
            $entry = Entry::where('tenant_id', $tenant)->where('company_id', $company)->find($credit->entry_id);
            if ($entry && !$entry->finance && Auth::user()->can('list_entryPurchase')) {
                return route('entry.purchase.show', [$entry->id]);
            }
        }
        if ($credit->order_purchase_finance_id && Auth::user()->can('list_orderPurchaseFinance')) {
            $order = OrderPurchaseFinance::find($credit->order_purchase_finance_id);
            if ($order) { return route('show.order.purchase.finance', [$order->id]); }
        }
        if ($credit->order_purchase_id && Auth::user()->can('list_orderPurchaseNormal')) {
            $order = OrderPurchase::find($credit->order_purchase_id);
            if ($order) { return url('/dashboard/ver/orden/compra/normal/'.$order->id); }
        }
        // No adivinar por invoice/código ni enlazar a un servicio legacy sin contexto.
        return '';
    }
}
