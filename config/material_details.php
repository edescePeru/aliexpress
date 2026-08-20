<?php
/**
 * Created by PhpStorm.
 * User: Milly
 * Date: 16/12/2025
 * Time: 06:51 PM
 */

return [
    'sections' => [
        'unit_measure' => [
            'label' => 'Unidad de medida',
            'type'  => 'relation',
            'model' => \App\UnitMeasure::class,
        ],
        'brand' => [
            'label' => 'Marca',
            'type'  => 'relation',
            'model' => \App\Brand::class,
        ],
        'exampler' => [
            'label' => 'Modelo',
            'type'  => 'relation',
            'model' => \App\Exampler::class,
        ],
        'genero' => [
            'label' => 'Género',
            'type'  => 'relation',
            'model' => \App\Genero::class,
        ],
        'talla' => [
            'label' => 'Talla',
            'type'  => 'relation',
            'model' => \App\Talla::class,
        ],
        'color' => [
            'label' => 'Color',
            'type'  => 'relation',
            'model' => \App\Color::class,
        ],
        'perecible' => [
            'label' => 'Perecible',
            'type'  => 'field',
            'field' => 'perecible', // campo en Material
        ],
        'category' => [
            'label' => 'Categorías',
            'type'  => 'relation',
            'model' => \App\Category::class,
        ],
        'subcategory' => [
            'label' => 'Subcategorías',
            'type'  => 'relation',
            'model' => \App\Subcategory::class,
        ],

        /*'calidad' => [
            'label' => 'Calidades',
            'type'  => 'relation',
            'model' => \App\Calidad::class,
        ],*/
    ],
];