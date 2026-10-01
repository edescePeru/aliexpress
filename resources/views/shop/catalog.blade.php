{{-- REAL NEXT 9.4: CURRENT TEMPORARY BACKEND CONTRACT. Anonymous fixtures / LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION. --}}
@extends('layouts.storeWeb', ['storeWebPage' => 'shop'])
@section('title', 'Todos los productos | Catálogo')
@section('description', 'Todos los productos del catálogo')
@section('keywords', 'catálogo, productos')
@section('content')
<main class="catalog-shop">
        <div class="catalog-container">
            <nav class="catalog-shop-categories" aria-label="Categorías del catálogo">
                <a class="active" href="{{ route('store-web.catalog') }}" aria-current="page">Todos</a>
                <a role="link" aria-disabled="true" tabindex="-1" title="No disponible: contrato pendiente">Novedades</a>
                <a role="link" aria-disabled="true" tabindex="-1" title="No disponible: contrato pendiente">Destacados</a>
                <a role="link" aria-disabled="true" tabindex="-1" title="No disponible: contrato pendiente">Promociones</a>
                <a role="link" aria-disabled="true" tabindex="-1" title="No disponible: contrato pendiente">Disponibles</a>
            </nav>

            <div class="catalog-shop-toolbar">
                <div class="catalog-shop-toolbar__summary">
                    <h1>Todos los productos</h1>
                    <span data-catalog-total role="status" aria-live="polite">{{ ($storeWebFixture ?? false) ? '9 productos' : 'Cargando productos…' }}</span>
                </div>
                <div class="catalog-shop-toolbar__actions">
                    <button class="catalog-filter-trigger" type="button" aria-controls="catalog-filter-drawer" aria-expanded="false">
                        <span class="icon_adjust-horiz" aria-hidden="true"></span> Filtros
                        <span class="catalog-filter-trigger__count" hidden>0</span>
                    </button>
                </div>
            </div>

            <section class="catalog-product-grid" aria-label="Listado de productos">
            {{-- Literal visual samples ONLY in preview; the adapter never adds mock badges to backend cards. --}}
            @if(($storeWebFixture ?? false) === true)
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><span class="catalog-product-card__badge">Nuevo</span><img src="{{ asset('store-web/img/shop/shop-1.jpg') }}" alt="Producto destacado uno"></div>
                    <div class="catalog-product-card__body"><h2>Producto destacado uno</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><img src="{{ asset('store-web/img/shop/shop-2.jpg') }}" alt="Selección del catálogo"></div>
                    <div class="catalog-product-card__body"><h2>Selección del catálogo</h2><p class="catalog-product-card__price">S/ 49.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><img src="{{ asset('store-web/img/shop/shop-3.jpg') }}" alt="Producto esencial de temporada"></div>
                    <div class="catalog-product-card__body"><h2>Producto esencial de temporada</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><img src="{{ asset('store-web/img/shop/shop-4.jpg') }}" alt="Favorito del negocio"></div>
                    <div class="catalog-product-card__body"><h2>Favorito del negocio</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><span class="catalog-product-card__badge catalog-product-card__badge--offer">Oferta</span><img src="{{ asset('store-web/img/shop/shop-5.jpg') }}" alt="Producto con precio especial"></div>
                    <div class="catalog-product-card__body"><h2>Producto con precio especial</h2><p class="catalog-product-card__price catalog-product-card__price--offer">S/ 49.00 <del>S/ 59.00</del></p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><img src="{{ asset('store-web/img/shop/shop-6.jpg') }}" alt="Novedad seleccionada"></div>
                    <div class="catalog-product-card__body"><h2>Novedad seleccionada</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><img src="{{ asset('store-web/img/shop/shop-7.jpg') }}" alt="Artículo recomendado"></div>
                    <div class="catalog-product-card__body"><h2>Artículo recomendado</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => true])

                    <div class="catalog-product-card__media"><span class="catalog-product-card__badge catalog-product-card__badge--muted">Agotado</span><img src="{{ asset('store-web/img/shop/shop-8.jpg') }}" alt="Producto temporalmente agotado"></div>
                    <div class="catalog-product-card__body"><h2>Producto temporalmente agotado</h2><p class="catalog-product-card__price">S/ 59.00</p><span class="catalog-product-card__status catalog-product-card__status--muted">No disponible</span></div>

@endcomponent
                @component('shop.partials.store-web.product-card', ['unavailable' => false])

                    <div class="catalog-product-card__media"><span class="catalog-product-card__badge catalog-product-card__badge--offer">Oferta</span><img src="{{ asset('store-web/img/shop/shop-9.jpg') }}" alt="Producto promocionado"></div>
                    <div class="catalog-product-card__body"><h2>Producto promocionado</h2><p class="catalog-product-card__price catalog-product-card__price--offer">S/ 49.00 <del>S/ 59.00</del></p><span class="catalog-product-card__status">Disponible</span></div>

@endcomponent
            @endif
            </section>

            <nav class="catalog-pagination" aria-label="Paginación del catálogo">
            @if(($storeWebFixture ?? false) === true)
                <a class="active" role="link" aria-disabled="true" tabindex="-1" aria-current="page" aria-label="Página 1">1</a>
                <a role="link" aria-disabled="true" tabindex="-1" aria-label="Página 2">2</a>
                <a role="link" aria-disabled="true" tabindex="-1" aria-label="Página 3">3</a>
                <a role="link" aria-disabled="true" tabindex="-1" aria-label="Página siguiente"><span class="arrow_right" aria-hidden="true"></span></a>
            @endif
            </nav>
        </div>
    </main>
@endsection
@section('overlays')
@include('shop.partials.store-web.filter-drawer')
@endsection

@section('scripts')
@php
    // Presentation config only. No new endpoint, context resolver or productive opt-in.
    $catalogContract = [
        'preview' => ($storeWebFixture ?? false) === true,
        'catalog' => route('store-web.catalog'),
        'products' => route('shop.products.data', ['pageNumber' => ':page']),
        'categories' => route('shop.categories.data'),
        'sizes' => route('shop.sizes.data'),
        'colors' => route('shop.colors.data'),
        'defaultImage' => asset('store-web/img/no_image.png'),
        'showPrices' => (bool) ($showPricesCatalogEmpresa ?? false),
        'search' => $search ?? '',
    ];
@endphp
<script id="store-web-catalog-contract" type="application/json">@json($catalogContract)</script>
<script src="{{ asset('store-web/js/catalog-data.js') }}?v=real-next-9-4" defer></script>
@endsection
