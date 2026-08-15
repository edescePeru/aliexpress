<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $brandId =
            $this->get('brand_id');

        return [
            'brand_id' => [
                'required',
                'integer',

                Rule::exists(
                    'brands',
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
                    'brands',
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
                        $brandId
                    ),
            ],

            'comment' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages()
    {
        return [
            'brand_id.required' =>
                'El :attribute es obligatorio.',

            'brand_id.integer' =>
                'El :attribute no es válido.',

            'brand_id.exists' =>
                'La marca indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'name.unique' =>
                'Ya existe un :attribute registrado para este negocio.',

            'comment.string' =>
                'La :attribute debe contener caracteres válidos.',

            'comment.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'brand_id' =>
                'id de la marca',

            'name' =>
                'nombre de marca de material',

            'comment' =>
                'descripción de marca de material',
        ];
    }
}