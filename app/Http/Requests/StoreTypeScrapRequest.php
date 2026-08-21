<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeScrapRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:191',

                Rule::unique(
                    'typescraps',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'width' => [
                'required',
                'numeric',
                'between:0,99999.99',
            ],

            'length' => [
                'required',
                'numeric',
                'between:0,99999.99',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' =>
                'El nombre del tipo de retacería es obligatorio.',

            'name.unique' =>
                'Ya existe un tipo de retacería con ese nombre para este negocio.',

            'width.required' =>
                'El ancho es obligatorio.',

            'width.numeric' =>
                'El ancho debe ser numérico.',

            'width.between' =>
                'El ancho está fuera del rango permitido.',

            'length.required' =>
                'El largo es obligatorio.',

            'length.numeric' =>
                'El largo debe ser numérico.',

            'length.between' =>
                'El largo está fuera del rango permitido.',
        ];
    }
}