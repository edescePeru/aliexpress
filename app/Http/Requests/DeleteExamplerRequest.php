<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteExamplerRequest extends FormRequest
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
        ];
    }

    public function attributes()
    {
        return [
            'exampler_id' =>
                'id del modelo de material',
        ];
    }
}