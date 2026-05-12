<!-- Portada -->
<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ env('APP_NAME') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="0ti5-pM4JvRkJ2Gwg5tqmsBXep9iU_7hz5LHDCIwFEM" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/icono_logo.ico') }}">

    <!-- CSS here -->
    <link rel="stylesheet" href="{{ asset('landing/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/slicknav.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/style.css') }}">
</head>

<body>
<!-- Preloader Start -->
<div id="preloader-active">
    <div class="preloader d-flex align-items-center justify-content-center">
        <div class="preloader-inner position-relative">
            <div class="preloader-circle"></div>
            <div class="preloader-img pere-text">
                <img src="{{ asset('landing/img/logo/logo_dashboard.png') }}" alt="">
            </div>
        </div>
    </div>
</div>
<!-- Preloader Start -->
<header>
    <!-- Header Start -->
    <div class="header-area header-transparent">
        <div class="main-header ">
            <div class="header-top d-none d-lg-block">
                <div class="container-fluid">
                    <div class="col-xl-12">
                        <div class="row d-flex justify-content-between align-items-center">
                            <div class="header-info-left">
                                <ul>
                                    <li>+51 998396337</li>
                                    <li>+51 992555482</li>
                                    <li>jmauricio@sermeind.com</li>
                                    <li>Lun - Vie: 09:00 - 18:00</li>
                                </ul>
                            </div>
                            <div class="header-info-right">
                                <ul class="header-social">
                                    <li><a href="https://www.facebook.com/sermeind.fabricaciones.industriales/?ref=page_internal" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-bottom  header-sticky">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <!-- Logo -->
                        <div class="col-xl-2 col-lg-2 col-md-2">
                            <div class="logo">
                                <!-- logo-1 -->
                                <a href="{{ url('/') }}" class="big-logo"><img src="{{ asset('admin/dist/img/logo_sermeind.png') }}" alt=""></a>
                                <!-- logo-2 -->
                                <a href="{{ url('/') }}" class="small-logo"><img src="{{ asset('admin/dist/img/logo_dashboard.png') }}" alt=""></a>
                            </div>
                        </div>
                        <div class="col-xl-10 col-lg-10 col-md-10">
                            <!-- Main-menu -->
                            <div class="main-menu f-right d-none d-lg-block">
                                <nav>
                                    <ul id="navigation">
                                        <li><a href="{{ url('/') }}">Inicio</a></li>
                                        <li><a href="{{ route('landing.about') }}">Nosotros</a></li>
                                        <li><a href="{{ route('landing.service') }}">Servicios</a></li>
                                        @auth()
                                            <li><a href="#">Pages</a>
                                                <ul class="submenu">
                                                    <li><a href="#">Element</a></li>
                                                    <li><a href="#">Projects Details</a></li>
                                                    <li><a href="#">Services Details</a></li>
                                                </ul>
                                            </li>
                                        @endauth
                                        <li><a href="{{ route('landing.contact') }}">Contacto</a></li>
                                        @guest
                                            {{--<li><a href="{{ route('register') }}" > Registro</a></li>--}}
                                            <li><a href="{{ route('login') }}" > Iniciar sesión</a></li>
                                            
                                        @else
                                            <li><a href="{{ route('home') }}"> Mi cuenta</a></li>
                                            <li><a href="#">{{ Auth::user()->name }}</a>
                                                <ul class="submenu">
                                                    <li>
                                                        <a href="{{ route('logout') }}"
                                                            onclick="event.preventDefault();
                                                            document.getElementById('logout-form').submit();">
                                                            <i class="fa fa-sign-out"></i>
                                                            {{ __('Cerrar Sesión') }}
                                                        </a>
                                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                                            @csrf
                                                        </form>
                                                    </li>
                                                </ul>
                                            </li>
                                        @endguest
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- Mobile Menu -->
                        <div class="col-12">
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header End -->
</header>
<main>

    <!-- slider Area Start-->
    <div class="slider-area ">
        <div class="">
            <div class="single-slider  hero-overly slider-height d-flex align-items-center" data-background="{{ asset('landing/img/hero/h1_hero1.jpg') }}">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-11">
                            <div class="hero__caption">
                            
                                <h1 data-animation="fadeInUp" data-delay=".5s">SERMEIND</h1>
                            
                                <div class="stock-text" data-animation="fadeInUp" data-delay=".8s">
                                    <!--<h2>Fabricaciones Industriales S.A.C.</h2>-->
                                    <h2>Fabricaciones Industriales</h2>
                                </div>

                            <!--
                                <div class="hero-text2 mt-110" data-animation="fadeInUp" data-delay=".9s">
                                    <span><a href="#l">Our Services</a></span>
                                </div>
                            -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- slider Area End-->
    <!-- Services Area Start -->
    <div class="services-area1 section-padding30">
        <div class="container">
            <!-- section tittle -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-tittle mb-55">
                        <div class="front-text">
                            <h2 class="">Nuestros Servicios</h2>
                        </div>
                    <!--
                        <span class="back-text">Services</span>
                    -->
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-4 col-lg-4 col-md-6 col-xs-12">
                    <div class="single-service-cap mb-30">
                        <div class="service-img">
                            <img src="{{ asset('landing/img/service/servicess1.png') }}" alt="">
                        </div>
                        <div class="service-cap">
                            <h4><a href="#">Mantenimiento y reparación general de Maquinarias</a></h4>
                        </div>
                        <div class="service-icon">
                            <img src="{{ asset('landing/img/icon/services_icon1.png') }}" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-xs-12">
                    <div class="single-service-cap mb-30">
                        <div class="service-img">
                            <img src="{{ asset('landing/img/service/servicess2.png') }}" alt="">
                        </div>
                        <div class="service-cap">
                            <h4><a href="#">Asesoramiento Técnico de maquinarias</a></h4>
                        </div>
                        <div class="service-icon">
                            <img src="{{ asset('landing/img/icon/services_icon1.png') }}" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-xs-12">
                    <div class="single-service-cap mb-30">
                        <div class="service-img">
                            <img src="{{ asset('landing/img/service/servicess3.png') }}" alt="">
                        </div>
                        <div class="service-cap">
                            <h4><a href="#">Diseño y cableado eléctrico de plantas industriales.</a></h4>
                        </div>
                        <div class="service-icon">
                            <img src="{{ asset('landing/img/icon/services_icon1.png') }}" alt="">
                        </div>
                    </div>
                </div>
                <a href="{{ route('landing.service') }}" class="btn red-btn2">Ver más</a>
            </div>
        </div>
    </div>
    <!-- Services Area End -->

    <!-- contact with us Start -->
    <section class="contact-with-area" data-background="{{ asset('landing/img/gallery/section-bg2.jpg') }}">
        <div class="container">
            <div class="row">
                <div class="col-xl-8 col-lg-9 offset-xl-1 offset-lg-1">
                    <div class="contact-us-caption">
                        <div class="team-info mb-30 pt-45">
                            <!-- Section Tittle -->
                            <div class="section-tittle section-tittle4">
                                <div class="front-text">
                                    <h2 class="">¿Quiénes Somos?</h2>
                                </div>
                                <span class="back-text">Nosotros</span>
                            </div>
                            <p><b  style="color: #fff;">SERMEIND FABRICACIONES INSUTRIALES S.A.C.</b> es una empresa con amplia experiencia en soldadura, fabricación y mantenimiento de todo tipo de maquinarias para la industria Alimentaria. Cabe indicar que nuestro equipo de trabajo está conformado por un grupo multidisciplinario de profesionales con amplia experiencia</p>
                            <a href="{{ route('landing.about') }}" class="white-btn">Leer más</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- contact with us End-->
    <!-- CountDown Area Start -->
    <div class="count-area">
        <div class="container">
            <div class="count-wrapper count-bg" data-background="{{ asset('landing/img/gallery/section-bg3.jpg') }}">
                <div class="row justify-content-center" >
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="count-clients">
                            <div class="single-counter">
                                <div class="count-number">
                                    <span class="counter">73</span>
                                </div>
                                <div class="count-text">
                                    <p>Clientes</p>
                                    <h5>Satisfechos</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="count-clients">
                            <div class="single-counter">
                                <div class="count-number">
                                    <span class="counter">250</span>
                                </div>
                                <div class="count-text">
                                    <p>Proyectos</p>
                                    <h5>Concluidos</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="count-clients">
                            <div class="single-counter">
                                <div class="count-number">
                                    <span class="counter">15</span>
                                </div>
                                <div class="count-text">
                                    <p>Proyectos en</p>
                                    <h5>Desarrollo</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CountDown Area End -->
    <!-- Team Start -->
    <div class="team-area section-padding30">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <!-- Section Tittle -->
                    <div class="section-tittle section-tittle5 mb-50">
                        <div class="front-text">
                            <h2 class="">Nuestro equipo</h2>
                        </div>
                    <!--
                        <span class="back-text">exparts</span>
                    -->
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- single Tem -->
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-">
                    <div class="single-team mb-30">
                        <div class="team-img">
                            <img src="{{ asset('landing/img/gallery/team1.png') }}" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-">
                    <div class="single-team mb-30">
                        <div class="team-img">
                            <img src="{{ asset('landing/img/gallery/team2.png') }}" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-">
                    <div class="single-team mb-30">
                        <div class="team-img">
                            <img src="{{ asset('landing/img/gallery/team3.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Team End -->


