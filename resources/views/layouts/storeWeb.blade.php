@php
    $publicStoreWeb = request()->routeIs('publicCatalog.company', 'publicCatalog.branch', 'publicCatalog.product', 'publicCatalog.branchProduct');
    if ($publicStoreWeb) {
        $storeWebHomeUrl = route('publicCatalog.company', $company->catalog_slug);
        $storeWebCatalogUrl = $branch
            ? route('publicCatalog.branch', [$company->catalog_slug, $branch->catalog_slug])
            : $storeWebHomeUrl;
        $storeWebBusinessName = $company->trade_name ?: $company->business_name;
    } else {
        abort_unless(app()->environment('local') && config('store-web.preview') === true &&
            (($storeWebFixture ?? false) === true || ($storeWebAuthenticatedDataBridge ?? false) === true), 404);
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="@yield('description')">
    <meta name="keywords" content="@yield('keywords')">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @unless($publicStoreWeb)
    <meta name="robots" content="noindex, nofollow">
    <meta name="store-web-status" content="{{ ($storeWebFixture ?? false) ? 'FRONTEND FIXTURE / BACKEND CONTRACT PENDING' : 'LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION / SW-01 BACKEND SYNC OPEN' }}">
    @endunless
    <title>@unless($publicStoreWeb){{ ($storeWebFixture ?? false) ? '[FIXTURE FRONTEND]' : '[LOCAL QA — NON PRODUCTION]' }} @endunless @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('store-web/css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('store-web/css/style.css') }}" type="text/css">
</head>
<body class="catalog-page catalog-page--{{ $storeWebPage }}" data-store-web-fixture="{{ ($storeWebFixture ?? false) ? 'true' : 'false' }}">
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
