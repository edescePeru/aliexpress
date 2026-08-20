<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTallaRequest extends FormRequest
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
        ];
    }

    public function messages()
    {
        return [
            'talla_id.required' =>
                'La talla es obligatoria.',

            'talla_id.integer' =>
                'La talla indicada no es válida.',

            'talla_id.exists' =>
                'La talla indicada no existe o no pertenece al negocio actual.',
        ];
    }
}