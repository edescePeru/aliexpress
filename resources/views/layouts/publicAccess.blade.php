<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="0ti5-pM4JvRkJ2Gwg5tqmsBXep9iU_7hz5LHDCIwFEM">
    <title>@yield('title', 'Venti360')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/logo_dashboard.ico') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/venti-public.css') }}">
</head>
<body class="public-access-page">
<main class="public-access-shell">
    <section class="public-access-visual" aria-label="Venti360">
        <div class="public-access-visual__content">
            <img
                class="public-access-visual__image"
                src="{{ asset('landing/img/logo_venti.png') }}"
                alt="Ilustración de gestión empresarial Venti360"
            >
            <div class="public-access-brand" aria-hidden="true">
                <span class="public-access-brand__name">Venti360</span>
                <span class="public-access-brand__tagline">Gestión simple para tu operación.</span>
            </div>
        </div>
    </section>
    <section class="public-access-panel">
        <div class="public-access-panel__content">
            <div class="public-access-card">
                @yield('content')
            </div>
            <footer class="public-access-footer">
                @hasSection('footer-prefix')
                    <span class="public-access-footer__desktop-prefix">@yield('footer-prefix') ·</span>
                @endif
                <span class="public-access-footer__mobile-prefix">Venti360 ·</span>
                Un producto de
                <a href="https://www.edesce.com/" target="_blank" rel="noopener noreferrer">edesce.com</a>
            </footer>
        </div>
    </section>
</main>
<script src="{{ asset('admin/dist/js/venti-public.js') }}"></script>
</body>
</html>
