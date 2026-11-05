<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleCategoriesRequest extends FormRequest
{
    public function authorize()
    {
        // Both existing deletion routes require destroy_category.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('categories', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos una categoría.',
            'ids.array' => 'La selección de categorías debe ser una lista.',
            'ids.min' => 'Seleccione al menos una categoría.',
            'ids.max' => 'Seleccione como máximo 500 categorías por operación.',
            'ids.*.required' => 'Cada categoría seleccionada debe tener un ID.',
            'ids.*.integer' => 'Los IDs de categorías deben ser números enteros.',
            'ids.*.min' => 'Los IDs de categorías deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene categorías repetidas.',
            'ids.*.exists' => 'Una categoría no existe, fue eliminada o no pertenece al negocio actual.',
        ];
    }
}
