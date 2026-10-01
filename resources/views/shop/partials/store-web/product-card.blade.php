{{-- BIND REAL DATA: card URL/name/image/price/availability after SW-01. Slot currently contains only fixture markup. --}}
<a class="catalog-product-card{{ !empty($unavailable) ? ' catalog-product-card--unavailable' : '' }}" href="{{ route('shop.product.show', ['material' => 'frontend-fixture']) }}">
    {{ $slot }}
</a>
