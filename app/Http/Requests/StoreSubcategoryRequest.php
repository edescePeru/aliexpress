<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
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

                /*
                 * La Category debe existir
                 * dentro del Tenant actual.
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

            'subcategories' => [
                'required',
                'array',
                'min:1',
            ],

            'subcategories.*.name' => [
                'required',
                'string',
                'max:255',
                'distinct',

                /*
                 * La unicidad es:
                 *
                 * Tenant + Category + Name.
                 */
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
                    ),
            ],

            'subcategories.*.description' => [
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
                'La :attribute es obligatoria.',

            'category_id.integer' =>
                'La :attribute no es válida.',

            'category_id.exists' =>
                'La categoría indicada no existe o no pertenece al negocio actual.',

            'subcategories.required' =>
                'Debe agregar al menos una subcategoría.',

            'subcategories.array' =>
                'El campo subcategorías no tiene un formato válido.',

            'subcategories.min' =>
                'Debe agregar al menos una subcategoría.',

            'subcategories.*.name.required' =>
                'El nombre de la subcategoría es obligatorio.',

            'subcategories.*.name.string' =>
                'El nombre de la subcategoría debe contener caracteres válidos.',

            'subcategories.*.name.max' =>
                'El nombre de la subcategoría debe contener máximo 255 caracteres.',

            'subcategories.*.name.distinct' =>
                'Hay nombres de subcategorías duplicados en el formulario.',

            'subcategories.*.name.unique' =>
                'Ya existe una subcategoría con ese nombre en esta categoría.',

            'subcategories.*.description.string' =>
                'La descripción de la subcategoría debe contener caracteres válidos.',

            'subcategories.*.description.max' =>
                'La descripción de la subcategoría debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'category_id' =>
                'categoría',

            'subcategories.*.name' =>
                'nombre de la subcategoría',

            'subcategories.*.description' =>
                'descripción de la subcategoría',
        ];
    }
}