<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $categoryId =
            $this->get(
                'category_id'
            );

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

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'categories',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->ignore(
                        $categoryId
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
            'category_id.required' =>
                'El :attribute es obligatorio.',

            'category_id.integer' =>
                'El :attribute no es válido.',

            'category_id.exists' =>
                'La categoría indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'name.unique' =>
                'Ya existe un :attribute registrado para este negocio.',

            'description.string' =>
                'La :attribute debe contener caracteres válidos.',

            'description.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'category_id' =>
                'id de categoría de material',

            'name' =>
                'nombre de categoría de material',

            'description' =>
                'descripción de categoría de material',
        ];
    }
}