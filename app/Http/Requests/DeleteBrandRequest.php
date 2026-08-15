<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteBrandRequest extends FormRequest
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
        ];
    }

    public function attributes()
    {
        return [
            'brand_id' =>
                'id de la marca de material',
        ];
    }
}