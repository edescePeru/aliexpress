<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleBrandsRequest extends FormRequest
{
    public function authorize()
    {
        // Both existing deletion routes require destroy_brand.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('brands', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos una marca.',
            'ids.array' => 'La selección de marcas debe ser una lista.',
            'ids.min' => 'Seleccione al menos una marca.',
            'ids.max' => 'Seleccione como máximo 500 marcas por operación.',
            'ids.*.required' => 'Cada marca seleccionada debe tener un ID.',
            'ids.*.integer' => 'Los IDs de marcas deben ser números enteros.',
            'ids.*.min' => 'Los IDs de marcas deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene marcas repetidas.',
            'ids.*.exists' => 'Una marca no existe, fue eliminada o no pertenece al negocio actual.',
        ];
    }
}
