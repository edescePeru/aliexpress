<?php

namespace App\Services\Purchases;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FinancePurchaseInput
{
    public static function validate(Request $request, $order = null, $regularize = false)
    {
        $tenant = TenantContext::tenantId();
        $company = TenantContext::companyId();
        // Los formularios legacy envían cadenas vacías para referencias opcionales.
        foreach (['supplier_id', 'approved_by', 'quote_id', 'payment_deadline_id', 'date_order', 'date_delivery'] as $field) {
            if ($request->input($field) === '') { $request->merge([$field => null]); }
        }
        $rules = [
            'supplier_id' => 'nullable|integer|min:1',
            'approved_by' => 'nullable|integer|min:1',
            'quote_id' => 'nullable|integer|min:1',
            'payment_deadline_id' => 'nullable|integer|min:1',
            'date_order' => 'nullable|date_format:d/m/Y',
            'date_delivery' => 'nullable|date_format:d/m/Y',
            'total_send' => 'required|numeric|min:0',
            'taxes_send' => 'required|numeric|min:0',
            'items' => 'required|string|json',
        ];
        if ($regularize) { $rules['entry_id'] = 'required|integer|min:1'; }
        \Illuminate\Support\Facades\Validator::make($request->all(), $rules)->validate();

        $id = $request->input('supplier_id');
        if ($id && !\App\Supplier::where('tenant_id', $tenant)->whereKey($id)->exists()) {
            self::invalid('supplier_id', 'El proveedor no está disponible en este tenant.');
        }
        $id = $request->input('approved_by');
        if ($id) {
            $query = \App\User::where('tenant_id', $tenant)->whereKey($id);
            $same = $order && (string) $order->approved_by === (string) $id;
            $query->whereHas('companies', function ($q) use ($company, $same) {
                $q->where('companies.id', $company);
                if (!$same) { $q->where('company_user.is_active', true); }
            });
            if (!$query->exists()) { self::invalid('approved_by', 'El aprobador no pertenece a la empresa o no está disponible.'); }
        }
        $id = $request->input('quote_id');
        if ($id) {
            $query = \App\Quote::where('tenant_id', $tenant)->where('company_id', $company)->whereKey($id);
            if (!$order || (string) $order->quote_id !== (string) $id) {
                $query->where('raise_status', 1)->where('state_active', 'open');
            }
            if (!$query->exists()) { self::invalid('quote_id', 'La cotización no pertenece a la empresa o no está disponible.'); }
        }
        $id = $request->input('payment_deadline_id');
        if ($id && !\App\PaymentDeadline::where('tenant_id', $tenant)->where('company_id', $company)
            ->where('type', 'purchases')->selectable($order ? $order->payment_deadline_id : null)->whereKey($id)->exists()) {
            self::invalid('payment_deadline_id', 'El plazo no pertenece a la empresa o no está disponible para compras.');
        }
        if ($regularize && !\App\Entry::where('tenant_id', $tenant)->where('company_id', $company)
            ->where('finance', true)->whereKey($request->input('entry_id'))->exists()) {
            self::invalid('entry_id', 'La entrada financiera no está disponible en esta empresa.');
        }

        $items = json_decode($request->input('items'), true);
        if (!is_array($items) || !$items || array_keys($items) !== range(0, count($items) - 1)) {
            self::invalid('items', 'Envía una lista de detalles no vacía.');
        }
        \Illuminate\Support\Facades\Validator::make(['rows' => $items], [
            'rows.*' => 'required|array',
            'rows.*.service' => 'required|string',
            'rows.*.unit' => 'required|string|max:191',
            'rows.*.quantity' => 'required|numeric|gt:0',
            'rows.*.price' => 'required|numeric|min:0',
            'rows.*.total' => 'required|numeric|min:0',
            'rows.*.detail_id' => 'nullable|integer|min:1',
        ])->validate();
        $seen = [];
        foreach ($items as $i => &$item) {
            $detailId = isset($item['detail_id']) ? $item['detail_id'] : null;
            if ($detailId) {
                if (!$order || isset($seen[$detailId]) || !\App\OrderPurchaseFinanceDetail::where('order_purchase_finance_id', $order->id)->whereKey($detailId)->exists()) {
                    self::invalid('items.'.$i.'.detail_id', 'El detalle no pertenece a esta orden o está repetido.');
                }
                $seen[$detailId] = true;
            } else { $item['detail_id'] = ''; }
        }
        unset($item);
        $request->merge(['items' => json_encode($items)]);
    }

    private static function invalid($field, $message)
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
