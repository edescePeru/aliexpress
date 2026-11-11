<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ $company->trade_name ?: $company->business_name }}</title>
    <link rel="stylesheet" href="{{ asset('store-web/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('store-web/css/style.css') }}">
</head>
<body class="catalog-page catalog-page--@yield('pageType', 'shop')">
    <header class="bg-white border-bottom py-3">
        <div class="catalog-container">
            <a class="font-weight-bold" href="{{ route('publicCatalog.company', $company->catalog_slug) }}">{{ $company->trade_name ?: $company->business_name }}</a>
            @if($branch)<span class="text-muted ml-2">{{ $branch->name }}</span>@endif
        </div>
    </header>
    @yield('content')
</body>
</html>
