<?php

namespace App\Http\Requests;

use App\Exampler;
use App\Subtype;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = TenantContext::tenantId();

        return [
            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'unit_measure' => [
                'nullable',
                'integer',
                Rule::exists('unit_measures', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'typescrap' => [
                'nullable',
                'integer',
                Rule::exists('typescraps', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'category' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'subcategory' => [
                'nullable',
                'integer',
                Rule::exists('subcategories', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'material_type' => [
                'nullable',
                'integer',
                Rule::exists('material_types', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'subtype' => [
                'nullable',
                'integer',
                Rule::exists('subtypes', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'brand' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'exampler' => [
                'nullable',
                'integer',
                Rule::exists('examplers', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'genero' => [
                'nullable',
                'integer',
                Rule::exists('generos', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            /*
             * Estos dos todavía no los hemos clasificado
             * como Tenant-level.
             */
            'tipo_venta' => [
                'nullable',
                'integer',
            ],

            'type_tax_id' => [
                'nullable',
                'integer',
            ],

            'perecible' => [
                'nullable',
                'in:s,n',
            ],

            'unit_price' => [
                'nullable',
                'numeric',
                'between:0,99999.99',
            ],

            'image' => [
                'nullable',
                'image',
            ],

            'tipo_variantes' => [
                'required',
                'in:0,1',
            ],

            'variantes_json' => [
                'required',
                'string',
            ],

            /*
             * Campos legacy/formulario.
             * La información real de StockItem se valida
             * dentro de variantes_json.
             */
            'sku_sin_variantes' => [
                'nullable',
                'string',
                'max:191',
            ],

            'codigo_sin_variantes' => [
                'nullable',
                'string',
                'max:191',
            ],

            'stock_min' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'stock_max' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'talla' => [
                'nullable',
                'array',
            ],

            'talla.*' => [
                'nullable',
                'integer',
                Rule::exists('tallas', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],

            'color' => [
                'nullable',
                'array',
            ],

            'color.*' => [
                'nullable',
                'integer',
                Rule::exists('colors', 'id')
                    ->where(function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $tenantId =
                TenantContext::tenantId();

            $tipoVariantes =
                (string) $this->input(
                    'tipo_variantes'
                );

            $variantes =
                json_decode(
                    $this->input(
                        'variantes_json',
                        '[]'
                    ),
                    true
                );

            if (!is_array($variantes)) {
                $validator->errors()->add(
                    'variantes_json',
                    'El formato de variantes es inválido.'
                );

                return;
            }

            if (count($variantes) === 0) {
                $validator->errors()->add(
                    'variantes_json',
                    'Debe enviar al menos un registro.'
                );

                return;
            }


            /*
             * PRODUCTO SIN VARIANTES
             *
             * Debe existir exactamente un registro,
             * porque corresponde al único StockItem.
             */

            if (
                $tipoVariantes === '0' &&
                count($variantes) !== 1
            ) {
                $validator->errors()->add(
                    'variantes_json',
                    'Cuando el producto no tiene variantes, solo debe enviarse un registro.'
                );
            }


            $skus = [];
            $barcodes = [];
            $variantCombinations = [];

            /*
 * ============================================================
 * COHERENCIA DE CATÁLOGOS PADRE / HIJO
 * ============================================================
 */

            $categoryId =
                $this->input('category');

            $subcategoryId =
                $this->input('subcategory');

            $materialTypeId =
                $this->input('material_type');

            $subtypeId =
                $this->input('subtype');

            $brandId =
                $this->input('brand');

            $examplerId =
                $this->input('exampler');


            /*
             * Category → Subcategory
             */
            if (
                $categoryId &&
                $subcategoryId
            ) {

                $subcategoryValid =
                    \App\Subcategory::query()
                        ->where(
                            'id',
                            $subcategoryId
                        )
                        ->where(
                            'category_id',
                            $categoryId
                        )
                        ->exists();

                if (!$subcategoryValid) {
                    $validator->errors()->add(
                        'subcategory',
                        'La subcategoría seleccionada no pertenece a la categoría indicada.'
                    );
                }
            }


            /*
             * Subcategory → MaterialType
             */
            if (
                $subcategoryId &&
                $materialTypeId
            ) {

                $materialTypeValid =
                    \App\MaterialType::query()
                        ->where(
                            'id',
                            $materialTypeId
                        )
                        ->where(
                            'subcategory_id',
                            $subcategoryId
                        )
                        ->exists();

                if (!$materialTypeValid) {
                    $validator->errors()->add(
                        'material_type',
                        'El tipo de material seleccionado no pertenece a la subcategoría indicada.'
                    );
                }
            }


            /*
             * MaterialType → Subtype
             */
            if (
                $materialTypeId &&
                $subtypeId
            ) {

                $subtypeValid =
                    Subtype::query()
                        ->where(
                            'id',
                            $subtypeId
                        )
                        ->where(
                            'material_type_id',
                            $materialTypeId
                        )
                        ->exists();

                if (!$subtypeValid) {
                    $validator->errors()->add(
                        'subtype',
                        'El subtipo seleccionado no pertenece al tipo de material indicado.'
                    );
                }
            }


            /*
             * Brand → Exampler
             */
            if (
                $brandId &&
                $examplerId
            ) {

                $examplerValid =
                    Exampler::query()
                        ->where(
                            'id',
                            $examplerId
                        )
                        ->where(
                            'brand_id',
                            $brandId
                        )
                        ->exists();

                if (!$examplerValid) {
                    $validator->errors()->add(
                        'exampler',
                        'El modelo seleccionado no pertenece a la marca indicada.'
                    );
                }
            }

            foreach (
                $variantes as $index => $variante
            ) {

                $fila = $index + 1;

                $sku =
                    trim(
                        $variante['sku'] ?? ''
                    );

                $barcode =
                    trim(
                        $variante['codigo_barras'] ?? ''
                    );

                $tallaId =
                    $variante['talla_id'] ?? null;

                $colorId =
                    $variante['color_id'] ?? null;


                /*
                 * ====================================================
                 * SKU
                 * ====================================================
                 */

                if ($sku === '') {
                    $validator->errors()->add(
                        'variantes_json',
                        "El registro {$fila} no tiene SKU."
                    );
                } else {

                    if (mb_strlen($sku) > 191) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El SKU del registro {$fila} no debe exceder los 191 caracteres."
                        );
                    }

                    /*
                     * SKU repetido dentro del mismo formulario.
                     */
                    $normalizedSku =
                        mb_strtolower($sku);

                    if (
                    in_array(
                        $normalizedSku,
                        $skus,
                        true
                    )
                    ) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El SKU {$sku} se encuentra repetido en el formulario."
                        );
                    } else {
                        $skus[] =
                            $normalizedSku;
                    }


                    /*
                     * SKU ya existente en el Tenant.
                     */
                    $skuExists =
                        \App\StockItem::query()
                            ->where(
                                'sku',
                                $sku
                            )
                            ->exists();

                    if ($skuExists) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El SKU {$sku} ya existe."
                        );
                    }
                }


                /*
                 * ====================================================
                 * BARCODE
                 * ====================================================
                 */

                if ($barcode !== '') {

                    if (
                        mb_strlen($barcode) > 191
                    ) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El código de barras del registro {$fila} no debe exceder los 191 caracteres."
                        );
                    }

                    $normalizedBarcode =
                        mb_strtolower(
                            $barcode
                        );

                    if (
                    in_array(
                        $normalizedBarcode,
                        $barcodes,
                        true
                    )
                    ) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El código de barras {$barcode} se encuentra repetido en el formulario."
                        );
                    } else {
                        $barcodes[] =
                            $normalizedBarcode;
                    }


                    /*
                     * Barcode ya existente en el Tenant.
                     */
                    $barcodeExists =
                        \App\StockItem::query()
                            ->where(
                                'barcode',
                                $barcode
                            )
                            ->exists();

                    if ($barcodeExists) {
                        $validator->errors()->add(
                            'variantes_json',
                            "El código de barras {$barcode} ya existe."
                        );
                    }
                }


                /*
                 * ====================================================
                 * STOCK MÍNIMO / MÁXIMO
                 * ====================================================
                 */

                $stockMinimo =
                    $variante[
                    'stock_minimo'
                    ] ?? '';

                $stockMaximo =
                    $variante[
                    'stock_maximo'
                    ] ?? '';


                if (
                    $stockMinimo !== '' &&
                    !is_numeric(
                        $stockMinimo
                    )
                ) {
                    $validator->errors()->add(
                        'variantes_json',
                        "El stock mínimo del registro {$fila} debe ser numérico."
                    );
                }


                if (
                    $stockMaximo !== '' &&
                    !is_numeric(
                        $stockMaximo
                    )
                ) {
                    $validator->errors()->add(
                        'variantes_json',
                        "El stock máximo del registro {$fila} debe ser numérico."
                    );
                }


                if (
                    $stockMinimo !== '' &&
                    $stockMaximo !== '' &&
                    is_numeric(
                        $stockMinimo
                    ) &&
                    is_numeric(
                        $stockMaximo
                    ) &&
                    (float) $stockMinimo >
                    (float) $stockMaximo
                ) {
                    $validator->errors()->add(
                        'variantes_json',
                        "El stock mínimo no puede ser mayor al stock máximo en el registro {$fila}."
                    );
                }


                /*
                 * ====================================================
                 * PRODUCTO CON VARIANTES
                 * ====================================================
                 */

                if ($tipoVariantes === '1') {

                    if (!$tallaId) {
                        $validator->errors()->add(
                            'variantes_json',
                            "La variante {$fila} no tiene talla."
                        );
                    }

                    if (!$colorId) {
                        $validator->errors()->add(
                            'variantes_json',
                            "La variante {$fila} no tiene color."
                        );
                    }


                    /*
                     * Validar Talla contra Tenant actual.
                     */

                    if ($tallaId) {

                        $tallaExists =
                            \App\Talla::query()
                                ->where(
                                    'id',
                                    $tallaId
                                )
                                ->exists();

                        if (!$tallaExists) {
                            $validator->errors()->add(
                                'variantes_json',
                                "La talla seleccionada en la variante {$fila} no pertenece al grupo empresarial actual."
                            );
                        }
                    }


                    /*
                     * Validar Color contra Tenant actual.
                     */

                    if ($colorId) {

                        $colorExists =
                            \App\Color::query()
                                ->where(
                                    'id',
                                    $colorId
                                )
                                ->exists();

                        if (!$colorExists) {
                            $validator->errors()->add(
                                'variantes_json',
                                "El color seleccionado en la variante {$fila} no pertenece al grupo empresarial actual."
                            );
                        }
                    }


                    /*
                     * No permitir Talla + Color repetidos
                     * dentro del mismo Material.
                     */

                    if (
                        $tallaId &&
                        $colorId
                    ) {

                        $combination =
                            $tallaId .
                            ':' .
                            $colorId;

                        if (
                        in_array(
                            $combination,
                            $variantCombinations,
                            true
                        )
                        ) {
                            $validator->errors()->add(
                                'variantes_json',
                                "La combinación talla/color de la variante {$fila} está repetida."
                            );
                        } else {
                            $variantCombinations[] =
                                $combination;
                        }
                    }

                } else {

                    /*
                     * Producto simple:
                     * no debe tener Variant.
                     *
                     * Si el JSON conserva talla/color
                     * por algún comportamiento JS legacy,
                     * por ahora simplemente no los usamos.
                     *
                     * No hacemos fallar el request todavía
                     * para no romper el formulario actual.
                     */
                }
            }
        });
    }


    public function messages()
    {
        return [
            'name.required' =>
                'El :attribute es obligatorio.',

            'name.string' =>
                'El :attribute debe contener caracteres válidos.',

            'name.max' =>
                'El :attribute no debe exceder los 255 caracteres.',


            'description.required' =>
                'La :attribute es obligatoria.',

            'description.string' =>
                'La :attribute debe contener caracteres válidos.',

            'description.max' =>
                'La :attribute no debe exceder los 255 caracteres.',


            'unit_measure.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',

            'typescrap.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',

            'category.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',

            'subcategory.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',

            'material_type.exists' =>
                'El :attribute no existe o no pertenece al grupo empresarial actual.',

            'subtype.exists' =>
                'El :attribute no existe o no pertenece al grupo empresarial actual.',

            'brand.exists' =>
                'La :attribute no existe o no pertenece al grupo empresarial actual.',

            'exampler.exists' =>
                'El :attribute no existe o no pertenece al grupo empresarial actual.',

            'genero.exists' =>
                'El :attribute no existe o no pertenece al grupo empresarial actual.',

            'talla.*.exists' =>
                'Una de las tallas no existe o no pertenece al grupo empresarial actual.',

            'color.*.exists' =>
                'Uno de los colores no existe o no pertenece al grupo empresarial actual.',


            'stock_max.numeric' =>
                'El :attribute debe ser un número.',

            'stock_max.min' =>
                'El :attribute debe ser mayor o igual a 0.',

            'stock_min.numeric' =>
                'El :attribute debe ser un número.',

            'stock_min.min' =>
                'El :attribute debe ser mayor o igual a 0.',


            'unit_price.numeric' =>
                'El :attribute debe ser un número.',

            'unit_price.between' =>
                'El :attribute está fuera del rango permitido.',


            'image.image' =>
                'La :attribute debe ser una imagen válida.',


            'tipo_variantes.required' =>
                'Debe indicar si el producto tiene variantes.',

            'tipo_variantes.in' =>
                'El tipo de variantes enviado no es válido.',


            'variantes_json.required' =>
                'Debe enviar la información del producto.',

            'variantes_json.string' =>
                'El formato de variantes no es válido.',


            'perecible.in' =>
                'El valor de :attribute no es válido.',
        ];
    }


    public function attributes()
    {
        return [
            'name' =>
                'nombre completo',

            'description' =>
                'descripción',

            'unit_measure' =>
                'unidad de medida',

            'stock_max' =>
                'stock máximo',

            'stock_min' =>
                'stock mínimo',

            'unit_price' =>
                'precio unitario',

            'image' =>
                'imagen',

            'category' =>
                'categoría',

            'subcategory' =>
                'subcategoría',

            'material_type' =>
                'tipo de material',

            'subtype' =>
                'subtipo de material',

            'brand' =>
                'marca',

            'exampler' =>
                'modelo',

            'genero' =>
                'género',

            'typescrap' =>
                'retacería',

            'tipo_variantes' =>
                'tipo de variantes',

            'variantes_json' =>
                'variantes',

            'perecible' =>
                'perecible',
        ];
    }
}