<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactNameRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $contactNameId =
            (int) $this->input(
                'contactName_id'
            );


        return [

            'contactName_id' => [
                'required',

                Rule::exists(
                    'contact_names',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'name' => [
                'required',
                'string',
                'max:255',
            ],


            'customer_id' => [
                'required',

                Rule::exists(
                    'customers',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'phone' => [
                'nullable',
                'string',
                'max:12',
            ],


            'email' => [
                'nullable',
                'string',
                'max:255',
                'email',

                Rule::unique(
                    'contact_names',
                    'email'
                )
                    ->where(function ($query) use ($tenantId) {

                        return $query->where(
                            'tenant_id',
                            $tenantId
                        );
                    })
                    ->ignore(
                        $contactNameId
                    ),
            ],


            'area' => [
                'nullable',
                'string',
            ],

        ];
    }


    public function messages()
    {
        return [

            'contactName_id.required' =>
                'El :attribute es obligatorio.',

            'contactName_id.exists' =>
                'El :attribute no existe o no pertenece al grupo empresarial actual.',


            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',


            'customer_id.required' =>
                'La :attribute es obligatoria.',

            'customer_id.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',


            'phone.string' =>
                'El :attribute debe contener caracteres válidos.',

            'phone.max' =>
                'El :attribute debe contener máximo 12 caracteres.',


            'email.string' =>
                'El :attribute debe contener caracteres válidos.',

            'email.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'email.email' =>
                'El :attribute debe ser un email válido.',

            'email.unique' =>
                'Ya existe un contacto con este correo electrónico dentro del grupo empresarial actual.',


            'area.string' =>
                'El :attribute debe contener caracteres válidos.',

        ];
    }


    public function attributes()
    {
        return [

            'contactName_id' =>
                'id del contacto',

            'name' =>
                'nombre de contacto',

            'customer_id' =>
                'empresa',

            'phone' =>
                'teléfono',

            'email' =>
                'correo electrónico',

            'area' =>
                'área',

        ];
    }
}