</main>
<footer>
    <!-- Footer Start-->
    <div class="footer-main">
        <div class="footer-area footer-padding">
            <div class="container">
                <div class="row  justify-content-between">
                    <div class="col-lg-4 col-md-4 col-sm-8">
                        <div class="single-footer-caption mb-30">
                            <!-- logo -->
                            <div class="footer-logo">
                                <a href="#"><img src="{{ asset('landing/img/logo/logo_sermeind.png') }}" alt=""></a>
                            </div>
                            <div class="footer-tittle">
                                <div class="footer-pera" style="text-align: justify;">
                                    <p class="info1" >Empresa con amplia experiencia en soldadura, fabricación y mantenimiento de todo tipo de maquinarias para la industria Alimentaria.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-7">
                        <div class="single-footer-caption mb-50">
                            <div class="footer-tittle">
                                <h4>Contact</h4>
                                <div class="footer-pera">
                                    <p class="info1">Distrito Moche - La Libertad - Peru</p>
                                </div>
                                <ul>
                                    <li><a>Cel: +51 998396337</a></li>
                                    <li><a>Cel: +51 992555482</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-8">
                        <div class="single-footer-caption mb-50">
                            <!-- Form -->
                            <div class="footer-form">
                                <div id="mc_embed_signup">
                                    <!--
                                    <form target="_blank" action="https://spondonit.us12.list-manage.com/subscribe/post?u=1462626880ade1ac87bd9c93a&amp;id=92a4423d01" method="get" class="subscribe_form relative mail_part" novalidate="true">
                                        <input type="email" name="EMAIL" id="newsletter-form-email" placeholder=" Email Address " class="placeholder hide-on-focus" onfocus="this.placeholder = ''" onblur="this.placeholder = ' Email Address '">

                                        <div class="form-icon">
                                            <button type="submit" name="submit" id="newsletter-submit" class="email_icon newsletter-submit button-contactForm">
                                                REGÍSTRATE
                                            </button>
                                        </div>
                                        -->
                                        <div class="mt-10 info"></div>
                                    </form>
                                </div>
                            </div>
                            <!-- Map -->
                            <div class="map-footer">
                                <img src="{{ asset('landing/img/gallery/map-footer.png') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Copy-Right -->
                <div class="row align-items-center">
                    <div class="col-xl-12 ">
                        <div class="footer-copy-right">
                            <p><!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                                Copyright &copy;<script>document.write(new Date().getFullYear());</script> Todos los derechos reservados por <a href="https://www.edesce.com/" target="_blank">EDESCE</a>
                                <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End-->
