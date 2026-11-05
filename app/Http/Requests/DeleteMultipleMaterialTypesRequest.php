<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleMaterialTypesRequest extends FormRequest
{
    public function authorize()
    {
        // Both existing deletion routes require destroy_materialType.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('material_types', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un tipo de material.',
            'ids.array' => 'La selección de tipos debe ser una lista.',
            'ids.min' => 'Seleccione al menos un tipo de material.',
            'ids.max' => 'Seleccione como máximo 500 tipos por operación.',
            'ids.*.required' => 'Cada tipo seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de tipos deben ser números enteros.',
            'ids.*.min' => 'Los IDs de tipos deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene tipos repetidos.',
            'ids.*.exists' => 'Un tipo no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
