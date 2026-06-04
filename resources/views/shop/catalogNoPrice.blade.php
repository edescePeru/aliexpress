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
            gap: 6px;
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
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search here.....">
            </form>
        </div>
    </div>
    <!-- Search End -->
@endsection

@section('plugins')

@endsection

@section('scripts')
    <script>
        window.APP_SHOP = {
            URLS: {
                PRODUCTS: "{{ route('shop.products.data', ':page') }}",
                DEFAULT_IMAGE: "{{ asset('shop/img/no-image.png') }}",
                CATEGORIES: "{{ route('shop.categories.data') }}",
                SIZES: "{{ route('shop.sizes.data') }}",
                COLORS: "{{ route('shop.colors.data') }}",
                WHATSAPP: "https://wa.me/51921867035"
            }
        };
    </script>
    <script src="{{ asset('js/shop/catalogNoPrice.js') }}?v={{ time() }}"></script>

@endsection