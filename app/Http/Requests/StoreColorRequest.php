<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:colors,name',
            'code' => 'nullable|string|max:255',
            'short_name' => 'required|string|max:255|unique:colors,short_name',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El :attribute es obligatoria.',
            'name.string' => 'El :attribute debe contener caracteres válidos.',
            'name.max' => 'El :attribute debe contener máximo 255 caracteres.',
            'name.unique' => 'Ya existe un :attribute en la base de datos.',

            'code.string' => 'La :attribute debe contener caracteres válidos.',
            'code.max' => 'La :attribute es demasiado largo.',

            'short_name.required' => 'El :attribute es obligatoria.',
            'short_name.string' => 'El :attribute debe contener caracteres válidos.',
            'short_name.max' => 'El :attribute debe contener máximo 255 caracteres.',
            'short_name.unique' => 'Ya existe un :attribute en la base de datos.',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nombre de color',
            'code' => 'codigo de color',
            'short_name' => 'nombre clave de color',
        ];
    }
}
