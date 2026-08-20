<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId =
            TenantContext::tenantId();

        $materialTypeId =
            $this->input(
                'materialtype_id'
            );

        $subcategoryId =
            $this->input(
                'subcategory_id'
            );

        return [
            'materialtype_id' => [
                'required',
                'integer',

                Rule::exists(
                    'material_types',
                    'id'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'subcategory_id' => [
                'required',
                'integer',

                Rule::exists(
                    'subcategories',
                    'id'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'material_types',
                    'name'
                )
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'subcategory_id',
                        $subcategoryId
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->ignore(
                        $materialTypeId
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}