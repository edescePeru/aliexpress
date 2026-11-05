<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteMultipleTypeScrapsRequest extends FormRequest
{
    public function authorize()
    {
        // Both existing deletion routes require destroy_typeScrap.
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('typescraps', 'id')
                    ->where('tenant_id', TenantContext::tenantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => 'Seleccione al menos un tipo de retacería.',
            'ids.array' => 'La selección de tipos debe ser una lista.',
            'ids.min' => 'Seleccione al menos un tipo de retacería.',
            'ids.max' => 'Seleccione como máximo 500 tipos de retacería por operación.',
            'ids.*.required' => 'Cada tipo seleccionado debe tener un ID.',
            'ids.*.integer' => 'Los IDs de tipos deben ser números enteros.',
            'ids.*.min' => 'Los IDs de tipos deben ser positivos.',
            'ids.*.distinct' => 'La selección contiene tipos repetidos.',
            'ids.*.exists' => 'Un tipo no existe, fue eliminado o no pertenece al negocio actual.',
        ];
    }
}
