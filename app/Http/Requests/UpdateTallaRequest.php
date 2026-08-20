<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTallaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $tallaId =
            $this->input(
                'talla_id'
            );

        return [
            'talla_id' => [
                'required',
                'integer',

                Rule::exists(
                    'tallas',
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
                'max:191',

                Rule::unique(
                    'tallas',
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
                        $tallaId
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'short_name' => [
                'nullable',
                'string',
                'max:191',
            ],
        ];
    }

    public function messages()
    {
        return [
            'talla_id.required' =>
                'La talla es obligatoria.',

            'talla_id.exists' =>
                'La talla indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El nombre de la talla es obligatorio.',

            'name.unique' =>
                'Ya existe una talla con ese nombre para este negocio.',
        ];
    }
}