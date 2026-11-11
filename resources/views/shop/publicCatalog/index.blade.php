@extends('layouts.storeWeb', ['storeWebPage' => 'shop'])
@section('title', 'Catálogo')
@section('content')
@php
    $catalogListUrl = $branch
        ? route('publicCatalog.branch', [$company->catalog_slug, $branch->catalog_slug])
        : route('publicCatalog.company', $company->catalog_slug);
@endphp
<main class="catalog-shop">
    <div class="catalog-container">
        {{--<nav class="catalog-shop-categories" aria-label="Secciones del catálogo">
            <a class="active" href="{{ $catalogListUrl }}" aria-current="page">Todos</a>
            <a aria-disabled="true" title="Próximamente">Novedades</a>
            <a aria-disabled="true" title="Próximamente">Destacados</a>
            <a aria-disabled="true" title="Próximamente">Promociones</a>
            <a href="{{ $catalogListUrl }}" title="Ver productos con stock disponible">Disponibles</a>
        </nav>--}}
        @if($facets['category_id']->isNotEmpty())
            @php
                $categoryFilters = \Illuminate\Support\Arr::except($filters, ['category_id', 'subcategory_id']);
                $categoryFilters = array_filter($categoryFilters, function ($value) {
                    return $value !== null && $value !== '';
                });
                $categoryUrl = function (array $params) use ($catalogListUrl) {
                    return $catalogListUrl . ($params ? '?' . http_build_query($params) : '');
                };
            @endphp
            <nav class="catalog-shop-categories" aria-label="Categorías de productos">
                <a href="{{ $categoryUrl($categoryFilters) }}" @if(empty($filters['category_id'])) class="active" aria-current="page" @endif>Todas las categorías</a>
                @foreach($facets['category_id'] as $category)
                    <a href="{{ $categoryUrl($categoryFilters + ['category_id' => $category->id]) }}"
                       @if((string) ($filters['category_id'] ?? '') === (string) $category->id) class="active" aria-current="page" @endif>{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif
        <div class="catalog-shop-toolbar">
            <div class="catalog-shop-toolbar__summary">
                <h1>Todos los productos</h1>
                <span>{{ $products->total() }} productos</span>
            </div>
            <button class="catalog-filter-trigger" type="button" aria-controls="catalog-filter-drawer" aria-expanded="false">
                <span class="icon_adjust-horiz" aria-hidden="true"></span> Filtros
            </button>
        </div>

        <section class="catalog-product-grid" aria-label="Productos">
            @forelse($products as $product)
                @php
                    $url = $selected ? route('publicCatalog.branchProduct', [$company->catalog_slug, $selected->catalog_slug, $product->id]) : route('publicCatalog.product', [$company->catalog_slug, $product->id]);
                @endphp
                <a class="catalog-product-card" href="{{ $url }}">
                    <div class="catalog-product-card__media"><img loading="lazy" src="{{ $catalog->image($product->image) }}" alt="{{ $product->full_name }}"></div>
                    <div class="catalog-product-card__body">
                        <h2>{{ $product->full_name }}</h2>
                        @if($showPrices)
                            @foreach(['S/ ' => [$product->pen_min, $product->pen_max], 'US$ ' => [$product->usd_min, $product->usd_max]] as $symbol => [$min, $max])
                                @if($min !== null)
                                    <p class="catalog-product-card__price">{{ $max > $min ? 'Desde ' : '' }}{{ $symbol }}{{ number_format($min, 2) }}</p>
                                @endif
                            @endforeach
                            @if($product->pen_min === null && $product->usd_min === null)<p class="catalog-product-card__price">Consultar precio</p>@endif
                        @endif
                        <span class="catalog-product-card__status">Disponible</span>
                    </div>
                </a>
            @empty
                <p>No hay productos disponibles para esta selección.</p>
            @endforelse
        </section>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</main>
@endsection
@section('overlays')
    @include('shop.publicCatalog.filters')
@endsection


