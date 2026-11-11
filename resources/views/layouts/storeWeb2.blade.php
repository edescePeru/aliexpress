{{-- Frozen shell: fixture rendering requires the explicit local preview bridge.
     Do not derive this flag from a request/query/session. This is not a SW-01 resolver.
     Production stays closed while SW-01 is unresolved. --}}
@php
    abort_unless(app()->environment('local') && config('store-web.preview') === true &&
        (($storeWebFixture ?? false) === true || ($storeWebAuthenticatedDataBridge ?? false) === true), 404);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="@yield('description')">
    <meta name="keywords" content="@yield('keywords')">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="store-web-status" content="{{ ($storeWebFixture ?? false) ? 'FRONTEND FIXTURE / BACKEND CONTRACT PENDING' : 'LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION / SW-01 BACKEND SYNC OPEN' }}">
    <title>{{ ($storeWebFixture ?? false) ? '[FIXTURE FRONTEND]' : '[LOCAL QA — NON PRODUCTION]' }} @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('store-web/css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/style.css') }}" type="text/css">
</head>
<body class="catalog-page catalog-page--{{ $storeWebPage }}" data-store-web-fixture="{{ ($storeWebFixture ?? false) ? 'true' : 'false' }}">
    <!-- LOCAL QA ONLY. Authenticated data uses CURRENT TEMPORARY BACKEND CONTRACT; SW-01 remains OPEN. -->
    @include('shop.partials.store-web.mobile-menu')
    @include('shop.partials.store-web.header')
    @yield('content')
    @yield('overlays')
    @include('shop.partials.store-web.footer')
    @if($storeWebPage !== 'detail')
        @include('shop.partials.store-web.bottom-nav')
    @endif
    @yield('bottom')
    <script src="{{ asset('store-web/js/catalog-main.js') }}?v=real-next-9-1" defer></script>
    @yield('scripts')
</body>
</html>
