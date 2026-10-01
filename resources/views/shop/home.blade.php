{{-- FRONTEND READY / BACKEND CONTRACT PENDING. PREVIEW FIXTURE ONLY, never production fallback. --}}
@php
    $homePreview = require resource_path('store-web/preview/home.php');
    $homeBranding = $homePreview['branding'];
@endphp
@extends('layouts.storeWeb', ['storeWebPage' => 'home'])
@section('title', 'Catálogo del negocio')
@section('description', 'Catálogo público del negocio')
@section('keywords', 'catálogo, productos, promociones')
@section('content')
<main class="catalog-home">
        <div class="catalog-container">
            <section class="catalog-business-intro" aria-labelledby="business-name">
                <div class="catalog-business-intro__logo" aria-hidden="true"><img src="{{ $homeBranding['logo'] }}" alt=""></div>
                <div class="catalog-business-intro__content">
                    <h1 id="business-name">{{ $homeBranding['name'] }}</h1>
                    <p>{{ $homeBranding['description'] }}</p>
                </div>
                <span class="catalog-business-intro__status"><span aria-hidden="true"></span> Catálogo público</span>
            </section>

            {{-- BACKEND CONTRACT PENDING: banner editorial, CTA/URL, validity and visibility. --}}
<section class="catalog-promo-banner" aria-labelledby="promo-title">
                <div class="catalog-promo-banner__content">
                    <span class="catalog-promo-banner__eyebrow">{{ $homePreview['banner']['eyebrow'] }}</span>
                    <h2 id="promo-title">{{ $homePreview['banner']['title'] }}</h2>
                    <p>{{ $homePreview['banner']['description'] }}</p>
                    <a href="{{ $homePreview['banner']['url'] }}">{{ $homePreview['banner']['cta'] }} <span class="arrow_right" aria-hidden="true"></span></a>
                </div>
                <div class="catalog-promo-banner__visual" aria-hidden="true"><span></span><span></span><span></span></div>
            </section>

            <section class="catalog-home-section" aria-labelledby="featured-title">
{{-- BACKEND CONTRACT PENDING: featured selection/order/badges. --}}
                <div class="catalog-home-section__heading">
                    <div><span class="catalog-home-section__eyebrow">Recomendados</span><h2 id="featured-title">Productos destacados</h2></div>
                    <span class="catalog-home-section__hint">Desliza para ver más</span>
                </div>
                <div class="catalog-featured-list" aria-label="Productos destacados">
                    @foreach($homePreview['featured'] as $card)
                    <a class="catalog-featured-product" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}">
                        <div class="catalog-featured-product__image">@if($card['badge'])<span class="catalog-featured-product__badge">{{ $card['badge'] }}</span>@endif<img src="{{ asset('store-web/img/product/' . $card['image']) }}" alt="{{ $card['name'] }}"></div>
                        <div class="catalog-featured-product__body"><h3>{{ $card['name'] }}</h3><p class="catalog-featured-product__price">{{ $card['price'] }}</p></div>
                    </a>
                    @endforeach
                </div>
                <a class="catalog-view-all" href="{{ route('store-web.catalog') }}">Ver todos los productos <span class="arrow_right" aria-hidden="true"></span></a>
            </section>

            <section class="catalog-home-section" aria-labelledby="offers-title">
{{-- BACKEND CONTRACT PENDING: promotions/prior prices/validity. --}}
                <div class="catalog-home-section__heading"><div><span class="catalog-home-section__eyebrow">Por tiempo limitado</span><h2 id="offers-title">Promociones</h2></div></div>
                <div class="catalog-offer-list">
                    @foreach($homePreview['promotions'] as $card)
                    <a class="catalog-offer-card" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}">
                        <div class="catalog-offer-card__image"><img src="{{ asset('store-web/img/product/' . $card['image']) }}" alt="{{ $card['name'] }}"></div>
                        <div class="catalog-offer-card__content"><span class="catalog-offer-card__badge">{{ $card['badge'] }}</span><h3>{{ $card['name'] }}</h3><p><strong>{{ $card['price'] }}</strong><del>{{ $card['previous'] }}</del></p></div>
                    </a>
                    @endforeach
                </div>
            </section>

            <section class="catalog-home-section catalog-home-section--categories" aria-labelledby="categories-title">
{{-- BACKEND CONTRACT PENDING: commercial collections/destination filters. --}}
                <div class="catalog-home-section__heading"><div><h2 id="categories-title">Explora por categoría</h2></div></div>
                <div class="catalog-category-strip">
                    <a role="link" aria-disabled="true" tabindex="-1"><span class="icon_grid-2x2" aria-hidden="true"></span> Novedades</a>
                    <a role="link" aria-disabled="true" tabindex="-1"><span class="icon_star_alt" aria-hidden="true"></span> Destacados</a>
                    <a role="link" aria-disabled="true" tabindex="-1"><span class="icon_tag_alt" aria-hidden="true"></span> Promociones</a>
                    <a role="link" aria-disabled="true" tabindex="-1"><span class="icon_box-checked" aria-hidden="true"></span> Disponibles</a>
                    <a href="{{ route('store-web.catalog') }}">Ver más <span class="arrow_right" aria-hidden="true"></span></a>
                </div>
            </section>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('store-web/js/home-preview.js') }}?v=real-next-9-3" defer></script>
@endsection
