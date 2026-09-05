<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestoreContactNameRequest extends FormRequest
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

            'contactName_id' => [
                'required',

                Rule::exists(
                    'contact_names',
                    'id'
                )->where(function ($query) use ($tenantId) {

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

            'contactName_id.required' =>
                'El :attribute es obligatorio.',

            'contactName_id.exists' =>
                'El contacto eliminado no existe o no pertenece al grupo empresarial actual.',

        ];
    }


    public function attributes()
    {
        return [

            'contactName_id' =>
                'id del contacto',

        ];
    }
}