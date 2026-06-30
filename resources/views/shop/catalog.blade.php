@extends('layouts.appShop')

@section('title')
    Tienda Web
@endsection

@section('logotipo')
    <a href="#"><img src="{{ asset('images/logo/'.$logotipoEmpresa) }}" alt="" width="80px" height="50px"></a>
@endsection

@section('logotipo2')
    <a href="#"><img src="{{ asset('images/logo/'.$logotipoEmpresa) }}" alt="" width="80px" height="50px"></a>
@endsection

@section('styles')
    <style>
        .pagination__option a.active {
            background: #ca1515;
            color: #ffffff;
            border-color: #ca1515;
        }

        .pagination-dots {
            display: inline-block;
            margin: 0 8px;
            color: #666;
        }

        .whatsapp-icon {
            color: #000000;
            font-size: 20px;
            line-height: 40px;
        }

        .whatsapp-icon:hover {
            color: #ffffff;
            font-size: 20px;
            line-height: 40px;
        }

        .mfp-bg {
            opacity: 0.8 !important;
        }

        .sidebar__all-products a.active,
        .subcategory-filter.active,
        .category-filter.active {
            color: #ca1515;
            font-weight: 600;
        }

        .price-input {
            display: block !important;
        }

        .price-input p {
            margin-bottom: 6px;
        }

        .price-values {
            display: flex;
            align-items: center;
            /* gap: 6px; */
            margin-bottom: 12px;
        }

        .price-values input {
            width: 75px !important;
            border: none;
            padding: 0;
            font-size: 14px;
            color: #111;
        }

        .price-separator {
            display: inline-block;
            margin: 0 2px;
        }

        .btn-filter-price {
            display: inline-block;
            padding: 5px 12px;
            border: 1px solid #ca1515;
            color: #111;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-filter-price:hover {
            background: #ca1515;
            color: #fff;
        }

        .social-icon-tiktok {
            width: 16px;
            height: 16px;
            object-fit: contain;
            vertical-align: middle;
        }

        /* Permite abrir el detalle al hacer clic en la imagen */
        .product__item__pic {
            position: relative;
        }

        .product-image-detail-link {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: block;
            z-index: 1;
            cursor: pointer;
        }

        /*
         * Mantiene los botones de ampliar imagen y WhatsApp
         * sobre el enlace transparente, sin alterar sus posiciones
         * ni las animaciones originales de la plantilla.
         */
        .product__item__pic .product__hover {
            z-index: 2;
        }

        .search-result-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 25px;
            padding: 12px 15px;
            border: 1px solid #eeeeee;
            background: #fafafa;
            font-size: 14px;
        }

        .btn-clear-catalog-search {
            border: 1px solid #ca1515;
            background: #ffffff;
            color: #ca1515;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-clear-catalog-search:hover {
            background: #ca1515;
            color: #ffffff;
        }
    </style>
@endsection

@section('activeShop')
    active
@endsection

