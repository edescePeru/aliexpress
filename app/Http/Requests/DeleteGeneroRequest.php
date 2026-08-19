<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteGeneroRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        return [
            'genero_id' => [
                'required',
                'integer',

                Rule::exists(
                    'generos',
                    'id'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],
        ];
    }

    public function messages()
    {
        return [
            'genero_id.required' =>
                'El :attribute es obligatorio.',

            'genero_id.integer' =>
                'El :attribute no es válido.',

            'genero_id.exists' =>
                'El género indicado no existe o no pertenece al negocio actual.',
        ];
    }

    public function attributes()
    {
        return [
            'genero_id' =>
                'id del género',
        ];
    }
}