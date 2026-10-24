<?php
namespace App\Services\Purchases;

use App\{Entry, MaterialOrder, OrderPurchase, OrderPurchaseDetail, PaymentDeadline, Quote, StockItem, Supplier, User};
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NormalPurchaseGuard
{
    public function context(Request $request): void
    {
        if ((string) $request->header('X-Purchase-Tenant') !== (string) TenantContext::tenantId()
            || (string) $request->header('X-Purchase-Company') !== (string) TenantContext::companyId()) {
            $this->invalid('La empresa cambió o el formulario es antiguo. Recargue la pantalla de órdenes.');
        }
    }

    public function users()
    {
        return User::query()->where('tenant_id', TenantContext::tenantId())
            ->whereHas('companies', function ($q) {
                $q->where('companies.id', TenantContext::companyId())->where('company_user.is_active', true);
            });
    }

    public function header(Request $request, ?OrderPurchase $existingOrder = null): void
    {
        $this->context($request);
        if ($request->filled('supplier_id') && !Supplier::query()->where('tenant_id', TenantContext::tenantId())->where('id', $request->input('supplier_id'))->exists()) {
            $this->invalid('El proveedor no pertenece al tenant actual.');
        }
        if ($request->filled('quote_id') && !Quote::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('id', $request->input('quote_id'))->exists()) {
            $this->invalid('La cotización no pertenece a la empresa actual.');
        }
        if ($request->filled('payment_deadline_id')) {
            $query = PaymentDeadline::query()->where('type', 'purchases');
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) { $query->lockForUpdate(); }
            $deadline = $query->find($request->input('payment_deadline_id'));
            $retained = $existingOrder && (int) $existingOrder->payment_deadline_id === (int) $request->input('payment_deadline_id');
            if (!$deadline || (!$deadline->is_active && !$retained)) {
                $this->invalid('Seleccione una condición de pago activa de la empresa actual.');
            }
        }
    }

    public function order($id, $lock = false)
    {
        $q = OrderPurchase::query()->where('type', 'n');
        if ($lock) { $q->lockForUpdate(); }
        return $q->findOrFail($id);
    }

    public function editable(OrderPurchase $order): void
    {
        $ids = OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->pluck('id');
        if (MaterialOrder::query()->whereIn('order_purchase_detail_id', $ids)->where('quantity_entered', '>', 0)->exists()
            || Entry::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                ->where('purchase_order', $order->code)->exists()) {
            $this->invalid('La orden tiene recepciones. Su modificación requiere la conciliación del siguiente bloque.');
        }
    }

    public function items($json, $order = null, $allowEmpty = false): array
    {
        $items = is_string($json) ? json_decode($json) : null;
        if (!is_array($items) || (!$allowEmpty && !$items)) { $this->invalid('Agregue productos válidos a la orden.'); }
        foreach ($items as $item) {
            if (!is_object($item)) { $this->invalid('Formato de producto inválido.'); }
            if (!empty($item->detail_id)) {
                if (!$order) { $this->invalid('Una orden nueva no admite detalles existentes.'); }
                OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->findOrFail($item->detail_id);
                // Las líneas existentes se editan por su endpoint específico.
                continue;
            }
            $this->product($item);
        }
        return $items;
    }

    public function product($item, $detail = null): void
    {
        $id = $item->stock_item_id ?? ($detail ? $detail->stock_item_id : null);
        $stock = StockItem::query()->where('tenant_id', TenantContext::tenantId())->where('is_active', 1)
            ->enabledForCompany(TenantContext::companyId())->with('material')->find($id);
        if (!$stock || !$stock->material || (int) $stock->material->enable_status !== 1) {
            $this->invalid('El producto no está habilitado para la empresa actual.');
        }
        $material = $item->id_material ?? ($detail ? $detail->material_id : null);
        if ((int) $material !== (int) $stock->material_id || ($detail && (int) $detail->material_id !== (int) $stock->material_id)) {
            $this->invalid('El StockItem no corresponde al material del detalle.');
        }
        foreach (['quantity', 'price', 'total'] as $field) {
            $value = $item->$field ?? null;
            if (!is_numeric($value) || !is_finite((float) $value) || (float) $value <= 0) {
                $this->invalid('Cantidad, precio y total deben ser números positivos.');
            }
        }
        $item->stock_item_id = (int) $stock->id; $item->id_material = (int) $stock->material_id;
    }

    public function recalculate(OrderPurchase $order): void
    {
        $order->total = OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->sum('total_detail');
        $order->igv = OrderPurchaseDetail::query()->where('order_purchase_id', $order->id)->sum('igv');
        $order->save();
    }

    public function invalid($message): void
    {
        throw ValidationException::withMessages(['order' => $message]);
    }
}
