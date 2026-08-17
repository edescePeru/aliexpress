<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name' =>
                'nombre de categoría de material',

            'description' =>
                'descripción de categoría de material',
        ];
    }
}