<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamplerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $examplerId =
            $this->input(
                'exampler_id'
            );

        $brandId =
            $this->input(
                'brand_id'
            );

        return [
            'exampler_id' => [
                'required',
                'integer',

                Rule::exists(
                    'examplers',
                    'id'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    ),
            ],

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
                    'examplers',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'brand_id',
                        $brandId
                    )
                    ->ignore(
                        $examplerId
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
            'exampler_id.required' =>
                'El :attribute es obligatorio.',

            'exampler_id.integer' =>
                'El :attribute no es válido.',

            'exampler_id.exists' =>
                'El modelo indicado no existe o no pertenece al negocio actual.',

            'brand_id.required' =>
                'La :attribute es obligatoria.',

            'brand_id.integer' =>
                'La :attribute no es válida.',

            'brand_id.exists' =>
                'La marca seleccionada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'name.unique' =>
                'Ya existe un modelo con ese nombre para esta marca.',

            'comment.string' =>
                'La :attribute debe contener caracteres válidos.',

            'comment.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'exampler_id' =>
                'id del modelo',

            'brand_id' =>
                'marca de material',

            'name' =>
                'nombre del modelo de material',

            'comment' =>
                'descripción del modelo de material',
        ];
    }
}