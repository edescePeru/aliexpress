<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubcategoryRequest extends FormRequest
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
            $this->get(
                'subcategory_id'
            );

        $categoryId =
            $this->get(
                'category_id'
            );

        return [
            'subcategory_id' => [
                'required',
                'integer',

                /*
                 * La Subcategory que modificamos
                 * debe pertenecer al Tenant.
                 */
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

            'category_id' => [
                'required',
                'integer',

                /*
                 * La nueva Category también debe
                 * pertenecer al mismo Tenant.
                 */
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
                    'subcategories',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'category_id',
                        $categoryId
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->ignore(
                        $subcategoryId
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
                'El :attribute es obligatorio.',

            'subcategory_id.integer' =>
                'El :attribute no es válido.',

            'subcategory_id.exists' =>
                'La subcategoría indicada no existe o no pertenece al negocio actual.',

            'category_id.required' =>
                'La :attribute es obligatoria.',

            'category_id.integer' =>
                'La :attribute no es válida.',

            'category_id.exists' =>
                'La categoría indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'name.unique' =>
                'Ya existe una subcategoría con ese nombre en esta categoría.',

            'description.string' =>
                'La :attribute debe contener caracteres válidos.',

            'description.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'subcategory_id' =>
                'id de la subcategoría',

            'category_id' =>
                'categoría',

            'name' =>
                'nombre de la subcategoría',

            'description' =>
                'descripción de la subcategoría',
        ];
    }
}