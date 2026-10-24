<?php
namespace App\Http\Controllers;

use App\Http\Requests\{StorePaymentDeadlineRequest, UpdatePaymentDeadlineRequest, DeletePaymentDeadlineRequest};
use App\PaymentDeadline;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentDeadlineController extends Controller
{
    public function index()
    {
        $permissions = Auth::user()->getAllPermissions()->pluck('name')->toArray();
        return view('paymentDeadline.index', compact('permissions'));
    }
    public function create() { TenantContext::tenantId(); TenantContext::companyId(); return view('paymentDeadline.create'); }

    public function store(StorePaymentDeadlineRequest $request)
    {
        $request->validated();
        DB::transaction(function () use ($request) {
            PaymentDeadline::create($request->only(['description', 'days', 'type', 'credit', 'is_active']));
        });
        return response()->json(['message' => 'Plazo de pago guardado correctamente.', 'url' => route('paymentDeadline.index')]);
    }
    public function edit($id)
    {
        $paymentDeadline = PaymentDeadline::findOrFail($id);
        return view('paymentDeadline.edit', compact('paymentDeadline'));
    }
    public function update(UpdatePaymentDeadlineRequest $request)
    {
        $request->validated();
        DB::transaction(function () use ($request) {
            $deadline = PaymentDeadline::lockForUpdate()->findOrFail($request->input('paymentDeadline_id'));
            $deadline->fill($request->only(['description', 'days', 'type', 'credit', 'is_active']));
            $deadline->save();
        });
        return response()->json(['message' => 'Plazo de pago actualizado correctamente.', 'url' => route('paymentDeadline.index')]);
    }
    public function destroy(DeletePaymentDeadlineRequest $request)
    {
        $request->validated();
        DB::transaction(function () use ($request) {
            $deadline = PaymentDeadline::lockForUpdate()->findOrFail($request->input('paymentDeadline_id'));
            $deadline->is_active = false; $deadline->save();
        });
        return response()->json(['message' => 'Plazo de pago desactivado correctamente.']);
    }
    public function getPaymentDeadlines()
    {
        return datatables(PaymentDeadline::query()->select('id', 'description', 'days', 'type', 'credit', 'is_active')->get())->toJson();
    }
}
