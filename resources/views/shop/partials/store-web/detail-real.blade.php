{{-- CURRENT TEMPORARY BACKEND CONTRACT. No public description/variant resolver/related query. --}}
<main class="catalog-product-detail">
    <div class="catalog-container">
        <a class="catalog-product-detail__back" href="{{ route('store-web.catalog') }}"><span class="arrow_left" aria-hidden="true"></span> Volver a productos</a>
        <section class="catalog-product-detail__hero" aria-labelledby="product-title">
            @include('shop.partials.store-web.gallery')
            <div class="catalog-product-summary">
                <h1 id="product-title">{{ $detailName }}</h1>
                @if($detailShowPrice)
                <div class="catalog-product-summary__pricing"><strong>{{ trim($priceText ?? '') !== '' ? $priceText : 'Precio por consultar' }}</strong></div>
                @endif
                <span class="catalog-product-availability catalog-product-availability--{{ $detailAvailable ? 'available' : 'unavailable' }}"><span aria-hidden="true"></span> {{ $detailAvailability }}</span>
                {{-- Intro: BACKEND CONTRACT PENDING. material.description is not approved public copy. --}}
                @if(collect($detailGroups)->contains(function ($options) { return count($options) > 0; }))
                <div class="catalog-product-variations" aria-label="Opciones informativas del producto" data-attribute-contract="VISUAL ATTRIBUTE OPTIONS — VARIANT RESOLUTION PENDING">
                    @foreach($detailGroups as $group => $options)
                    @if(count($options))
                    <fieldset class="catalog-product-variation" aria-describedby="attribute-options-note">
                        <legend>{{ $group }}</legend>
                        <div>
                            @foreach($options as $option)
                            <input type="radio" name="detail-{{ $group }}" id="detail-{{ $group }}-{{ $loop->index }}" disabled><label for="detail-{{ $group }}-{{ $loop->index }}">{{ $option->name }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    @endif
                    @endforeach
                </div>
                <p class="catalog-product-summary__cta-note" id="attribute-options-note">Opciones informativas. Consulta la combinación con el negocio.</p>
                @endif
                @include('shop.partials.store-web.detail-cta', ['inline' => true])
                <p class="catalog-product-summary__cta-note">{{ $detailPhone ? 'El negocio responderá tu consulta directamente.' : 'Contacto por WhatsApp no disponible.' }}</p>
            </div>
        </section>
        <section class="catalog-product-detail-section" aria-labelledby="features-title">
            <div class="catalog-product-detail-section__heading"><span>Información</span><h2 id="features-title">Características</h2></div>
            <dl class="catalog-product-features">
                @if($detailBrand)<div><dt>Marca</dt><dd>{{ $detailBrand }}</dd></div>@endif
                @if($detailCode)<div><dt>Código</dt><dd>{{ $detailCode }}</dd></div>@endif
                <div><dt>Estado</dt><dd>{{ $detailAvailability }}</dd></div>
            </dl>
        </section>
        {{-- Description, generic features, presentations and related slots: BACKEND CONTRACT PENDING.
             The approved sections remain in fixture mode; no internal description or mock is published here. --}}
    </div>
</main>
