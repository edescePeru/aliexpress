<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $colorId =
            $this->input(
                'color_id'
            );

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

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'colors',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->ignore(
                        $colorId
                    ),
            ],

            'code' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'short_name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'colors',
                    'short_name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->ignore(
                        $colorId
                    ),
            ],
        ];
    }
}