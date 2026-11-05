<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleUnitMeasuresRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_unitMeasure.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('unit_measures', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos una unidad.',
            'ids.array' => 'La selección de unidades debe ser una lista.',
            'ids.min' => 'Seleccione al menos una unidad.',
            'ids.max' => 'Seleccione como máximo 500 unidades por operación.',
            'ids.*.required' => 'Cada unidad seleccionada debe tener un ID.',
            'ids.*.integer' => 'Los IDs de unidades deben ser números enteros.',
            'ids.*.min' => 'Los IDs de unidades deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene unidades repetidas.',
            'ids.*.exists' => 'Una unidad no existe, fue eliminada o no pertenece al negocio actual.',
        ];
    }
}
