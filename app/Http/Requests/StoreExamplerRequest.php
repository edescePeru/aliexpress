<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamplerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /*'name' => 'required|string|max:255',*/
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('examplers', 'name')
                    ->where(function ($q) {
                        $q->where('brand_id', $this->input('brand_id'));
                    })
                    ->ignore($this->input('exampler_id')),
            ],
            'comment' => 'nullable|string|max:255',
            'brand_id' => 'required|exists:brands,id'
        ];
    }

    public function messages()
    {
        return [
            'brand_id.required' => 'El :attribute es obligatorio.',
            'brand_id.exists' => 'El :attribute no existe en las marcas registradas.',

            'name.required' => 'El :attribute es obligatoria.',
            'name.string' => 'El :attribute debe contener caracteres válidos.',
            'name.max' => 'El :attribute debe contener máximo 255 caracteres.',
            'name.unique' => 'Ya existe un :attribute en la base de datos.',

            'comment.string' => 'La :attribute debe contener caracteres válidos.',
            'comment.max' => 'La :attribute es demasiado largo.',
        ];
    }

    public function attributes()
    {
        return [
            'brand_id' => 'id de la marca de material',
            'name' => 'nombre del modelo de material',
            'comment' => 'descripción del modelo de material',
        ];
    }
}
