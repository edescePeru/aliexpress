<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleColorsRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_color.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('colors', 'id')
                    ->where('tenant_id', TenantContext::tenantId()),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un color.',
            'ids.array' => 'La selección de colores debe ser una lista.',
            'ids.min' => 'Seleccione al menos un color.',
            'ids.max' => 'Seleccione como máximo 500 colores por operación.',
            'ids.*.required' => 'Cada color seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de colores deben ser números enteros.',
            'ids.*.min' => 'Los IDs de colores deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene colores repetidos.',
            'ids.*.exists' => 'Un color no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
