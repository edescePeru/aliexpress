<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $subcategoryId =
            $this->input(
                'subcategory_id'
            );

        return [
            'subcategory_id' => [
                'required',
                'integer',

                Rule::exists(
                    'subcategories',
                    'id'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'material_types',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'subcategory_id',
                        $subcategoryId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages()
    {
        return [
            'subcategory_id.required' =>
                'La subcategoría es obligatoria.',

            'subcategory_id.exists' =>
                'La subcategoría indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El nombre del tipo es obligatorio.',

            'name.unique' =>
                'Ya existe un tipo con ese nombre dentro de esta subcategoría.',
        ];
    }
}