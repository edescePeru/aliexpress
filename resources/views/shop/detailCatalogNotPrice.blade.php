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
                        <a href="{{ route('store-web.tienda') }}">
                            <i class="fa fa-home"></i> Home
                        </a>

                        @if($material->category)
                            <a href="#">
                                {{ $material->category->name ?? $material->category->description }}
                            </a>
                        @endif

                        <span>{{ $material->full_name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="product-details spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="product__details__pic">
                        <div class="product__details__pic__left product__thumb nice-scroll">
                            @foreach($images as $index => $image)
                                <a class="pt {{ $index === 0 ? 'active' : '' }}" href="#product-{{ $index + 1 }}">
                                    <img src="{{ $image['thumb'] }}" alt="{{ $image['label'] }}">
                                </a>
                            @endforeach
                        </div>
                        <div class="product__details__slider__content">
                            <div class="product__details__pic__slider owl-carousel">
                                @foreach($images as $index => $image)
                                    <img data-hash="product-{{ $index + 1 }}"
                                         class="product__big__img"
                                         src="{{ $image['image'] }}"
                                         alt="{{ $image['label'] }}">
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="product__details__text">
                        <h3>
                            {{ $material->full_name }}

                            @if($material->brand)
                                <span>Marca: {{ $material->brand->name }}</span>
                            @endif
                        </h3>

                        <div class="product__details__price">
                            {{ $priceText }}
                        </div>

                        <p>
                            Producto disponible en catálogo. Consulta por WhatsApp para confirmar disponibilidad, presentación y precio final.
                        </p>

                        <div class="product__details__widget">
                            <ul>
                                <li>
                                    <span>Disponibilidad:</span>
                                    <p>{{ $stockAvailable > 0 ? 'En stock' : 'Sin stock' }}</p>
                                </li>

                                <li>
                                    <span>Colores disponibles:</span>

                                    @if($colors->count())
                                        <div class="color__checkbox">
                                            @foreach($colors as $color)
                                                <label title="{{ $color->name }}">
                                                    <input type="radio" name="color__radio">
                                                    <span class="checkmark"
                                                          style="background: {{ $color->code ?: '#cccccc' }};border: 1px solid #333;"></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <p>Único</p>
                                    @endif
                                </li>

                                <li>
                                    <span>Tallas disponibles:</span>

                                    @if($sizes->count())
                                        <div class="size__btn">
                                            @foreach($sizes as $index => $size)
                                                <label class="{{ $index === 0 ? 'active' : '' }}">
                                                    <input type="radio" name="size_radio">
                                                    {{ $size->short_name ?: $size->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <p>Única</p>
                                    @endif
                                </li>
                            </ul>
                        </div>

                        @if($material->presentations->count())
                            <div class="product-presentations mt-4">
                                <h5>Presentaciones disponibles</h5>

                                <table class="table table-bordered table-sm mt-3">
                                    <thead>
                                    <tr>
                                        <th>Presentación</th>
                                        <th>Precio</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($material->presentations as $presentation)
                                        <tr>
                                            <td>
                                                <strong>{{ $presentation->quantity }} unidades</strong>
                                                <br>
                                                <small>Equivale a {{ $presentation->quantity }} unidades</small>
                                            </td>
                                            <td>S/. {{ number_format($presentation->price, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                {{--<div class="col-lg-12">
                    <div class="product__details__tab">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab">Description</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabs-2" role="tab">Specification</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabs-3" role="tab">Reviews ( 2 )</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tabs-1" role="tabpanel">
                                <h6>Description</h6>
                                <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed
                                    quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt loret.
                                    Neque porro lorem quisquam est, qui dolorem ipsum quia dolor si. Nemo enim ipsam
                                    voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed quia ipsu
                                    consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Nulla
                                    consequat massa quis enim.</p>
                                <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget
                                    dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes,
                                    nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium
                                    quis, sem.</p>
                            </div>
                            <div class="tab-pane" id="tabs-2" role="tabpanel">
                                <h6>Specification</h6>
                                <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed
                                    quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt loret.
                                    Neque porro lorem quisquam est, qui dolorem ipsum quia dolor si. Nemo enim ipsam
                                    voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed quia ipsu
                                    consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Nulla
                                    consequat massa quis enim.</p>
                                <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget
                                    dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes,
                                    nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium
                                    quis, sem.</p>
                            </div>
                            <div class="tab-pane" id="tabs-3" role="tabpanel">
                                <h6>Reviews ( 2 )</h6>
                                <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed
                                    quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt loret.
                                    Neque porro lorem quisquam est, qui dolorem ipsum quia dolor si. Nemo enim ipsam
                                    voluptatem quia voluptas sit aspernatur aut odit aut loret fugit, sed quia ipsu
                                    consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Nulla
                                    consequat massa quis enim.</p>
                                <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget
                                    dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes,
                                    nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium
                                    quis, sem.</p>
                            </div>
                        </div>
                    </div>
                </div>--}}
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
                WHATSAPP: "https://wa.me/{{ $whatsappEmpresa }}"
            }
        };
    </script>
    {{--<script src="{{ asset('js/shop/catalog.js') }}?v={{ time() }}"></script>--}}

@endsection