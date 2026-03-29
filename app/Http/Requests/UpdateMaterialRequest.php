<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'material_id'       => 'required|exists:materials,id',

            'description'       => 'required|string|max:255',
            'name'              => 'required|string|max:255',

            'unit_measure'      => 'nullable|exists:unit_measures,id',
            'typescrap'         => 'nullable|exists:typescraps,id',
            'category'          => 'nullable|exists:categories,id',
            'subcategory'       => 'nullable|exists:subcategories,id',
            'brand'             => 'nullable|exists:brands,id',
            'exampler'          => 'nullable|exists:examplers,id',
            'genero'            => 'nullable|exists:warrants,id',
            'tipo_venta'        => 'nullable|exists:tipo_ventas,id',
            'type_tax_id'       => 'nullable|integer',

            'perecible'         => 'nullable|in:s,n',
            'unit_price'        => 'nullable|numeric|between:0,99999999.99',

            'image'             => 'nullable|image',

            'tipo_variantes'    => 'required|in:0,1',
            'variantes_json'    => 'required|string',

            // Compatibilidad UI / campos auxiliares
            'sku_sin_variantes'    => 'nullable|string|max:255',
            'codigo_sin_variantes' => 'nullable|string|max:255',

            'talla'             => 'nullable|array',
            'talla.*'           => 'nullable|integer',

            'color'             => 'nullable|array',
            'color.*'           => 'nullable|integer',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tipoVariantes = (string) $this->input('tipo_variantes');
            $variantes = json_decode($this->input('variantes_json', '[]'), true);

            if (!is_array($variantes)) {
                $validator->errors()->add('variantes_json', 'El formato de variantes es inválido.');
                return;
            }

            if (count($variantes) === 0) {
                $validator->errors()->add('variantes_json', 'Debe enviar al menos un registro de variante.');
                return;
            }

            if ($tipoVariantes === '0' && count($variantes) !== 1) {
                $validator->errors()->add('variantes_json', 'Cuando el producto no tiene variantes, solo debe enviarse un registro.');
            }

            $skus = [];
            $variantKeys = [];

            foreach ($variantes as $index => $variante) {
                $fila = $index + 1;

                $variantId = $variante['variant_id'] ?? null;
                $tallaId = $variante['talla_id'] ?? null;
                $colorId = $variante['color_id'] ?? null;
                $stockItemId = $variante['stock_item_id'] ?? null;

                $sku = trim($variante['sku'] ?? '');
                $barcode = trim($variante['codigo_barras'] ?? '');

                $isActive = $variante['is_active'] ?? 1;
                $afectoInventario = $variante['afecto_inventario'] ?? 1;
                $pack = $variante['pack'] ?? 0;
                $cantidadPack = $variante['cantidad_pack'] ?? 1;

                $inventoryLevels = $variante['inventory_levels'] ?? [];

                // =========================
                // SKU obligatorio y único en el payload
                // =========================
                if ($sku === '') {
                    $validator->errors()->add('variantes_json', "La fila {$fila} no tiene SKU.");
                } else {
                    if (in_array(mb_strtolower($sku), $skus, true)) {
                        $validator->errors()->add('variantes_json', "El SKU '{$sku}' está repetido en el formulario.");
                    }
                    $skus[] = mb_strtolower($sku);
                }

                // =========================
                // Reglas por tipo
                // =========================
                if ($tipoVariantes === '1') {
                    if (empty($tallaId)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila} no tiene talla.");
                    }

                    if (empty($colorId)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila} no tiene color.");
                    }

                    // Evitar combinaciones duplicadas talla/color en el mismo request
                    $comboKey = ($tallaId ?: 'null') . '-' . ($colorId ?: 'null');

                    if (in_array($comboKey, $variantKeys, true)) {
                        $validator->errors()->add('variantes_json', "La combinación talla/color está repetida en la fila {$fila}.");
                    }

                    $variantKeys[] = $comboKey;
                }

                // =========================
                // Flags válidos
                // =========================
                if (!in_array((int) $isActive, [0, 1], true)) {
                    $validator->errors()->add('variantes_json', "El estado activo de la fila {$fila} es inválido.");
                }

                if (!in_array((int) $afectoInventario, [0, 1], true)) {
                    $validator->errors()->add('variantes_json', "El inventariable de la fila {$fila} es inválido.");
                }

                if (!in_array((int) $pack, [0, 1], true)) {
                    $validator->errors()->add('variantes_json', "El valor de paquete de la fila {$fila} es inválido.");
                }

                if ($cantidadPack !== '' && !is_numeric($cantidadPack)) {
                    $validator->errors()->add('variantes_json', "La cantidad por paquete de la fila {$fila} debe ser numérica.");
                }

                if (is_numeric($cantidadPack) && (float) $cantidadPack < 0) {
                    $validator->errors()->add('variantes_json', "La cantidad por paquete de la fila {$fila} no puede ser negativa.");
                }

                // =========================
                // inventory_levels
                // =========================
                if (!is_array($inventoryLevels)) {
                    $validator->errors()->add('variantes_json', "Los inventory levels de la fila {$fila} no tienen un formato válido.");
                    continue;
                }

                $warehouseIds = [];

                foreach ($inventoryLevels as $levelIndex => $level) {
                    $subFila = $levelIndex + 1;

                    $inventoryLevelId = $level['id'] ?? null;
                    $warehouseId = $level['warehouse_id'] ?? null;
                    $minAlert = $level['min_alert'] ?? 0;
                    $maxAlert = $level['max_alert'] ?? 0;

                    if (empty($warehouseId)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, no tiene almacén.");
                        continue;
                    }

                    if (!is_numeric($warehouseId)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, tiene un almacén inválido.");
                    }

                    // Evitar warehouse repetido dentro de la misma variante
                    if (in_array((string) $warehouseId, $warehouseIds, true)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila} tiene almacenes repetidos en los inventory levels.");
                    }
                    $warehouseIds[] = (string) $warehouseId;

                    if ($inventoryLevelId !== null && $inventoryLevelId !== '' && !is_numeric($inventoryLevelId)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, tiene un id de inventory level inválido.");
                    }

                    if ($minAlert !== '' && !is_numeric($minAlert)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, tiene stock mínimo inválido.");
                    }

                    if ($maxAlert !== '' && !is_numeric($maxAlert)) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, tiene stock máximo inválido.");
                    }

                    if (is_numeric($minAlert) && (float) $minAlert < 0) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, no puede tener stock mínimo negativo.");
                    }

                    if (is_numeric($maxAlert) && (float) $maxAlert < 0) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, no puede tener stock máximo negativo.");
                    }

                    if (
                        $minAlert !== '' &&
                        $maxAlert !== '' &&
                        is_numeric($minAlert) &&
                        is_numeric($maxAlert) &&
                        (float) $minAlert > (float) $maxAlert
                    ) {
                        $validator->errors()->add('variantes_json', "La fila {$fila}, inventario {$subFila}, tiene stock mínimo mayor que stock máximo.");
                    }
                }

                // =========================
                // Reglas especiales para sin variantes
                // =========================
                if ($tipoVariantes === '0') {
                    // En simple no exigimos talla/color
                    // pero si hay stock_item_id, debe ser numérico
                    if ($stockItemId !== null && $stockItemId !== '' && !is_numeric($stockItemId)) {
                        $validator->errors()->add('variantes_json', "El stock item del producto simple es inválido.");
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
            'material_id.required' => 'El identificador del producto es obligatorio.',
            'material_id.exists'   => 'El producto que intenta editar no existe.',

            'name.required' => 'El :attribute es obligatorio.',
            'name.string'   => 'El :attribute debe contener caracteres válidos.',
            'name.max'      => 'El :attribute no debe exceder los 255 caracteres.',

            'description.required' => 'La :attribute es obligatoria.',
            'description.string'   => 'La :attribute debe contener caracteres válidos.',
            'description.max'      => 'La :attribute no debe exceder los 255 caracteres.',

            'unit_measure.exists' => 'La :attribute no existe en la base de datos.',
            'typescrap.exists'    => 'La :attribute no existe en la base de datos.',
            'category.exists'     => 'La :attribute no existe en la base de datos.',
            'subcategory.exists'  => 'La :attribute no existe en la base de datos.',
            'brand.exists'        => 'La :attribute no existe en la base de datos.',
            'exampler.exists'     => 'El :attribute no existe en la base de datos.',
            'genero.exists'       => 'El :attribute no existe en la base de datos.',
            'tipo_venta.exists'   => 'El :attribute no existe en la base de datos.',

            'unit_price.numeric' => 'El :attribute debe ser un número.',
            'unit_price.between' => 'El :attribute está fuera del rango permitido.',

            'image.image' => 'La :attribute debe ser una imagen válida.',

            'tipo_variantes.required' => 'Debe indicar si el producto tiene variantes.',
            'tipo_variantes.in'       => 'El tipo de variantes enviado no es válido.',

            'variantes_json.required' => 'Debe enviar la información de las variantes.',
            'variantes_json.string'   => 'El formato de variantes no es válido.',

            'perecible.in' => 'El valor de :attribute no es válido.',
        ];
    }

    public function attributes()
    {
        return [
            'material_id' => 'producto',
            'name' => 'nombre completo',
            'description' => 'descripción',
            'unit_measure' => 'unidad de medida',
            'unit_price' => 'precio unitario',
            'image' => 'imagen',
            'category' => 'categoría',
            'subcategory' => 'subcategoría',
            'brand' => 'marca',
            'exampler' => 'modelo',
            'typescrap' => 'retacería',
            'genero' => 'género',
            'tipo_venta' => 'tipo de venta',
            'tipo_variantes' => 'tipo de variantes',
            'variantes_json' => 'variantes',
            'perecible' => 'perecible',
        ];
    }
}