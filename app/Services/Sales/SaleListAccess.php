<?php
namespace App\Services\Sales;

use App\Sale;
use App\Services\Workers\WorkerAccess;
use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class SaleListAccess
{
    public function context(bool $errors = false): void
    {
        app(WorkerAccess::class)->context();
        $this->permission('list_puntoVenta');
        if ($errors) { $this->permission('listErrors_puntoVenta'); }
    }
    public function allBranch(): bool { return (bool) Auth::user()->can('viewBranch_puntoVenta'); }
    public function permission(string $permission): void { abort_unless(Auth::user() && Auth::user()->can($permission), 403); }

    public function visible($query, bool $errors = false)
    {
        $this->context($errors);
        $query->where('sales.tenant_id', TenantContext::tenantId())->where('sales.company_id', TenantContext::companyId())
            ->where('sales.branch_id', TenantContext::branchId())->whereNotNull('sales.created_by')
            ->whereExists(function ($q) { $q->selectRaw('1')->from('users as sale_author')->whereColumn('sale_author.id', 'sales.created_by')->where('sale_author.tenant_id', TenantContext::tenantId()); })
            ->whereExists(function ($q) { $q->selectRaw('1')->from('branches as sale_branch')->whereColumn('sale_branch.id', 'sales.branch_id')->where('sale_branch.company_id', TenantContext::companyId()); });
        if (!$this->allBranch()) { $query->where('sales.created_by', Auth::id()); }
        return $query;
    }
    public function sale($id)
    {
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) { abort(404); }
        $sale = $this->visible(Sale::query())->where('sales.id', $id)->first();
        abort_unless($sale, 404);
        return $sale;
    }
    public function validateFilters($request): void
    {
        $request->validate([
            'created_by_id' => 'nullable|integer|min:1', 'page' => 'nullable|integer|min:1', 'code' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:9999', 'startDate' => 'nullable|required_with:endDate|date_format:d/m/Y',
            'endDate' => 'nullable|required_with:startDate|date_format:d/m/Y|after_or_equal:startDate',
            'customer_id' => 'nullable|string|max:50', 'cash_box_id' => 'nullable|string|max:50', 'cash_box_subtype_id' => 'nullable|integer|min:1',
            'dispatch_status' => 'nullable|string|max:30', 'payment_status' => 'nullable|in:verde,naranja,rojo',
            'invoice_status' => 'nullable|string|max:40', 'document_type' => 'nullable|in:01,03', 'sale_status' => 'nullable|in:active,annulled',
        ]);
        if ($request->filled('created_by_id') && !$this->allBranch() && (int) $request->input('created_by_id') !== Auth::id()) {
            throw ValidationException::withMessages(['created_by_id' => 'No tiene permiso para consultar ventas de otro usuario.']);
        }
    }
    public function snapshot($request): void
    {
        foreach (['Tenant' => TenantContext::tenantId(), 'Company' => TenantContext::companyId(), 'Branch' => TenantContext::branchId()] as $key => $id) {
            if ((string) $request->header('X-Sales-'.$key) !== (string) $id) { throw ValidationException::withMessages(['context' => 'Cambió la empresa o el local desde que abrió el listado. Recargue la página.']); }
        }
    }
    public function boxes()
    {
        $this->context();
        return \App\CashBox::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('is_active', true)
            ->where(function ($q) { $q->whereNull('branch_id')->orWhere('branch_id', TenantContext::branchId()); });
    }
    public function subtypes()
    {
        return \App\CashBoxSubtype::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())->where('is_active', true)
            ->where(function ($q) { $q->whereNull('cash_box_id')->orWhereIn('cash_box_id', $this->boxes()->select('id')); });
    }
    public function permissions(): array
    {
        $map = ['print' => 'print_puntoVenta', 'dispatch' => 'updateDispatch_puntoVenta', 'payments' => 'managePayments_puntoVenta', 'documents' => 'manageDocuments_puntoVenta', 'annul' => 'annul_puntoVenta'];
        $result = [];
        foreach ($map as $key => $permission) { $result[$key] = (bool) Auth::user()->can($permission); }
        $result['emit'] = $result['documents'] && (Auth::user()->can('generarBoleta_puntoVenta') || Auth::user()->can('generarFactura_puntoVenta'));
        return $result;
    }
}
