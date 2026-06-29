<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Ashion Template">
    <meta name="keywords" content="Ashion, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Laravel') }} | @yield('title')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/logo_dashboard.ico') }}">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cookie&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ asset('shop/css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/jquery-ui.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/slicknav.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('shop/css/style.css') }}" type="text/css">

    @yield('styles')
</head>
<body>
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>


    <!-- Offcanvas Menu Begin -->
    <div class="offcanvas-menu-overlay"></div>

    <div class="offcanvas-menu-wrapper">
        <div class="offcanvas__close">+</div>
        <ul class="offcanvas__widget">
            <li><span class="icon_search search-switch"></span></li>
            {{--<li><a href="#"><span class="icon_heart_alt"></span>
                    <div class="tip">2</div>
                </a></li>
            <li><a href="#"><span class="icon_bag_alt"></span>
                    <div class="tip">2</div>
                </a></li>--}}
        </ul>
        <div class="offcanvas__logo">
            @yield('logotipo')

        </div>
        <div id="mobile-menu-wrap"></div>
        <div class="offcanvas__auth">
            @can('access_dashboard')
                <a href="{{ route('dashboard.principal') }}">DASHBOARD</a>
            @endcan
        </div>
    </div>
    <!-- Offcanvas Menu End -->

    <!-- Header Section Begin -->
    <header class="header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-3 col-lg-2">
                    <div class="header__logo">
                        @yield('logotipo2')

                    </div>
                </div>
                <div class="col-xl-6 col-lg-7">
                    <nav class="header__menu">
                        <ul>
                            {{--<li class="@yield('activeHome')"><a href="{{ route('store-web.home') }}">Home</a></li>--}}
                            <li class="@yield('activeShop')"><a href="{{ route('store-web.catalog') }}">TIENDA</a></li>
                            {{--<li><a href="#">Dashboard</a></li>
                            <li><a href="#">Pages</a>
                                <ul class="dropdown">
                                    <li><a href="./product-details.html">Product Details</a></li>
                                    <li><a href="./shop-cart.html">Shop Cart</a></li>
                                    <li><a href="./checkout.html">Checkout</a></li>
                                    <li><a href="./blog-details.html">Blog Details</a></li>
                                </ul>
                            </li>--}}
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3">
                    <div class="header__right">
                        <div class="header__right__auth">
                            @can('access_dashboard')
                                <a href="{{ route('dashboard.principal') }}">DASHBOARD</a>
                            @endcan
                        </div>
                        <ul class="header__right__widget">
                            <li><span class="icon_search search-switch"></span></li>
                            {{--<li><a href="#"><span class="icon_heart_alt"></span>
                                    <div class="tip">2</div>
                                </a></li>
                            <li><a href="#"><span class="icon_bag_alt"></span>
                                    <div class="tip">2</div>
                                </a></li>--}}
                        </ul>
                    </div>
                </div>
            </div>
            <div class="canvas__open">
                <i class="fa fa-bars"></i>
            </div>
        </div>
    </header>
    <!-- Header Section End -->

    <!-- Breadcrumb Begin -->
    @yield('breadcrumb')
    {{--<div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="./index.html"><i class="fa fa-home"></i> Home</a>
                        <span>Shop</span>
                    </div>
                </div>
            </div>
        </div>
    </div>--}}
    <!-- Breadcrumb End -->

    <!-- Shop Section Begin -->
    @yield('content')
    <!-- Shop Section End -->

    <!-- Instagram Begin -->
    {{--<div class="instagram">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-1.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-2.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-3.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-4.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-5.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 p-0">
                    <div class="instagram__item set-bg" data-setbg="{{ asset('shop/img/instagram/insta-6.jpg') }}">
                        <div class="instagram__text">
                            <i class="fa fa-instagram"></i>
                            <a href="#">@ ashion_shop</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>--}}
    <!-- Instagram End -->

    <!-- Search Begin -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Buscar producto...">
            </form>
        </div>
    </div>
    <!-- Search End -->

    <!-- Footer Section Begin -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 offset-lg-2 col-md-6 col-sm-7">
                    <div class="footer__about">
                        <div class="footer__logo">
                            @yield('logotipo')
                        </div>
                        @yield('description_footer')
                    </div>
                </div>

                <div class="col-lg-4 col-md-8 col-sm-8">
                    @yield('footer__newslatter')
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                    <div class="footer__copyright__text">
                        <p>Copyright &copy; <script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart" aria-hidden="true"></i> by <a href="https://edesce.com" target="_blank">EDESCE</a></p>
                    </div>
                    <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Section End -->

    <!-- Js Plugins -->
    <script src="{{ asset('shop/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('shop/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('shop/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('shop/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('shop/js/mixitup.min.js') }}"></script>
    <script src="{{ asset('shop/js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('shop/js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('shop/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('shop/js/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('shop/js/main.js') }}"></script>

    <script>
        window.APP_SHOP_SEARCH = {
            URL: "{{ route('shop.product.search') }}",
            CATALOG_URL: "{{ route('store-web.catalog') }}"
        };
    </script>
    <script src="{{ asset('js/shop/search.js') }}?v={{ time() }}"></script>
    @yield('scripts')
</body>
</html>
