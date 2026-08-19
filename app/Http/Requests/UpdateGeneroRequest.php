<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneroRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $generoId =
            $this->input(
                'genero_id'
            );

        return [
            'genero_id' => [
                'required',
                'integer',

                Rule::exists(
                    'generos',
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
                    'generos',
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
                        $generoId
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
            'genero_id.required' =>
                'El :attribute es obligatorio.',

            'genero_id.integer' =>
                'El :attribute no es válido.',

            'genero_id.exists' =>
                'El género indicado no existe o no pertenece al negocio actual.',

            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute debe contener máximo 191 caracteres.',

            'name.unique' =>
                'Ya existe un género con ese nombre para este negocio.',

            'description.string' =>
                'La :attribute debe contener caracteres válidos.',

            'description.max' =>
                'La :attribute debe contener máximo 255 caracteres.',
        ];
    }

    public function attributes()
    {
        return [
            'genero_id' =>
                'id del género',

            'name' =>
                'nombre del género',

            'description' =>
                'descripción del género',
        ];
    }
}