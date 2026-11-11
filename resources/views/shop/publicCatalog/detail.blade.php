@extends('layouts.storeWeb', ['storeWebPage' => 'detail'])
@section('title', $product->full_name)
@section('content')
<main class="catalog-product-detail">
    <div class="catalog-container">
        <a class="catalog-product-detail__back" href="{{ $branch ? route('publicCatalog.branch', [$company->catalog_slug, $branch->catalog_slug]) : route('publicCatalog.company', $company->catalog_slug) }}">← Volver a productos</a>
        <section class="catalog-product-detail__hero">
            <div class="catalog-product-summary text-center">
                <img class="img-fluid" src="{{ $catalog->image($product->image) }}" alt="{{ $product->full_name }}">
            </div>
            <div>
                <div class="catalog-product-summary">
                    <h1>{{ $product->full_name }}</h1>
                    @if($showPrices)
                        <div class="catalog-product-summary__pricing">
                            @foreach(['PEN' => 'S/ ', 'USD' => 'US$ '] as $currency => $symbol)
                                @php
                                    $ranges = $locations->pluck('prices.'.$currency)->filter();
                                    $min = $ranges->min(function ($range) { return $range[0]; });
                                    $max = $ranges->max(function ($range) { return $range[1]; });
                                @endphp
                                @if($min !== null)
                                    <strong>{{ $max > $min ? 'Desde ' : '' }}{{ $symbol }}{{ number_format($min, 2) }}</strong>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <span class="catalog-product-availability catalog-product-availability--available">Disponible</span>
                    @if($whatsapp)
                        <a class="catalog-whatsapp-cta catalog-whatsapp-cta--inline" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quisiera consultar por '.$product->full_name) }}" target="_blank" rel="noopener noreferrer"><i class="fa fa-whatsapp" aria-hidden="true"></i><span>Consultar por WhatsApp</span></a>
                    @else
                        <button class="catalog-whatsapp-cta catalog-whatsapp-cta--inline" type="button" disabled>
                            <i class="fa fa-whatsapp" aria-hidden="true"></i><span>Consultar por WhatsApp</span>
                        </button>
                        <p class="catalog-product-summary__cta-note">Contacto por WhatsApp no disponible.</p>
                    @endif
                </div>
                <section class="catalog-product-summary mt-3" aria-labelledby="tiendas">
                    <h2 id="tiendas" class="h5">Disponible en tiendas</h2>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Tienda</th>@if($showPrices)<th>Precio</th>@endif</tr></thead>
                            <tbody>
                            @foreach($locations as $location)
                                <tr>
                                    <td><a href="{{ route('publicCatalog.branchProduct', [$company->catalog_slug, $location['slug'], $product->id]) }}">{{ $location['name'] }}</a></td>
                                    @if($showPrices)
                                        <td>
                                            @forelse($location['prices'] as $currency => [$min, $max])
                                                <div>{{ $max > $min ? 'Desde ' : '' }}{{ $currency === 'USD' ? 'US$ ' : 'S/ ' }}{{ number_format($min, 2) }}</div>
                                            @empty
                                                Consultar precio
                                            @endforelse
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </section>
    </div>
</main>
@endsection
