{{-- BIND REAL DATA: public company logo/name after SW-01/SW-07. BACKEND CONTRACT PENDING: branding. Home GET search uses the existing catalog contract. --}}
<header class="header catalog-header">
        <div class="container-fluid catalog-container">
            <div class="row align-items-center catalog-header__main">
                <div class="col-xl-3 col-lg-2">
                    <div class="header__logo">
                        <a href="{{ route('store-web.home') }}"><img src="{{ $detailBranding['logo'] ?? $homeBranding['logo'] ?? asset('store-web/img/logo.png') }}" alt="{{ $detailBranding['label'] ?? $homeBranding['label'] ?? 'Catálogo del negocio' }}"></a>
                        <span class="catalog-header__business-name">{{ $detailBranding['label'] ?? $homeBranding['label'] ?? 'Catálogo del negocio' }}</span>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-7">
                    <nav class="header__menu"><ul>
                        <li @if($storeWebPage === 'home')class="active"@endif><a href="{{ route('store-web.home') }}">Inicio</a></li>
                        <li @if($storeWebPage !== 'home')class="active"@endif><a href="{{ route('store-web.catalog') }}">Productos</a></li>
                        <li><a href="#catalog-contact">Contacto</a></li>
                    </ul></nav>
                </div>
                <div class="col-lg-3">
                    <div class="header__right">
                        <button class="catalog-header__search-trigger" type="button" data-catalog-search-focus aria-label="Enfocar búsqueda de productos"><span class="icon_search" aria-hidden="true"></span></button>
                    </div>
                </div>
            </div>
            <button class="canvas__open catalog-mobile-menu__trigger" type="button" data-catalog-menu-open aria-controls="catalog-mobile-menu" aria-expanded="false" aria-label="Abrir menú"><i class="fa fa-bars" aria-hidden="true"></i></button>
            <div class="catalog-search">
                <form class="catalog-search__field catalog-search__form" role="search" @if($storeWebPage === 'shop')action="{{ route('store-web.catalog') }}" method="get" @endif @if($storeWebPage === 'home')action="{{ route('store-web.catalog') }}" method="get" data-home-catalog-search @endif>
                    <label class="catalog-visually-hidden" for="catalog-search-input">Buscar productos</label>
                    <span class="icon_search" aria-hidden="true"></span>
                    <input class="catalog-search__input" id="catalog-search-input" @if($storeWebPage === 'shop')name="search"@endif @if($storeWebPage === 'home')name="search"@endif type="search" placeholder="Buscar productos..." autocomplete="off">
                    <button class="catalog-search__clear" type="button" aria-label="Limpiar búsqueda" hidden><span aria-hidden="true">&times;</span></button>
                </form>
            </div>
        </div>
    </header>