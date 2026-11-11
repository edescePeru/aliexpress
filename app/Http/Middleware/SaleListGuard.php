<?php
namespace App\Http\Middleware;

use App\Services\Sales\SaleListAccess;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Closure;

class SaleListGuard
{
    public function handle($request, Closure $next, string $operation = 'read', string $source = '', string $key = '')
    {
        $access = app(SaleListAccess::class); $access->context($operation === 'errors');
        $permissions = ['print' => 'print_puntoVenta', 'dispatch' => 'updateDispatch_puntoVenta', 'payments' => 'managePayments_puntoVenta',
            'documents' => 'manageDocuments_puntoVenta', 'emit' => 'manageDocuments_puntoVenta', 'annul' => 'annul_puntoVenta'];
        if (isset($permissions[$operation])) { $access->permission($permissions[$operation]); }
        if ((!$request->isMethod('GET') && !$request->isMethod('HEAD')) || $request->header('X-Sales-Tenant') !== null
            || in_array($request->route()->getActionMethod(), ['getSalesAdmin', 'getSalesErrorAdmin'], true)) { $access->snapshot($request); }
        $id = null;
        if ($source === 'route') { $id = $request->route($key); if (is_object($id) && method_exists($id, 'getKey')) { $id = $id->getKey(); } }
        elseif ($source === 'input') { $id = $request->input($key); }
        elseif ($source === 'payment') {
            $payment = DB::table('sale_partial_payments')->where('id', $request->route($key))->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('branch_id', TenantContext::branchId())->first(['sale_id', 'cash_movement_id']);
            abort_unless($payment, 404); $id = $payment->sale_id;
            $movement = DB::table('cash_movements')->where('id', $payment->cash_movement_id)->where('sale_id', $id)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->first(['cash_register_id']);
            abort_unless($movement && DB::table('cash_registers')->where('id', $movement->cash_register_id)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('branch_id', TenantContext::branchId())->exists(), 404);
        }
        $sale = $source !== '' ? $access->sale($id) : null;
        if ($sale && ($operation === 'annul' || ($operation === 'payments' && $request->isMethod('DELETE')))) {
            $method = $request->route()->getActionMethod();
            $supportsFullRefund = $operation === 'annul' && in_array($method, [
                    'anularOrder', 'consultarAnulacion', 'generarNotaCreditoTotal',
                    'consultarNotaCredito', 'annul', 'consultAnnulment',
                ], true);
            if ($supportsFullRefund) {
                app(\App\Services\Sales\DeferredSaleRefund::class)->plan($sale);
            } else {
                app(\App\Services\CashRegularizationGuard::class)->sale($sale);
            }
        }
        if ($operation === 'emit') {
            $type = $request->input('tipo_comprobante', $sale->type_document);
            if (in_array($type, ['factura', '01'], true)) { $access->permission('generarFactura_puntoVenta'); }
            elseif (in_array($type, ['boleta', '03'], true)) { $access->permission('generarBoleta_puntoVenta'); }
            else { throw ValidationException::withMessages(['type_document' => 'Seleccione Boleta o Factura.']); }
        }
        if ($sale && in_array($operation, ['emit', 'annul', 'documents'], true) && !$request->isMethod('GET') && $sale->requiresEmissionReview()) {
            // Reconciliation/file recovery must remain available to authorized operators.
            $method = $request->route()->getActionMethod();
            if (!in_array($method, ['recuperarArchivosNubefact', 'consultarAnulacion', 'consultarNotaCredito', 'consultAnnulment'], true)) {
                throw ValidationException::withMessages(['sale_id' => 'La emisión requiere conciliación antes de modificar, emitir, anular o descartar.']);
            }
        }
        if ($operation === 'payments' && $request->isMethod('POST') && $request->filled('cash_box_id')) {
            $request->validate(['cash_box_id' => 'required|integer|min:1', 'cash_box_subtype_id' => 'nullable|integer|min:1']);
            abort_unless($access->boxes()->where('id', $request->input('cash_box_id'))->exists(), 404);
            if ($request->filled('cash_box_subtype_id')) {
                abort_unless($access->subtypes()->where('id', $request->input('cash_box_subtype_id'))->where(function ($q) use ($request) { $q->whereNull('cash_box_id')->orWhere('cash_box_id', $request->input('cash_box_id')); })->exists(), 404);
            }
        }
        return $next($request);
    }

}
