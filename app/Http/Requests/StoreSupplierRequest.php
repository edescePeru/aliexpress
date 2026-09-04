<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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

                Rule::unique(
                    'suppliers',
                    'RUC'
                )->where(function ($query) use ($tenantId) {

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


            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],


            'email' => [
                'nullable',
                'string',
                'email',
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
                'Ya existe un proveedor con este RUC dentro del grupo empresarial actual.',


            'address.string' =>
                'La :attribute debe contener caracteres válidos.',

            'address.max' =>
                'La :attribute debe contener máximo 255 caracteres.',


            'phone.string' =>
                'El :attribute debe contener caracteres válidos.',

            'phone.max' =>
                'El :attribute debe contener máximo 255 caracteres.',


            'email.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'email.string' =>
                'El :attribute debe contener caracteres válidos.',

            'email.email' =>
                'La :attribute no tiene formato de email correcto.',

        ];
    }


    public function attributes()
    {
        return [

            'business_name' =>
                'Razón Social',

            'ruc' =>
                'RUC del proveedor',

            'address' =>
                'dirección del proveedor',

            'phone' =>
                'teléfono del proveedor',

            'email' =>
                'email del proveedor',

        ];
    }
}