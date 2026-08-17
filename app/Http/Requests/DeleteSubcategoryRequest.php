<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteSubcategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

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
        ];
    }

    public function messages()
    {
        return [
            'subcategory_id.required' =>
                'El :attribute es obligatorio.',

            'subcategory_id.integer' =>
                'El :attribute no es válido.',

            'subcategory_id.exists' =>
                'La subcategoría indicada no existe o no pertenece al negocio actual.',
        ];
    }

    public function attributes()
    {
        return [
            'subcategory_id' =>
                'id de la subcategoría de material',
        ];
    }
}