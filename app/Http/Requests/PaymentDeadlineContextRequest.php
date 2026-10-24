<?php
namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PaymentDeadlineContextRequest extends FormRequest
{
    public function authorize() { return true; } // Los permisos se mantienen en las rutas existentes.

    protected function contextRules(): array
    {
        return [
            'form_tenant_id' => ['required', 'integer', Rule::in([TenantContext::tenantId()])],
            'form_company_id' => ['required', 'integer', Rule::in([TenantContext::companyId()])],
        ];
    }

    protected function identifierRules(): array
    {
        return ['required', 'integer', Rule::exists('payment_deadlines', 'id')->where(function ($q) {
            $q->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId());
        })];
    }

    protected function fieldRules(): array
    {
        return ['description' => 'required|string|max:255', 'days' => 'required|integer|min:0|max:2147483647',
            'type' => 'required|in:purchases,quotes', 'credit' => 'required|boolean', 'is_active' => 'required|boolean'];
    }

    public function messages()
    {
        return ['form_tenant_id.*' => 'El tenant cambió. Recargue el formulario.',
            'form_company_id.*' => 'La empresa cambió. Recargue el formulario.',
            'paymentDeadline_id.exists' => 'El plazo no está disponible en la empresa actual.',
            'days.integer' => 'La cantidad de días debe ser un número entero.', 'days.min' => 'Los días no pueden ser negativos.'];
    }
}
