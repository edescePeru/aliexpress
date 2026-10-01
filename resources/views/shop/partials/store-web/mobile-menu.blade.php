<div class="catalog-mobile-menu" id="catalog-mobile-menu" aria-hidden="true">
        <div class="catalog-mobile-menu__overlay" data-catalog-menu-close></div>
        <div class="catalog-mobile-menu__panel" role="dialog" aria-modal="true" aria-label="Navegación del catálogo" tabindex="-1">
            <button class="catalog-mobile-menu__close" type="button" data-catalog-menu-close aria-label="Cerrar menú"><span aria-hidden="true">&times;</span></button>
            <a class="catalog-mobile-menu__logo" href="{{ route('store-web.home') }}"><img src="{{ $detailBranding['logo'] ?? $homeBranding['logo'] ?? asset('store-web/img/logo.png') }}" alt="{{ $detailBranding['label'] ?? $homeBranding['label'] ?? 'Catálogo del negocio' }}"></a>
            <nav class="catalog-mobile-menu__nav" aria-label="Navegación móvil">
                <a @if($storeWebPage === 'home')class="active" aria-current="page"@endif href="{{ route('store-web.home') }}">Inicio</a>
                <a @if($storeWebPage !== 'home')class="active" aria-current="page"@endif href="{{ route('store-web.catalog') }}">Productos</a>
                <a href="#catalog-contact">Contacto</a>
            </nav>
        </div>
    </div>