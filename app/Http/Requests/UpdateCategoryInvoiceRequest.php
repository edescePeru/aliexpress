<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryInvoiceRequest extends FormRequest
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
            'category_id' => ['required', 'integer', \App\CategoryInvoice::currentCategoryRule()],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [

            'category_id.required' => 'El :attribute es obligatorio.',
            'category_id.exists' => 'El :attribute debe existir en la base de datos.',

            'name.required' => 'El :attribute es obligatoria.',
            'name.string' => 'El :attribute debe contener caracteres válidos.',
            'name.max' => 'El :attribute debe contener máximo 255 caracteres.',

            'description.string' => 'La :attribute debe contener caracteres válidos.',
            'description.max' => 'La :attribute debe contener máximo 255 caracteres.',

        ];
    }

    public function attributes()
    {
        return [
            'category_id' => 'id de categoría de factura',
            'name' => 'nombre de categoría de factura',
            'description' => 'descripción de categoría de factura',
        ];
    }
}
