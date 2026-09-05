<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        return [
            'business_name' => [
                'required',
                'string',
                'max:255',
            ],

            'ruc' => [
                'required',
                'string',

                Rule::unique('customers', 'RUC')
                    ->where(function ($query) use ($tenantId) {
                        return $query->where(
                            'tenant_id',
                            $tenantId
                        );
                    }),
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages()
    {
        return [
            'business_name.required' =>
                'La :attribute es obligatoria.',

            'business_name.string' =>
                'La :attribute debe contener caracteres válidos.',

            'business_name.max' =>
                'La :attribute debe contener máximo 255 caracteres.',

            'ruc.required' =>
                'El :attribute es obligatorio.',

            'ruc.string' =>
                'El :attribute debe contener caracteres válidos.',

            'ruc.unique' =>
                'Ya existe un cliente con este documento dentro del grupo empresarial actual.',

            'address.string' =>
                'La :attribute debe contener caracteres válidos.',

            'address.max' =>
                'La :attribute debe contener máximo 255 caracteres.',

            'location.string' =>
                'La :attribute debe contener caracteres válidos.',

            'location.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'business_name' =>
                'Razón Social',

            'ruc' =>
                'documento del cliente',

            'address' =>
                'dirección del cliente',

            'location' =>
                'código de ubicación del cliente',
        ];
    }
}