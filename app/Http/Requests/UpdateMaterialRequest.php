<?php

namespace App\Http\Requests;

use App\Color;
use App\InventoryLevel;
use App\Material;
use App\MaterialType;
use App\StockItem;
use App\Subtype;
use App\Talla;
use App\Variant;
use App\Warehouse;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
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

            /*
             * ========================================================
             * MATERIAL
             * ========================================================
             */

            'material_id' => [
                'required',
                'integer',

                Rule::exists(
                    'materials',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            /*
             * ========================================================
             * DATOS GENERALES
             * ========================================================
             */

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


            /*
             * ========================================================
             * CATÁLOGOS TENANT
             * ========================================================
             */

            'unit_measure' => [
                'nullable',
                'integer',

                Rule::exists(
                    'unit_measures',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'typescrap' => [
                'nullable',
                'integer',

                Rule::exists(
                    'typescraps',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'category' => [
                'nullable',
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'subcategory' => [
                'nullable',
                'integer',

                Rule::exists(
                    'subcategories',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'material_type' => [
                'nullable',
                'integer',

                Rule::exists(
                    'material_types',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'subtype' => [
                'nullable',
                'integer',

                Rule::exists(
                    'subtypes',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'brand' => [
                'nullable',
                'integer',

                Rule::exists(
                    'brands',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            /*
             * Exampler actualmente no usa SoftDeletes.
             */
            'exampler' => [
                'nullable',
                'integer',

                Rule::exists(
                    'examplers',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query->where(
                        'tenant_id',
                        $tenantId
                    );
                }),
            ],


            'genero' => [
                'nullable',
                'integer',

                Rule::exists(
                    'generos',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            /*
             * Estos catálogos todavía no forman parte
             * del aislamiento que estamos haciendo.
             */
            'tipo_venta' => [
                'nullable',
                'integer',
                'exists:tipo_ventas,id',
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
                'between:0,99999999.99',
            ],


            'image' => [
                'nullable',
                'image',
            ],


            /*
             * ========================================================
             * TIPO PRODUCTO
             * ========================================================
             */

            'tipo_variantes' => [
                'required',
                'in:0,1',
            ],

            'variantes_json' => [
                'required',
                'string',
            ],


            /*
             * ========================================================
             * CAMPOS AUXILIARES UI
             * ========================================================
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


            'talla' => [
                'nullable',
                'array',
            ],

            'talla.*' => [
                'nullable',
                'integer',

                Rule::exists(
                    'tallas',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],


            'color' => [
                'nullable',
                'array',
            ],

            'color.*' => [
                'nullable',
                'integer',

                Rule::exists(
                    'colors',
                    'id'
                )->where(function ($query) use ($tenantId) {

                    $query->where(
                        'tenant_id',
                        $tenantId
                    );
                }),
            ],
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $tenantId =
                TenantContext::tenantId();

            $companyId =
                TenantContext::companyId();

            $materialId =
                $this->input(
                    'material_id'
                );


            /*
             * ========================================================
             * MATERIAL DEL TENANT ACTUAL
             * ========================================================
             */

            $material =
                Material::query()
                    ->where(
                        'id',
                        $materialId
                    )
                    ->first();

            /*
             * La Rule anterior debería detectar esto,
             * pero no continuamos con las validaciones
             * relacionadas si el Material no existe.
             */
            if (!$material) {
                return;
            }


            /*
             * ========================================================
             * NO PERMITIR SIMPLE <-> VARIANTES DESDE EDIT
             * ========================================================
             */

            $tipoVariantes =
                (string) $this->input(
                    'tipo_variantes'
                );

            $materialTieneVariantes =
                $material
                    ->variants()
                    ->exists();

            $tipoActual =
                $materialTieneVariantes
                    ? '1'
                    : '0';


            if (
                $tipoVariantes !==
                $tipoActual
            ) {

                $validator
                    ->errors()
                    ->add(
                        'tipo_variantes',
                        'No se puede cambiar un producto de simple a variantes o de variantes a simple desde la edición.'
                    );

                /*
                 * Continuamos validando el resto para devolver
                 * todos los errores posibles en una respuesta.
                 */
            }


            /*
             * ========================================================
             * COHERENCIA DE CATÁLOGOS
             * ========================================================
             */

            $categoryId =
                $this->input(
                    'category'
                );

            $subcategoryId =
                $this->input(
                    'subcategory'
                );

            $materialTypeId =
                $this->input(
                    'material_type'
                );

            $subtypeId =
                $this->input(
                    'subtype'
                );

            $brandId =
                $this->input(
                    'brand'
                );

            $examplerId =
                $this->input(
                    'exampler'
                );


            /*
             * Category → Subcategory
             */

            if (
                $categoryId &&
                $subcategoryId
            ) {

                $valid =
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

                if (!$valid) {

                    $validator
                        ->errors()
                        ->add(
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

                $valid =
                    MaterialType::query()
                        ->where(
                            'id',
                            $materialTypeId
                        )
                        ->where(
                            'subcategory_id',
                            $subcategoryId
                        )
                        ->exists();

                if (!$valid) {

                    $validator
                        ->errors()
                        ->add(
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

                $valid =
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

                if (!$valid) {

                    $validator
                        ->errors()
                        ->add(
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

                $valid =
                    \App\Exampler::query()
                        ->where(
                            'id',
                            $examplerId
                        )
                        ->where(
                            'brand_id',
                            $brandId
                        )
                        ->exists();

                if (!$valid) {

                    $validator
                        ->errors()
                        ->add(
                            'exampler',
                            'El modelo seleccionado no pertenece a la marca indicada.'
                        );
                }
            }


            /*
             * ========================================================
             * VARIANTES JSON
             * ========================================================
             */

            $variantes =
                json_decode(
                    $this->input(
                        'variantes_json',
                        '[]'
                    ),
                    true
                );


            if (!is_array($variantes)) {

                $validator
                    ->errors()
                    ->add(
                        'variantes_json',
                        'El formato de variantes es inválido.'
                    );

                return;
            }


            if (count($variantes) === 0) {

                $validator
                    ->errors()
                    ->add(
                        'variantes_json',
                        'Debe enviar al menos un registro.'
                    );

                return;
            }


            /*
             * Producto simple:
             * exactamente un StockItem.
             */

            if (
                $tipoVariantes === '0' &&
                count($variantes) !== 1
            ) {

                $validator
                    ->errors()
                    ->add(
                        'variantes_json',
                        'Cuando el producto no tiene variantes, solo debe enviarse un registro.'
                    );
            }


            /*
             * Utilizados para detectar duplicados
             * dentro del mismo request.
             */

            $skus = [];

            $barcodes = [];

            $variantKeys = [];


            foreach (
                $variantes as $index => $variante
            ) {

                $fila =
                    $index + 1;


                $variantId =
                    $variante[
                    'variant_id'
                    ] ?? null;

                $stockItemId =
                    $variante[
                    'stock_item_id'
                    ] ?? null;

                $tallaId =
                    $variante[
                    'talla_id'
                    ] ?? null;

                $colorId =
                    $variante[
                    'color_id'
                    ] ?? null;


                $sku =
                    trim(
                        $variante[
                        'sku'
                        ] ?? ''
                    );

                $barcode =
                    trim(
                        $variante[
                        'codigo_barras'
                        ] ?? ''
                    );


                $isActive =
                    $variante[
                    'is_active'
                    ] ?? 1;

                $afectoInventario =
                    $variante[
                    'afecto_inventario'
                    ] ?? 1;

                $pack =
                    $variante[
                    'pack'
                    ] ?? 0;

                $cantidadPack =
                    $variante[
                    'cantidad_pack'
                    ] ?? 1;


                $inventoryLevels =
                    $variante[
                    'inventory_levels'
                    ] ?? [];


                /*
                 * ====================================================
                 * RESOLVER STOCKITEM ACTUAL
                 * ====================================================
                 *
                 * Lo necesitamos para permitir que el propio
                 * StockItem conserve su SKU/barcode.
                 */

                $currentStockItemId =
                    null;


                /*
                 * Producto con variantes.
                 */
                if (
                    $tipoVariantes === '1' &&
                    $variantId
                ) {

                    $variant =
                        Variant::query()
                            ->where(
                                'id',
                                $variantId
                            )
                            ->where(
                                'material_id',
                                $material->id
                            )
                            ->first();


                    if (!$variant) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La variante de la fila {$fila} no pertenece al producto que se está editando."
                            );

                    } else {

                        $currentStockItem =
                            $variant
                                ->stockItem()
                                ->first();

                        if ($currentStockItem) {

                            $currentStockItemId =
                                $currentStockItem->id;
                        }
                    }
                }


                /*
                 * Producto simple.
                 */
                if (
                    $tipoVariantes === '0' &&
                    $stockItemId
                ) {

                    $currentStockItem =
                        StockItem::query()
                            ->where(
                                'id',
                                $stockItemId
                            )
                            ->where(
                                'material_id',
                                $material->id
                            )
                            ->whereNull(
                                'variant_id'
                            )
                            ->first();


                    if (!$currentStockItem) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                'El StockItem del producto simple no pertenece al producto que se está editando.'
                            );

                    } else {

                        $currentStockItemId =
                            $currentStockItem->id;
                    }
                }


                /*
                 * ====================================================
                 * SKU
                 * ====================================================
                 */

                if ($sku === '') {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "La fila {$fila} no tiene SKU."
                        );

                } else {

                    if (
                        mb_strlen(
                            $sku
                        ) > 191
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El SKU de la fila {$fila} no debe exceder los 191 caracteres."
                            );
                    }


                    $normalizedSku =
                        mb_strtolower(
                            $sku
                        );


                    if (
                    in_array(
                        $normalizedSku,
                        $skus,
                        true
                    )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El SKU '{$sku}' está repetido en el formulario."
                            );

                    } else {

                        $skus[] =
                            $normalizedSku;
                    }


                    /*
                     * Único dentro del Tenant.
                     *
                     * StockItem tiene TenantScope.
                     */
                    $skuQuery =
                        StockItem::query()
                            ->where(
                                'sku',
                                $sku
                            );


                    if ($currentStockItemId) {

                        $skuQuery->where(
                            'id',
                            '<>',
                            $currentStockItemId
                        );
                    }


                    if ($skuQuery->exists()) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El SKU '{$sku}' ya se encuentra registrado."
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
                        mb_strlen(
                            $barcode
                        ) > 191
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El código de barras de la fila {$fila} no debe exceder los 191 caracteres."
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

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El código de barras '{$barcode}' está repetido en el formulario."
                            );

                    } else {

                        $barcodes[] =
                            $normalizedBarcode;
                    }


                    $barcodeQuery =
                        StockItem::query()
                            ->where(
                                'barcode',
                                $barcode
                            );


                    if ($currentStockItemId) {

                        $barcodeQuery->where(
                            'id',
                            '<>',
                            $currentStockItemId
                        );
                    }


                    if (
                    $barcodeQuery->exists()
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El código de barras '{$barcode}' ya se encuentra registrado."
                            );
                    }
                }


                /*
                 * ====================================================
                 * PRODUCTO CON VARIANTES
                 * ====================================================
                 */

                if (
                    $tipoVariantes === '1'
                ) {

                    if (!$tallaId) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila} no tiene talla."
                            );
                    }


                    if (!$colorId) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila} no tiene color."
                            );
                    }


                    /*
                     * Talla Tenant-aware.
                     */

                    if ($tallaId) {

                        $tallaExists =
                            Talla::query()
                                ->where(
                                    'id',
                                    $tallaId
                                )
                                ->exists();


                        if (!$tallaExists) {

                            $validator
                                ->errors()
                                ->add(
                                    'variantes_json',
                                    "La talla de la fila {$fila} no pertenece al grupo empresarial actual."
                                );
                        }
                    }


                    /*
                     * Color Tenant-aware.
                     */

                    if ($colorId) {

                        $colorExists =
                            Color::query()
                                ->where(
                                    'id',
                                    $colorId
                                )
                                ->exists();


                        if (!$colorExists) {

                            $validator
                                ->errors()
                                ->add(
                                    'variantes_json',
                                    "El color de la fila {$fila} no pertenece al grupo empresarial actual."
                                );
                        }
                    }


                    /*
                     * No repetir combinación dentro
                     * del mismo formulario.
                     */

                    if (
                        $tallaId &&
                        $colorId
                    ) {

                        $comboKey =
                            $tallaId .
                            ':' .
                            $colorId;


                        if (
                        in_array(
                            $comboKey,
                            $variantKeys,
                            true
                        )
                        ) {

                            $validator
                                ->errors()
                                ->add(
                                    'variantes_json',
                                    "La combinación talla/color está repetida en la fila {$fila}."
                                );

                        } else {

                            $variantKeys[] =
                                $comboKey;
                        }


                        /*
                         * No permitir que la combinación ya exista
                         * en otra Variant del mismo Material.
                         *
                         * Esto también evita recrear una variante
                         * antigua que simplemente no vino en el
                         * formulario.
                         */

                        $combinationQuery =
                            Variant::query()
                                ->where(
                                    'material_id',
                                    $material->id
                                )
                                ->where(
                                    'talla_id',
                                    $tallaId
                                )
                                ->where(
                                    'color_id',
                                    $colorId
                                );


                        if ($variantId) {

                            $combinationQuery
                                ->where(
                                    'id',
                                    '<>',
                                    $variantId
                                );
                        }


                        if (
                        $combinationQuery
                            ->exists()
                        ) {

                            $validator
                                ->errors()
                                ->add(
                                    'variantes_json',
                                    "La combinación talla/color de la fila {$fila} ya existe para este producto."
                                );
                        }
                    }
                }


                /*
                 * ====================================================
                 * PRODUCTO SIMPLE
                 * ====================================================
                 */

                if (
                    $tipoVariantes === '0'
                ) {

                    /*
                     * Una petición manipulada no debe convertir
                     * silenciosamente el producto simple en variante.
                     */

                    if (
                        $tallaId ||
                        $colorId
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                'Un producto simple no puede tener talla ni color asociados como variante.'
                            );
                    }
                }


                /*
                 * ====================================================
                 * FLAGS
                 * ====================================================
                 */

                if (
                !in_array(
                    (int) $isActive,
                    [0, 1],
                    true
                )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "El estado activo de la fila {$fila} es inválido."
                        );
                }


                if (
                !in_array(
                    (int) $afectoInventario,
                    [0, 1],
                    true
                )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "El valor inventariable de la fila {$fila} es inválido."
                        );
                }


                if (
                !in_array(
                    (int) $pack,
                    [0, 1],
                    true
                )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "El valor de paquete de la fila {$fila} es inválido."
                        );
                }


                if (
                    $cantidadPack !== '' &&
                    !is_numeric(
                        $cantidadPack
                    )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "La cantidad por paquete de la fila {$fila} debe ser numérica."
                        );
                }


                if (
                    is_numeric(
                        $cantidadPack
                    ) &&
                    (float) $cantidadPack < 0
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "La cantidad por paquete de la fila {$fila} no puede ser negativa."
                        );
                }


                /*
                 * ====================================================
                 * INVENTORY LEVELS
                 * ====================================================
                 *
                 * Desde Edit solamente permitimos actualizar
                 * InventoryLevels existentes.
                 *
                 * Los InventoryLevels nuevos son creados por
                 * DefaultInventoryLocationResolver en backend.
                 */

                if (
                !is_array(
                    $inventoryLevels
                )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'variantes_json',
                            "Los niveles de inventario de la fila {$fila} tienen un formato inválido."
                        );

                    continue;
                }


                $warehouseIds = [];


                foreach (
                    $inventoryLevels
                    as $levelIndex => $level
                ) {

                    $subFila =
                        $levelIndex + 1;


                    $inventoryLevelId =
                        $level[
                        'id'
                        ] ?? null;

                    $warehouseId =
                        $level[
                        'warehouse_id'
                        ] ?? null;

                    $minAlert =
                        $level[
                        'min_alert'
                        ] ?? 0;

                    $maxAlert =
                        $level[
                        'max_alert'
                        ] ?? 0;


                    /*
                     * Si existe una fila de inventario enviada,
                     * debe representar un registro real.
                     */

                    if (!$inventoryLevelId) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, no tiene un nivel de inventario válido."
                            );

                        continue;
                    }


                    if (
                    !is_numeric(
                        $inventoryLevelId
                    )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, tiene un identificador inválido."
                            );

                        continue;
                    }


                    if (!$warehouseId) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, no tiene almacén."
                            );

                        continue;
                    }


                    if (
                    !is_numeric(
                        $warehouseId
                    )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, tiene un almacén inválido."
                            );

                        continue;
                    }


                    /*
                     * No repetir Warehouse dentro del payload
                     * de un StockItem.
                     */

                    if (
                    in_array(
                        (string) $warehouseId,
                        $warehouseIds,
                        true
                    )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila} tiene almacenes repetidos en los niveles de inventario."
                            );

                    } else {

                        $warehouseIds[] =
                            (string) $warehouseId;
                    }


                    /*
                     * Warehouse debe pertenecer a la Company actual.
                     */

                    $warehouse =
                        Warehouse::query()
                            ->where(
                                'id',
                                $warehouseId
                            )
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->first();


                    if (!$warehouse) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El almacén de la fila {$fila}, inventario {$subFila}, no pertenece a la empresa actual."
                            );

                        continue;
                    }


                    /*
                     * Para validar el InventoryLevel necesitamos
                     * conocer el StockItem actual.
                     *
                     * Las variantes nuevas no deben enviar
                     * InventoryLevels.
                     */

                    if (!$currentStockItemId) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila} es nueva y no puede modificar niveles de inventario existentes."
                            );

                        continue;
                    }


                    $inventoryLevel =
                        InventoryLevel::query()
                            ->where(
                                'id',
                                $inventoryLevelId
                            )
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'stock_item_id',
                                $currentStockItemId
                            )
                            ->where(
                                'warehouse_id',
                                $warehouseId
                            )
                            ->first();


                    if (!$inventoryLevel) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "El nivel de inventario de la fila {$fila}, inventario {$subFila}, no pertenece al producto, almacén o empresa actual."
                            );
                    }


                    /*
                     * Min / Max.
                     */

                    if (
                        $minAlert !== '' &&
                        !is_numeric(
                            $minAlert
                        )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, tiene stock mínimo inválido."
                            );
                    }


                    if (
                        $maxAlert !== '' &&
                        !is_numeric(
                            $maxAlert
                        )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, tiene stock máximo inválido."
                            );
                    }


                    if (
                        is_numeric(
                            $minAlert
                        ) &&
                        (float) $minAlert < 0
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, no puede tener stock mínimo negativo."
                            );
                    }


                    if (
                        is_numeric(
                            $maxAlert
                        ) &&
                        (float) $maxAlert < 0
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, no puede tener stock máximo negativo."
                            );
                    }


                    if (
                        $minAlert !== '' &&
                        $maxAlert !== '' &&
                        is_numeric(
                            $minAlert
                        ) &&
                        is_numeric(
                            $maxAlert
                        ) &&
                        (float) $minAlert >
                        (float) $maxAlert
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'variantes_json',
                                "La fila {$fila}, inventario {$subFila}, tiene stock mínimo mayor que stock máximo."
                            );
                    }
                }
            }
        });
    }


    public function messages()
    {
        return [

            'material_id.required' =>
                'El identificador del producto es obligatorio.',

            'material_id.exists' =>
                'El producto no existe o no pertenece al grupo empresarial actual.',


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

            'tipo_venta.exists' =>
                'El :attribute no existe en la base de datos.',


            'talla.*.exists' =>
                'Una de las tallas no existe o no pertenece al grupo empresarial actual.',

            'color.*.exists' =>
                'Uno de los colores no existe o no pertenece al grupo empresarial actual.',


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

            'material_id' =>
                'producto',

            'name' =>
                'nombre completo',

            'description' =>
                'descripción',

            'unit_measure' =>
                'unidad de medida',

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

            'typescrap' =>
                'tipo de retacería',

            'genero' =>
                'género',

            'tipo_venta' =>
                'tipo de venta',

            'tipo_variantes' =>
                'tipo de variantes',

            'variantes_json' =>
                'variantes',

            'perecible' =>
                'perecible',
        ];
    }
}