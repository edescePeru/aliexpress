<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteCategoryRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'categories',
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
            'category_id.required' =>
                'El :attribute es obligatorio.',

            'category_id.integer' =>
                'El :attribute no es válido.',

            'category_id.exists' =>
                'La categoría indicada no existe o no pertenece al negocio actual.',
        ];
    }

    public function attributes()
    {
        return [
            'category_id' =>
                'id de categoría de material',
        ];
    }
}