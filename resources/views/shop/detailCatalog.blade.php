{{-- REAL NEXT 9.5: authenticated local temporary contract; anonymous fixture remains isolated. --}}
@extends('layouts.storeWeb', ['storeWebPage' => 'detail'])
@php
    if (!($storeWebFixture ?? false)) require resource_path('store-web/detail.php');
@endphp
@section('title', ($detailName ?? 'Producto de ejemplo') . ' | Catálogo')
@section('description', 'Detalle público del producto')
@section('keywords', 'catálogo, producto')
@section('content')
@if(!($storeWebFixture ?? false))
    @include('shop.partials.store-web.detail-real')
@else
<main class="catalog-product-detail">
        <div class="catalog-container">
            <a class="catalog-product-detail__back" href="{{ route('store-web.catalog') }}"><span class="arrow_left" aria-hidden="true"></span> Volver a productos</a>

            <section class="catalog-product-detail__hero" aria-labelledby="product-title">
                @include('shop.partials.store-web.gallery')


                {{-- BIND REAL DATA: product summary. BACKEND CONTRACT NEEDED: variant selection/public description. --}}
<div class="catalog-product-summary">
                    <span class="catalog-product-summary__badge">Oferta</span>
                    <h1 id="product-title">Producto de ejemplo</h1>
                    <div class="catalog-product-summary__pricing">
                        <strong>S/ 129.90</strong>
                        <del>S/ 159.90</del>
                    </div>
                    <span class="catalog-product-availability catalog-product-availability--available"><span aria-hidden="true"></span> Disponible</span>
                    <p class="catalog-product-summary__intro">Una descripción breve del producto que permite conocer rápidamente sus principales beneficios y presentación.</p>

                    <div class="catalog-product-variations" aria-label="Opciones del producto">
                        <fieldset class="catalog-product-variation">
                            <legend>Presentación</legend>
                            <div>
                                <input type="radio" name="presentation" id="presentation-1" checked><label for="presentation-1">Opción 1</label>
                                <input type="radio" name="presentation" id="presentation-2"><label for="presentation-2">Opción 2</label>
                                <input type="radio" name="presentation" id="presentation-3"><label for="presentation-3">Opción 3</label>
                            </div>
                        </fieldset>
                        <fieldset class="catalog-product-variation">
                            <legend>Otro atributo</legend>
                            <div>
                                <input type="radio" name="attribute" id="attribute-a" checked><label for="attribute-a">Variante A</label>
                                <input type="radio" name="attribute" id="attribute-b"><label for="attribute-b">Variante B</label>
                            </div>
                        </fieldset>
                    </div>

                    <button class="catalog-whatsapp-cta catalog-whatsapp-cta--inline" type="button" data-product-name="Producto de ejemplo" data-product-url="">
                        <i class="fa fa-whatsapp" aria-hidden="true"></i><span>Consultar por WhatsApp</span>
                    </button>
                    <p class="catalog-product-summary__cta-note">El negocio responderá tu consulta directamente.</p>
                </div>
            </section>

            <section class="catalog-product-detail-section" aria-labelledby="features-title">
                <div class="catalog-product-detail-section__heading"><span>Información</span><h2 id="features-title">Características</h2></div>
                <dl class="catalog-product-features">
                    <div><dt>Marca</dt><dd>Ejemplo</dd></div>
                    <div><dt>Código</dt><dd>PROD-001</dd></div>
                    <div><dt>Presentación</dt><dd>Unidad</dd></div>
                    <div><dt>Estado</dt><dd>Disponible</dd></div>
                </dl>
            </section>

            <section class="catalog-product-detail-section" aria-labelledby="description-title">
                <div class="catalog-product-detail-section__heading"><span>Acerca del producto</span><h2 id="description-title">Descripción</h2></div>
                <div class="catalog-product-description">
                    <p>Este contenido temporal permite presentar información más completa del producto de forma clara y directa. Aquí podrán explicarse sus usos, beneficios, materiales, recomendaciones o cualquier detalle relevante para la decisión del cliente.</p>
                    <p>La estructura está preparada para productos de distintas industrias sin depender de atributos exclusivos de ropa, tecnología o alimentos.</p>
                </div>
            </section>

            <section class="catalog-product-detail-section catalog-product-detail-section--related" aria-labelledby="related-title">
{{-- BACKEND CONTRACT NEEDED: related products. --}}
                <div class="catalog-product-detail-section__heading"><span>También puede interesarte</span><h2 id="related-title">Productos relacionados</h2></div>
                <div class="catalog-related-list">
                    <a class="catalog-related-card" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}"><div><img src="{{ asset('store-web/img/product/related/rp-1.jpg') }}" alt="Producto relacionado uno"></div><h3>Producto relacionado uno</h3><p>S/ 59.90</p></a>
                    <a class="catalog-related-card" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}"><div><img src="{{ asset('store-web/img/product/related/rp-2.jpg') }}" alt="Producto relacionado dos"></div><h3>Producto relacionado dos</h3><p>S/ 49.90</p></a>
                    <a class="catalog-related-card" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}"><div><img src="{{ asset('store-web/img/product/related/rp-3.jpg') }}" alt="Producto relacionado tres"></div><h3>Producto relacionado tres</h3><p>S/ 69.90</p></a>
                    <a class="catalog-related-card" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}"><div><img src="{{ asset('store-web/img/product/related/rp-4.jpg') }}" alt="Producto relacionado cuatro"></div><h3>Producto relacionado cuatro</h3><p>S/ 39.90</p></a>
                </div>
            </section>
        </div>
    </main>
@endif
@endsection
@section('bottom')
<div class="catalog-product-sticky-cta">
    @if(!($storeWebFixture ?? false))
        @include('shop.partials.store-web.detail-cta', ['inline' => false])
    @else
        <button class="catalog-whatsapp-cta" type="button" data-product-name="Producto de ejemplo" data-product-url="">
            <i class="fa fa-whatsapp" aria-hidden="true"></i><span>Consultar por WhatsApp</span>
        </button>
    @endif
    </div>
@endsection

@section('scripts')
@if(!($storeWebFixture ?? false))
<script type="application/json" id="store-web-detail-contract">@json($detailClientContract)</script>
<script src="{{ asset('store-web/js/product-detail.js') }}?v=real-next-9-5" defer></script>
@endif
@endsection
