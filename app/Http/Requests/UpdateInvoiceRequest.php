<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            'category_invoice_id' => ['nullable', 'integer', \App\CategoryInvoice::currentCategoryRule()],
            'purchase_order' => 'nullable|string|min:5|max:255',
            'invoice' => 'required|string|min:5|max:255',
            'entry_type' => 'required',
            'type_order' => 'required',
            'deferred_invoice' => 'nullable',
            'supplier_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', \App\Support\TenantContext::tenantId())],
            //'image' => 'image',
            'date_invoice' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'purchase_order.string' => 'La :attribute debe contener caracteres válidos.',
            'purchase_order.min' => 'La :attribute debe contener mínimo 5 caracteres.',
            'purchase_order.max' => 'La :attribute debe contener máximo 255 caracteres.',
            'invoice.required' => 'La :attribute es obligatorio.',
            'invoice.string' => 'La :attribute debe contener caracteres válidos.',
            'invoice.min' => 'La :attribute debe contener mínimo 5 caracteres.',
            'invoice.max' => 'La :attribute debe contener máximo 255 caracteres.',
            'entry_type.required' => 'La :attribute es obligatorio.',
            'type_order.required' => 'La :attribute es obligatorio.',
            'supplier_id.exists' => 'El :attribute no existe en la base de datos.',
            //'image.image' => 'Los :attribute son obligatorio.',
            'date_invoice.required' => 'La :attribute es obligatoria.',
        ];
    }

    public function attributes()
    {
        return [
            'date_invoice' => 'fecha de factura',
            'purchase_order' => 'orden de compra',
            'invoice' => 'factura',
            'entry_type' => 'tipo de entrada',
            'type_order' => 'tipo de orden',
            'supplier_id' => 'proveedor',
            'deferred_invoice' => 'opción diferido',
        ];
    }
}
