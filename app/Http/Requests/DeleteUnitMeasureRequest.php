<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteUnitMeasureRequest extends FormRequest
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
            'unitMeasure_id' => [
                'required',
                'integer',

                Rule::exists(
                    'unit_measures',
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
            'unitMeasure_id.required' =>
                'El :attribute es obligatorio.',

            'unitMeasure_id.integer' =>
                'El :attribute no es válido.',

            'unitMeasure_id.exists' =>
                'La unidad de medida indicada no existe o no pertenece al negocio actual.',
        ];
    }

    public function attributes()
    {
        return [
            'unitMeasure_id' =>
                'id de la unidad de medida',
        ];
    }
}