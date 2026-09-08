<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantCompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        return [
            'ruc' => [
                'required',
                'digits:11',

                Rule::unique('companies', 'ruc')
                    ->where(function ($query) use ($tenantId) {
                        return $query->where(
                            'tenant_id',
                            $tenantId
                        );
                    }),
            ],

            'business_name' => [
                'required',
                'string',
                'max:200',
            ],

            'trade_name' => [
                'nullable',
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

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'branch_name' => [
                'required',
                'string',
                'max:150',
            ],

            'branch_code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9\-_]+$/',
            ],

            'branch_address' => [
                'nullable',
                'string',
                'max:250',
            ],

            'branch_phone' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    public function messages()
    {
        return [
            'ruc.required' =>
                'El RUC es obligatorio.',

            'ruc.digits' =>
                'El RUC debe contener exactamente 11 dígitos.',

            'ruc.unique' =>
                'Ya existe una empresa con este RUC dentro del grupo empresarial actual.',

            'business_name.required' =>
                'La razón social es obligatoria.',

            'business_name.max' =>
                'La razón social no puede superar los 200 caracteres.',

            'trade_name.max' =>
                'El nombre comercial no puede superar los 150 caracteres.',

            'email.email' =>
                'El correo de la empresa no tiene un formato válido.',

            'branch_name.required' =>
                'El nombre de la sucursal principal es obligatorio.',

            'branch_name.max' =>
                'El nombre de la sucursal no puede superar los 150 caracteres.',

            'branch_code.required' =>
                'El código de la sucursal principal es obligatorio.',

            'branch_code.regex' =>
                'El código de la sucursal solo puede contener letras, números, guiones y guiones bajos.',

            'branch_code.max' =>
                'El código de la sucursal no puede superar los 30 caracteres.',
        ];
    }
}