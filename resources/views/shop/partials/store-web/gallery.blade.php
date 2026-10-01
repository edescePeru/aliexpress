@if(!($storeWebFixture ?? false))
<div class="catalog-product-gallery">
    <div class="catalog-product-gallery__stage">
        <img id="catalog-product-main-image" src="{{ $detailImages[0]['image'] }}" alt="{{ $detailImages[0]['label'] }}">
        <span class="catalog-product-gallery__counter" aria-live="polite">1 / {{ count($detailImages) }}</span>
    </div>
    <div class="catalog-product-gallery__thumbs" aria-label="Imágenes del producto">
        @foreach($detailImages as $image)
        <button class="catalog-product-gallery__thumb{{ $loop->first ? ' active' : '' }}" type="button" data-gallery-image="{{ $image['image'] }}" data-gallery-alt="{{ $image['label'] }}" aria-label="Mostrar imagen {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><img src="{{ $image['thumb'] }}" alt=""></button>
        @endforeach
    </div>
</div>
@else
{{-- BIND REAL DATA: images[] after SW-01; current images are frontend fixtures. No variant resolver. --}}
<div class="catalog-product-gallery">
                    <div class="catalog-product-gallery__stage">
                        <img id="catalog-product-main-image" src="{{ asset('store-web/img/product/details/product-1.jpg') }}" alt="Vista principal del producto de ejemplo">
                        <span class="catalog-product-gallery__counter" aria-live="polite">1 / 4</span>
                    </div>
                    <div class="catalog-product-gallery__thumbs" aria-label="Imágenes del producto">
                        <button class="catalog-product-gallery__thumb active" type="button" data-gallery-image="{{ asset('store-web/img/product/details/product-1.jpg') }}" data-gallery-alt="Vista frontal del producto" aria-label="Mostrar imagen 1" aria-pressed="true"><img src="{{ asset('store-web/img/product/details/thumb-1.jpg') }}" alt=""></button>
                        <button class="catalog-product-gallery__thumb" type="button" data-gallery-image="{{ asset('store-web/img/product/details/product-3.jpg') }}" data-gallery-alt="Vista alternativa del producto" aria-label="Mostrar imagen 2" aria-pressed="false"><img src="{{ asset('store-web/img/product/details/thumb-2.jpg') }}" alt=""></button>
                        <button class="catalog-product-gallery__thumb" type="button" data-gallery-image="{{ asset('store-web/img/product/details/product-2.jpg') }}" data-gallery-alt="Detalle del producto" aria-label="Mostrar imagen 3" aria-pressed="false"><img src="{{ asset('store-web/img/product/details/thumb-3.jpg') }}" alt=""></button>
                        <button class="catalog-product-gallery__thumb" type="button" data-gallery-image="{{ asset('store-web/img/product/details/product-4.jpg') }}" data-gallery-alt="Otra perspectiva del producto" aria-label="Mostrar imagen 4" aria-pressed="false"><img src="{{ asset('store-web/img/product/details/thumb-4.jpg') }}" alt=""></button>
                    </div>
                </div>
@endif
