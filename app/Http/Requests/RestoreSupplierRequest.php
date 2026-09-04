<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestoreSupplierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();


        return [

            'supplier_id' => [
                'required',

                Rule::exists(
                    'suppliers',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query->where(
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

            'supplier_id.required' =>
                'El :attribute es obligatorio.',

            'supplier_id.exists' =>
                'El proveedor eliminado no existe o no pertenece al grupo empresarial actual.',
        ];
    }


    public function attributes()
    {
        return [

            'supplier_id' =>
                'id del proveedor',

        ];
    }
}