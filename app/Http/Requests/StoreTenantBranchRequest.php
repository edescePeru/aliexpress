<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantBranchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        $companyId = (int) $this->input('company_id');

        return [
            'company_id' => [
                'required',

                Rule::exists('companies', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query
                            ->where('tenant_id', $tenantId)
                            ->where('is_active', true);
                    }),
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9\-_]+$/',

                Rule::unique('branches', 'code')
                    ->where(function ($query) use ($companyId) {
                        return $query->where(
                            'company_id',
                            $companyId
                        );
                    }),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
                'max:250',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' =>
                'La empresa es obligatoria.',

            'company_id.exists' =>
                'La empresa no existe, está inactiva o no pertenece al grupo empresarial actual.',

            'code.required' =>
                'El código de la sucursal es obligatorio.',

            'code.unique' =>
                'Ya existe una sucursal con este código dentro de la empresa.',

            'code.regex' =>
                'El código solo puede contener letras, números, guiones y guiones bajos.',

            'name.required' =>
                'El nombre de la sucursal es obligatorio.',
        ];
    }
}