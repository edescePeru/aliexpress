<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleTallasRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_talla.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('tallas', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos una talla.',
            'ids.array' => 'La selección de tallas debe ser una lista.',
            'ids.min' => 'Seleccione al menos una talla.',
            'ids.max' => 'Seleccione como máximo 500 tallas por operación.',
            'ids.*.required' => 'Cada talla seleccionada debe tener un ID.',
            'ids.*.integer' => 'Los IDs de tallas deben ser números enteros.',
            'ids.*.min' => 'Los IDs de tallas deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene tallas repetidas.',
            'ids.*.exists' => 'Una talla no existe, fue eliminada o no pertenece al negocio actual.',
        ];
    }
}
