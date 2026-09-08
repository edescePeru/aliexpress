<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantCompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        $companyId =
            (int) $this->route('id');

        return [
            'ruc' => [
                'required',
                'digits:11',

                Rule::unique(
                    'companies',
                    'ruc'
                )
                    ->where(function ($query) use ($tenantId) {
                        return $query->where(
                            'tenant_id',
                            $tenantId
                        );
                    })
                    ->ignore($companyId),
            ],

            'business_name' => [
                'required',
                'string',
                'max:200',
            ],

            'trade_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
                'max:250',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
        ];
    }

    public function messages()
    {
        return [
            'ruc.required' =>
                'El RUC es obligatorio.',

            'ruc.digits' =>
                'El RUC debe contener exactamente 11 dígitos.',

            'ruc.unique' =>
                'Ya existe una empresa con este RUC dentro del grupo empresarial actual.',

            'business_name.required' =>
                'La razón social es obligatoria.',

            'email.email' =>
                'El correo no tiene un formato válido.',
        ];
    }
}