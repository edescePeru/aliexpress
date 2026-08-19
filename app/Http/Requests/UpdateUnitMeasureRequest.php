<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitMeasureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $unitMeasureId =
            $this->input(
                'unitMeasure_id'
            );

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

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'unit_measures',
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
                        $unitMeasureId
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
            'unitMeasure_id.required' =>
                'El :attribute es obligatorio.',

            'unitMeasure_id.integer' =>
                'El :attribute no es válido.',

            'unitMeasure_id.exists' =>
                'La unidad de medida indicada no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 255 caracteres.',

            'name.unique' =>
                'Ya existe una unidad de medida con ese nombre para este negocio.',

            'description.string' =>
                'La :attribute debe contener caracteres válidos.',

            'description.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'unitMeasure_id' =>
                'id de unidad de medida',

            'name' =>
                'nombre de unidad de medida',

            'description' =>
                'descripción de unidad de medida',
        ];
    }
}