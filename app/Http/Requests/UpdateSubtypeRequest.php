<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubtypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $subtypeId =
            $this->input(
                'subtype_id'
            );

        $materialTypeId =
            $this->input(
                'material_type_id'
            );

        return [
            'subtype_id' => [
                'required',
                'integer',

                Rule::exists(
                    'subtypes',
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

            'material_type_id' => [
                'required',
                'integer',

                Rule::exists(
                    'material_types',
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
                    'subtypes',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'material_type_id',
                        $materialTypeId
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->ignore(
                        $subtypeId
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
            'subtype_id.required' =>
                'El subtipo es obligatorio.',

            'subtype_id.exists' =>
                'El subtipo indicado no existe o no pertenece al negocio actual.',

            'material_type_id.required' =>
                'El tipo de material es obligatorio.',

            'material_type_id.exists' =>
                'El tipo de material indicado no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El nombre del subtipo es obligatorio.',

            'name.unique' =>
                'Ya existe un subtipo con ese nombre dentro de este tipo de material.',
        ];
    }
}