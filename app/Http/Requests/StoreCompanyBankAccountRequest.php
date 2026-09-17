<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyBankAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_id' => [
                'required',
                'integer',
                'exists:banks,id',
            ],

            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'account_number' => [
                'required',
                'string',
                'max:100',
            ],

            'cci' => [
                'nullable',
                'string',
                'max:100',
            ],

            'currency' => [
                'required',
                Rule::in([
                    'PEN',
                    'USD',
                ]),
            ],

            'account_holder' => [
                'nullable',
                'string',
                'max:200',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}