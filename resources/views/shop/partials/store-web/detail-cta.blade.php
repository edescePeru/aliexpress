<button class="catalog-whatsapp-cta{{ $inline ? ' catalog-whatsapp-cta--inline' : '' }}" type="button" data-product-name="{{ $detailName }}" data-product-url="{{ $detailUrl }}" @if(!$detailPhone)disabled aria-disabled="true"@endif>
    <i class="fa fa-whatsapp" aria-hidden="true"></i><span>Consultar por WhatsApp</span>
</button>
