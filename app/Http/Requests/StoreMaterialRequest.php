<?php

namespace App\Http\Requests;

use App\Material;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'description'      => 'required|string|max:255',
            'name'             => 'required|string|max:255',

            'unit_measure'     => 'nullable|exists:unit_measures,id',
            'typescrap'        => 'nullable|exists:typescraps,id',
            'category'         => 'nullable|exists:categories,id',
            'subcategory'      => 'nullable|exists:subcategories,id',
            'brand'            => 'nullable|exists:brands,id',
            'exampler'         => 'nullable|exists:examplers,id',

            'genero'           => 'nullable|integer',
            'tipo_venta'       => 'nullable|integer',
            'type_tax_id'      => 'nullable|integer',
            'perecible'        => 'nullable|in:s,n',

            'unit_price'       => 'nullable|numeric|between:0,99999.99',

            'image'            => 'nullable|image',

            'tipo_variantes'   => 'required|in:0,1',
            'variantes_json'   => 'required|string',

            'sku_sin_variantes'    => 'nullable|string|max:255',
            'codigo_sin_variantes' => 'nullable|string|max:255',
            'stock_min'            => 'nullable|numeric|min:0',
            'stock_max'            => 'nullable|numeric|min:0',

            'talla'            => 'nullable',
            'talla.*'          => 'nullable|integer',
            'color'            => 'nullable',
            'color.*'          => 'nullable|integer',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tipoVariantes = $this->input('tipo_variantes');
            $variantes = json_decode($this->input('variantes_json', '[]'), true);

            if (!is_array($variantes)) {
                $validator->errors()->add('variantes_json', 'El formato de variantes es inválido.');
                return;
            }

            if (count($variantes) === 0) {
                $validator->errors()->add('variantes_json', 'Debe enviar al menos una variante.');
                return;
            }

            foreach ($variantes as $index => $variante) {
                $fila = $index + 1;

                if (empty($variante['sku'])) {
                    $validator->errors()->add("variantes_json", "La variante {$fila} no tiene SKU.");
                }

                if (isset($variante['stock_minimo']) && $variante['stock_minimo'] !== '' && !is_numeric($variante['stock_minimo'])) {
                    $validator->errors()->add("variantes_json", "El stock mínimo de la variante {$fila} debe ser numérico.");
                }

                if (isset($variante['stock_maximo']) && $variante['stock_maximo'] !== '' && !is_numeric($variante['stock_maximo'])) {
                    $validator->errors()->add("variantes_json", "El stock máximo de la variante {$fila} debe ser numérico.");
                }

                if (isset($variante['stock_minimo'], $variante['stock_maximo']) &&
                    $variante['stock_minimo'] !== '' &&
                    $variante['stock_maximo'] !== '' &&
                    is_numeric($variante['stock_minimo']) &&
                    is_numeric($variante['stock_maximo']) &&
                    (float)$variante['stock_minimo'] > (float)$variante['stock_maximo']) {
                    $validator->errors()->add("variantes_json", "El stock mínimo no puede ser mayor al stock máximo en la variante {$fila}.");
                }

                if ($tipoVariantes == '1') {
                    if (empty($variante['talla_id'])) {
                        $validator->errors()->add("variantes_json", "La variante {$fila} no tiene talla.");
                    }

                    if (empty($variante['color_id'])) {
                        $validator->errors()->add("variantes_json", "La variante {$fila} no tiene color.");
                    }
                }
            }

            if ($tipoVariantes == '0' && count($variantes) !== 1) {
                $validator->errors()->add('variantes_json', 'Cuando el producto no tiene variantes, solo debe enviarse un registro.');
            }
        });
    }

    public function messages()
    {
        return [
            'name.required' => 'El :attribute es obligatorio.',
            'name.string' => 'El :attribute debe contener caracteres válidos.',
            'name.max' => 'El :attribute no debe exceder los 255 caracteres.',

            'description.required' => 'La :attribute es obligatoria.',
            'description.string' => 'La :attribute debe contener caracteres válidos.',
            'description.max' => 'La :attribute no debe exceder los 255 caracteres.',

            'unit_measure.exists' => 'La :attribute no existe en la base de datos.',

            'typescrap.exists' => 'La :attribute no existe en la base de datos.',

            'stock_max.numeric' => 'El :attribute debe ser un número.',
            'stock_max.min' => 'El :attribute debe ser mayor o igual a 0.',

            'stock_min.numeric' => 'El :attribute debe ser un número.',
            'stock_min.min' => 'El :attribute debe ser mayor o igual a 0.',

            'unit_price.numeric' => 'El :attribute debe ser un número.',
            'unit_price.between' => 'El :attribute está fuera del rango permitido.',

            'image.image' => 'La :attribute debe ser una imagen válida.',

            'category.exists' => 'La :attribute no existe en la base de datos.',
            'subcategory.exists' => 'La :attribute no existe en la base de datos.',
            'brand.exists' => 'La :attribute no existe en la base de datos.',
            'exampler.exists' => 'El :attribute no existe en la base de datos.',

            'tipo_variantes.required' => 'Debe indicar si el producto tiene variantes.',
            'tipo_variantes.in' => 'El tipo de variantes enviado no es válido.',

            'variantes_json.required' => 'Debe enviar la información de las variantes.',
            'variantes_json.string' => 'El formato de variantes no es válido.',

            'perecible.in' => 'El valor de :attribute no es válido.',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nombre completo',
            'description' => 'descripción',
            'unit_measure' => 'unidad de medida',
            'stock_max' => 'stock máximo',
            'stock_min' => 'stock mínimo',
            'unit_price' => 'precio unitario',
            'image' => 'imagen',
            'category' => 'categoría',
            'subcategory' => 'subcategoría',
            'brand' => 'marca',
            'exampler' => 'modelo',
            'typescrap' => 'retacería',
            'tipo_variantes' => 'tipo de variantes',
            'variantes_json' => 'variantes',
            'perecible' => 'perecible',
        ];
    }
}
