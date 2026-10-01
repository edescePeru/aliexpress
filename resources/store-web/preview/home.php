<?php

// PREVIEW FIXTURE ONLY. Never a production fallback or tenant/company projection.
abort_unless(app()->environment('local') && config('store-web.preview') === true
    && ($storeWebFixture ?? false) === true, 404);

// BACKEND CONTRACT PENDING: these are local presentation slots, not an API schema.
return [
    'branding' => [
        'logo' => asset('store-web/img/logo.png'),
        'label' => 'Catálogo del negocio',
        'name' => 'Nombre del negocio',
        'description' => 'Encuentra nuestros productos y promociones.',
        'footer' => 'Catálogo público de productos y promociones.',
        'socials' => ['instagram' => null, 'tiktok' => null, 'facebook' => null, 'youtube' => null],
    ],
    // BACKEND CONTRACT PENDING: title, description, CTA/URL, validity and visibility.
    'banner' => [
        'eyebrow' => 'Selección especial',
        'title' => 'Descubre lo mejor de nuestro catálogo',
        'description' => 'Productos seleccionados y promociones en un solo lugar.',
        'cta' => 'Explorar productos',
        'url' => route('store-web.catalog'),
    ],
    // Literal approved samples. No selection/ranking, dates, stock or discount math.
    'featured' => [
        ['name' => 'Producto esencial', 'image' => 'product-1.jpg', 'price' => 'S/ 59.00', 'badge' => null],
        ['name' => 'Selección especial', 'image' => 'product-2.jpg', 'price' => 'S/ 49.00', 'badge' => 'Nuevo'],
        ['name' => 'Favorito del catálogo', 'image' => 'product-3.jpg', 'price' => 'S/ 69.00', 'badge' => null],
        ['name' => 'Novedad destacada', 'image' => 'product-4.jpg', 'price' => 'S/ 39.00', 'badge' => null],
    ],
    'promotions' => [
        ['name' => 'Producto en promoción', 'image' => 'product-5.jpg', 'price' => 'S/ 49.00', 'previous' => 'S/ 59.00', 'badge' => 'Oferta'],
        ['name' => 'Precio especial', 'image' => 'product-6.jpg', 'price' => 'S/ 55.00', 'previous' => 'S/ 69.00', 'badge' => '-20%'],
    ],
];
