<?php
namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseTransferOperationRequest extends FormRequest
{
    public function authorize() { return true; } // Ruta + WarehouseTransferAccess autorizan la operación.
    public function rules()
    {
        $rules = [
            'form_tenant_id' => ['required', 'integer', Rule::in([TenantContext::tenantId()])],
            'form_company_id' => ['required', 'integer', Rule::in([TenantContext::companyId()])],
            'form_branch_id' => ['required', 'integer', Rule::in([TenantContext::branchId()])],
        ];
        $action = $this->route()->getActionMethod();
        if ($action === 'products') { $rules += ['source_warehouse_id' => 'required|integer|min:1', 'q' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:100000']; }
        if ($action === 'store') {
            $rules += ['request_key' => 'required|uuid', 'source_warehouse_id' => 'required|integer|min:1', 'destination_warehouse_id' => 'required|integer|min:1|different:source_warehouse_id',
                'notes' => 'nullable|string|max:2000', 'lines' => 'required|array|min:1|max:500',
                'lines.*.stock_item_id' => 'required|integer|min:1|distinct',
                'lines.*.requested_quantity' => ['required', 'regex:/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D']];
        }
        if ($action === 'receive') {
            $rules += ['request_key' => 'required|uuid', 'notes' => 'nullable|string|max:2000', 'lines' => 'required|array|min:1|max:500',
                'lines.*.transfer_line_id' => 'required|integer|min:1|distinct',
                'lines.*.quantity' => ['required', 'regex:/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D']];
        }
        if ($action === 'cancel') { $rules += ['reason' => 'required|string|max:2000']; }
        return $rules;
    }
    public function messages()
    {
        return ['form_tenant_id.*' => 'El tenant cambió. Recargue la página.',
            'form_company_id.*' => 'La empresa cambió. Recargue la página.',
            'form_branch_id.*' => 'El local cambió. Recargue la página.',
            'lines.required' => 'Agregue al menos un producto con cantidad positiva.'];
    }
}
