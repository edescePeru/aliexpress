<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code_quote' => 'nullable|string',
            'code_description' => 'nullable|string',
            'observations' => 'nullable|string',

            'date_quote' => 'nullable|string',      // d/m/Y
            'date_validate' => 'nullable|string',   // d/m/Y

            'way_to_pay' => 'nullable|string',
            'delivery_time' => 'nullable|string',

            'customer_id' => 'nullable|exists:customers,id',
            'contact_id' => 'nullable|exists:contact_names,id',
            'payment_deadline' => 'nullable|exists:payment_deadlines,id',

            // Totales (front)
            'descuento' => 'nullable|numeric|min:0',
            'gravada' => 'nullable|numeric|min:0',
            'igv_total' => 'nullable|numeric|min:0',
            'total_importe' => 'nullable|numeric|min:0',

            // Meta descuento
            'discount_type' => ['nullable', Rule::in(['amount', 'percent'])],
            'discount_input_mode' => ['nullable', Rule::in(['with_igv', 'without_igv'])],
            'discount_input_value' => 'nullable|numeric|min:0',

            // Equipments JSON string
            'equipments' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'equipments.required' => 'Debe enviar los equipos de la cotización.',
            'equipments.string' => 'El campo equipos debe ser un JSON válido (string).',
            'discount_type.in' => 'Tipo de descuento inválido.',
            'discount_input_mode.in' => 'Modo de descuento inválido.',
        ];
    }

    public function attributes()
    {
        return [
            'code_description' => 'descripción',
            'code_quote' => 'código',
            'date_quote' => 'fecha',
            'date_validate' => 'fecha válida',
            'way_to_pay' => 'forma de pago',
            'delivery_time' => 'tiempo de entrega',
            'customer_id' => 'cliente',
            'equipments' => 'equipos',
            'descuento' => 'descuento',
            'gravada' => 'gravada',
            'igv_total' => 'igv',
            'total_importe' => 'total',
            'discount_type' => 'tipo descuento',
            'discount_input_mode' => 'modo descuento',
            'discount_input_value' => 'valor descuento',
        ];
    }
}