@section('breadcrumb')
    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="{{ route('store-web.catalog') }}"><i class="fa fa-home"></i> Home</a>
                        <span>Shop</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="shop spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-3">
                    <div class="shop__sidebar">
                        <div class="sidebar__categories">
                            <div class="section-title">
                                <h4>Categorías</h4>
                            </div>

                            <div class="sidebar__all-products mb-3">
                                <a href="#" id="show-all-products" class="active">
                                    Todos los productos
                                </a>
                            </div>

                            <div class="categories__accordion">
                                <div class="accordion" id="categoriesAccordion">
                                    {{-- Se renderiza por AJAX --}}
                                </div>
                            </div>
                        </div>
                        @if( $showPricesCatalogEmpresa == "s" )
                        <div class="sidebar__filter">
                            <div class="section-title">
                                <h4>Filtrar por precio</h4>
                            </div>

                            <div class="filter-range-wrap">
                                <div class="price-range ui-slider ui-corner-all ui-slider-horizontal ui-widget ui-widget-content"
                                     data-min="0"
                                     data-max="{{ $maxPrice > 0 ? $maxPrice : 100 }}"></div>

                                <div class="range-slider">
                                    <div class="price-input">
                                        <p>Precio:</p>

                                        <div class="price-values">
                                            <input type="text" id="minamount" readonly>
                                            <span class="price-separator">-</span>
                                            <input type="text" id="maxamount" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <a href="#" id="btn-filter-price" class="btn-filter-price">Filtrar</a>
                        </div>
                        @endcan
                        <div class="sidebar__sizes">
                            <div class="section-title">
                                <h4>Filtrar por talla</h4>
                            </div>

                            <div class="size__list" id="sizes-container">
                                {{-- Se renderiza por AJAX --}}
                            </div>
                        </div>
                        <div class="sidebar__color">
                            <div class="section-title">
                                <h4>Filtrar por color</h4>
                            </div>

                            <div class="size__list color__list" id="colors-container">
                                {{-- Se renderiza por AJAX --}}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 col-md-9">
                    <div id="search-result-bar" class="search-result-bar" style="display: none;">
                        <span id="search-result-text"></span>

                        <button type="button" id="clear-catalog-search" class="btn-clear-catalog-search">
                            <i class="fa fa-times"></i> Limpiar búsqueda
                        </button>
                    </div>

                    <div class="row" id="products-container"></div>

                    <div class="row">
                        <div class="col-lg-12 text-center">
                            <div class="pagination__option" id="products-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Begin -->
    {{--<div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search here.....">
            </form>
        </div>
    </div>--}}
    <!-- Search End -->
@endsection

@section('description_footer')
    <p>{{ $descriptionFooterEmpresa }}</p>
@endsection

@section('footer__newslatter')
    <div class="footer__newslatter">
        <h6>REDES SOCIALES</h6>

        <div class="footer__social">
            @if(!empty($socialNetworksEmpresa['facebook']))
                <a href="{{ $socialNetworksEmpresa['facebook'] }}" target="_blank">
                    <i class="fa fa-facebook"></i>
                </a>
            @endif

            @if(!empty($socialNetworksEmpresa['twitter']))
                <a href="{{ $socialNetworksEmpresa['twitter'] }}" target="_blank">
                    <i class="fa fa-twitter"></i>
                </a>
            @endif

            @if(!empty($socialNetworksEmpresa['youtube']))
                <a href="{{ $socialNetworksEmpresa['youtube'] }}" target="_blank">
                    <i class="fa fa-youtube-play"></i>
                </a>
            @endif

            @if(!empty($socialNetworksEmpresa['instagram']))
                <a href="{{ $socialNetworksEmpresa['instagram'] }}" target="_blank">
                    <i class="fa fa-instagram"></i>
                </a>
            @endif

            @if(!empty($socialNetworksEmpresa['pinterest']))
                <a href="{{ $socialNetworksEmpresa['pinterest'] }}" target="_blank">
                    <i class="fa fa-pinterest"></i>
                </a>
            @endif

                @if(!empty($socialNetworksEmpresa['tiktok']))
                    <a href="{{ $socialNetworksEmpresa['tiktok'] }}" target="_blank">
                        <img src="{{ asset('images/logo/tiktok.png') }}"
                             alt="TikTok"
                             class="social-icon-tiktok">
                    </a>
                @endif
        </div>
    </div>
@endsection

@section('plugins')

@endsection

@section('scripts')
    <script>
        window.APP_SHOP = {
            search: @json($search ?? ''),
            URLS: {
                PRODUCTS: "{{ route('shop.products.data', ':page') }}",
                DEFAULT_IMAGE: "{{ asset('shop/img/no-image.png') }}",
                CATEGORIES: "{{ route('shop.categories.data') }}",
                SIZES: "{{ route('shop.sizes.data') }}",
                COLORS: "{{ route('shop.colors.data') }}",
                WHATSAPP: "https://wa.me/{{ $whatsappEmpresa }}",
                CAN_SHOW_PRICES: "{{ $showPricesCatalogEmpresa }}",
                CAN_SHOW_PRESENTATIONS: "{{ $showPresentationsEmpresa }}"
            }
        };
    </script>
    <script src="{{ asset('js/shop/catalog.js') }}?v={{ time() }}"></script>

@endsection