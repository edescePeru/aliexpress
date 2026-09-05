<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestoreCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        return [
            'customer_id' => [
                'required',

                Rule::exists('customers', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereNotNull(
                                'deleted_at'
                            );
                    }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'customer_id.required' =>
                'El :attribute es obligatorio.',

            'customer_id.exists' =>
                'El cliente eliminado no existe o no pertenece al grupo empresarial actual.',
        ];
    }

    public function attributes()
    {
        return [
            'customer_id' =>
                'id del cliente',
        ];
    }
}