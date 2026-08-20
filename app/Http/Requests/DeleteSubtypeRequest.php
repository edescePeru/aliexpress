<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteSubtypeRequest extends FormRequest
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
        ];
    }

    public function messages()
    {
        return [
            'subtype_id.required' =>
                'El subtipo es obligatorio.',

            'subtype_id.exists' =>
                'El subtipo indicado no existe o no pertenece al negocio actual.',
        ];
    }
}