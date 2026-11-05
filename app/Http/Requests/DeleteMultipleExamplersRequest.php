<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleExamplersRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_exampler.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('examplers', 'id')
                    ->where('tenant_id', TenantContext::tenantId()),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un modelo.',
            'ids.array' => 'La selección de modelos debe ser una lista.',
            'ids.min' => 'Seleccione al menos un modelo.',
            'ids.max' => 'Seleccione como máximo 500 modelos por operación.',
            'ids.*.required' => 'Cada modelo seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de modelos deben ser números enteros.',
            'ids.*.min' => 'Los IDs de modelos deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene modelos repetidos.',
            'ids.*.exists' => 'Un modelo no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