</footer> 

<!-- JS here -->

<!-- All JS Custom Plugins Link Here here -->
<script src="{{ asset('landing/js/vendor/modernizr-3.5.0.min.js') }}"></script>
<!-- Jquery, Popper, Bootstrap -->
<script src="{{ asset('landing/js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('landing/js/popper.min.js') }}"></script>
<script src="{{ asset('landing/js/bootstrap.min.js') }}"></script>
<!-- Jquery Mobile Menu -->
<script src="{{ asset('landing/js/jquery.slicknav.min.js') }}"></script>

<!-- Jquery Slick , Owl-Carousel Plugins -->
<script src="{{ asset('landing/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('landing/js/slick.min.js') }}"></script>
<!-- Date Picker -->
<script src="{{ asset('landing/js/gijgo.min.js') }}"></script>
<!-- One Page, Animated-HeadLin -->
<script src="{{ asset('landing/js/wow.min.js') }}"></script>
<script src="{{ asset('landing/js/animated.headline.js') }}"></script>
<script src="{{ asset('landing/js/jquery.magnific-popup.js') }}"></script>

<!-- Scrollup, nice-select, sticky -->
<script src="{{ asset('landing/js/jquery.scrollUp.min.js') }}"></script>
<script src="{{ asset('landing/js/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('landing/js/jquery.sticky.js') }}"></script>

<!-- counter , waypoint -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
<script src="{{ asset('landing/js/jquery.counterup.min.js') }}"></script>

<!-- contact js -->
<script src="{{ asset('landing/js/contact.js') }}"></script>
<script src="{{ asset('landing/js/jquery.form.js') }}"></script>
<script src="{{ asset('landing/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('landing/js/mail-script.js') }}"></script>
<script src="{{ asset('landing/js/jquery.ajaxchimp.min.js') }}"></script>

<!-- Jquery Plugins, main Jquery -->
<script src="{{ asset('landing/js/plugins.js') }}"></script>
<script src="{{ asset('landing/js/main.js') }}"></script>

</body>
</html>