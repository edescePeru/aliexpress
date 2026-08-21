<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeScrapRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $typeScrapId =
            $this->input(
                'typeScrap_id'
            );

        return [
            'typeScrap_id' => [
                'required',
                'integer',

                Rule::exists(
                    'typescraps',
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
                    'typescraps',
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
                        $typeScrapId
                    ),
            ],

            'width' => [
                'required',
                'numeric',
                'between:0,99999.99',
            ],

            'length' => [
                'required',
                'numeric',
                'between:0,99999.99',
            ],
        ];
    }

    public function messages()
    {
        return [
            'typeScrap_id.required' =>
                'El tipo de retacería es obligatorio.',

            'typeScrap_id.exists' =>
                'El tipo de retacería indicado no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El nombre del tipo de retacería es obligatorio.',

            'name.unique' =>
                'Ya existe un tipo de retacería con ese nombre para este negocio.',
        ];
    }
}