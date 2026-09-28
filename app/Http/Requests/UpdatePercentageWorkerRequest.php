<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePercentageWorkerRequest extends FormRequest
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
            'value' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [

            'value.required' => 'La :attribute debe contener caracteres válidos.',
            'value.numeric' => 'La :attribute debe ser un número.',
            'value.min' => 'La :attribute debe ser mínimo 0.',
        ];
    }

    public function attributes()
    {
        return [
            'value' => 'valor del porcentaje de recursos humanos',
        ];
    }
}
