<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleGenerosRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_genero.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('generos', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un género.',
            'ids.array' => 'La selección de géneros debe ser una lista.',
            'ids.min' => 'Seleccione al menos un género.',
            'ids.max' => 'Seleccione como máximo 500 géneros por operación.',
            'ids.*.required' => 'Cada género seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de géneros deben ser números enteros.',
            'ids.*.min' => 'Los IDs de géneros deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene géneros repetidos.',
            'ids.*.exists' => 'Un género no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
