<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteColorRequest extends FormRequest
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
            'color_id' => [
                'required',
                'integer',

                Rule::exists(
                    'colors',
                    'id'
                )->where(
                    'tenant_id',
                    $tenantId
                ),
            ],
        ];
    }
}