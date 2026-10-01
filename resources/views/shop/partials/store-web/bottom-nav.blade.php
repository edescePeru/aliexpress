<nav class="catalog-bottom-nav" aria-label="Navegación principal">
        <a class="catalog-bottom-nav__item{{ $storeWebPage === 'home' ? ' active' : '' }}" href="{{ route('store-web.home') }}"><span class="icon_house_alt" aria-hidden="true"></span><span class="catalog-bottom-nav__label">Inicio</span></a>
        <a class="catalog-bottom-nav__item{{ $storeWebPage === 'shop' ? ' active' : '' }}" href="{{ route('store-web.catalog') }}"><span class="icon_grid-2x2" aria-hidden="true"></span><span class="catalog-bottom-nav__label">Productos</span></a>
        <button class="catalog-bottom-nav__item" type="button" data-catalog-search-focus aria-label="Enfocar búsqueda de productos"><span class="icon_search" aria-hidden="true"></span><span class="catalog-bottom-nav__label">Buscar</span></button>
        <a class="catalog-bottom-nav__item" href="#catalog-contact"><span class="icon_chat_alt" aria-hidden="true"></span><span class="catalog-bottom-nav__label">Contacto</span></a>
    </nav>