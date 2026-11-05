<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleSubtypesRequest extends FormRequest
{
    public function authorize()
    {
        // Existing route middleware requires destroy_subType.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('subtypes', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un subtipo.',
            'ids.array' => 'La selección de subtipos debe ser una lista.',
            'ids.min' => 'Seleccione al menos un subtipo.',
            'ids.max' => 'Seleccione como máximo 500 subtipos por operación.',
            'ids.*.required' => 'Cada subtipo seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de subtipos deben ser números enteros.',
            'ids.*.min' => 'Los IDs de subtipos deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene subtipos repetidos.',
            'ids.*.exists' => 'Un subtipo no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
