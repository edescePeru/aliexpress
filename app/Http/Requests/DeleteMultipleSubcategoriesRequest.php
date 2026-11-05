<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleSubcategoriesRequest extends FormRequest
{
    public function authorize()
    {
        // Both existing deletion routes require destroy_subcategory.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('subcategories', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos una subcategoría.',
            'ids.array' => 'La selección de subsubcategorías debe ser una lista.',
            'ids.min' => 'Seleccione al menos una subcategoría.',
            'ids.max' => 'Seleccione como máximo 500 subsubcategorías por operación.',
            'ids.*.required' => 'Cada subcategoría seleccionada debe tener un ID.',
            'ids.*.integer' => 'Los IDs de subsubcategorías deben ser números enteros.',
            'ids.*.min' => 'Los IDs de subsubcategorías deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene subsubcategorías repetidas.',
            'ids.*.exists' => 'Una subcategoría no existe, fue eliminada o no pertenece al negocio actual.',
        ];
    }
}
