<?php

namespace App\Http\Controllers;

use App\MaterialDetailSetting;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class MaterialDetailSettingController extends Controller
{
    public function index()
    {
        $companyId =
            TenantContext::companyId();

        $setting =
            MaterialDetailSetting::query()
                ->forCompany(
                    $companyId
                )
                ->first();

        $sections =
            config(
                'material_details.sections'
            );

        $enabled = [];

        if (
            $setting &&
            is_array(
                $setting->enabled_sections
            )
        ) {
            $enabled =
                $setting->enabled_sections;
        }

        return view(
            'materialDetailSetting.index',
            [
                'setting' =>
                    $setting,

                'sections' =>
                    $sections,

                'enabled' =>
                    $enabled,
            ]
        );
    }

    public function store(Request $request) {
        $tenantId =
            TenantContext::tenantId();

        $companyId =
            TenantContext::companyId();


        $validKeys =
            array_keys(
                config(
                    'material_details.sections'
                )
            );


        $enabled = [];


        if (
        $request->has(
            'enabled_sections'
        )
        ) {

            foreach (
                $request->enabled_sections
                as $key
            ) {

                if (
                in_array(
                    $key,
                    $validKeys,
                    true
                )
                ) {
                    $enabled[] =
                        $key;
                }
            }
        }


        /*
         * =========================================================
         * DEPENDENCIAS
         * =========================================================
         */


        /*
         * Subcategory necesita Category.
         */
        if (
        in_array(
            'subcategory',
            $enabled,
            true
        )
        ) {
            $this->ensureEnabled(
                $enabled,
                'category'
            );
        }


        /*
         * Exampler necesita Brand.
         */
        if (
        in_array(
            'exampler',
            $enabled,
            true
        )
        ) {
            $this->ensureEnabled(
                $enabled,
                'brand'
            );
        }


        /*
         * MaterialType necesita:
         *
         * Category
         * └── Subcategory
         *     └── MaterialType
         */
        if (
        in_array(
            'material_type',
            $enabled,
            true
        )
        ) {
            $this->ensureEnabled(
                $enabled,
                'category'
            );

            $this->ensureEnabled(
                $enabled,
                'subcategory'
            );
        }


        /*
         * Subtype necesita toda la cadena:
         *
         * Category
         * └── Subcategory
         *     └── MaterialType
         *         └── Subtype
         */
        if (
        in_array(
            'subtype',
            $enabled,
            true
        )
        ) {
            $this->ensureEnabled(
                $enabled,
                'category'
            );

            $this->ensureEnabled(
                $enabled,
                'subcategory'
            );

            $this->ensureEnabled(
                $enabled,
                'material_type'
            );
        }


        /*
         * Eliminamos posibles duplicados.
         */
        $enabled =
            array_values(
                array_unique(
                    $enabled
                )
            );


        MaterialDetailSetting::updateOrCreate(
            [
                'tenant_id' =>
                    $tenantId,

                'company_id' =>
                    $companyId,
            ],
            [
                'enabled_sections' =>
                    $enabled,
            ]
        );


        return redirect()
            ->back()
            ->with(
                'success',
                'Configuración de detalles de producto guardada correctamente.'
            );
    }

    private function ensureEnabled(array &$enabled,$key) {
        if (
        !in_array(
            $key,
            $enabled,
            true
        )
        ) {
            $enabled[] =
                $key;
        }
    }
}