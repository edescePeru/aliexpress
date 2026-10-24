<?php
namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseAvailabilityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        $rules = ['form_tenant_id' => ['required', 'integer', Rule::in([TenantContext::tenantId()])],
            'form_company_id' => ['required', 'integer', Rule::in([TenantContext::companyId()])],
            'form_branch_id' => ['required', 'integer', Rule::in([TenantContext::branchId()])]];
        if ($this->route()->getActionMethod() === 'products') { $rules += ['q' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:100000']; }
        else { $rules += ['stock_item_id' => 'required|integer|min:1']; }
        return $rules;
    }
    public function messages()
    {
        return ['form_tenant_id.*' => 'El tenant cambió. Recargue la consulta.', 'form_company_id.*' => 'La empresa cambió. Recargue la consulta.', 'form_branch_id.*' => 'El local cambió. Recargue la consulta.'];
    }
}
