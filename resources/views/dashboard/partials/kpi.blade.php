@php
    $variantClass = isset($variant) ? ' next-kpi-card--'.$variant : '';
    $toneClass = isset($tone) ? ' next-kpi-card--'.$tone : '';
@endphp
<a class="next-kpi-card{{ $variantClass }}{{ $toneClass }}" href="{{ $href }}" aria-label="Ver {{ $label }}: {{ $value }} registros">
    <span class="next-kpi-icon"><i class="{{ $icon }}" aria-hidden="true"></i></span>
    <span class="next-kpi-content">
        <span class="next-kpi-label">{{ $label }}</span>
        <strong class="next-kpi-value">{{ number_format($value) }}</strong>
    </span>
    <i class="fas fa-arrow-right next-kpi-arrow" aria-hidden="true"></i>
</a>
