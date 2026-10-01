{{-- BIND REAL DATA: logo/contact/social links. Fixture copy only. --}}
@if($storeWebPage === 'home')
<footer class="catalog-home-footer" id="catalog-contact">
        <div class="catalog-container">
            <div class="catalog-home-footer__content">
                <div>
                    <img src="{{ $homeBranding['logo'] }}" alt="{{ $homeBranding['label'] }}">
                    <p>{{ $homeBranding['footer'] }}</p>
                    {{-- BACKEND CONTRACT PENDING: publicly approved social URLs; null means no navigation. --}}
                    <nav class="catalog-social-links" aria-label="Redes sociales">
                        <{{ $homeBranding['socials']['instagram'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($homeBranding['socials']['instagram'])href="{{ $homeBranding['socials']['instagram'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="instagram" aria-label="Instagram"><i class="fa fa-instagram" aria-hidden="true"></i></{{ $homeBranding['socials']['instagram'] ? 'a' : 'span' }}>
                        <{{ $homeBranding['socials']['tiktok'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($homeBranding['socials']['tiktok'])href="{{ $homeBranding['socials']['tiktok'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="tiktok" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25h-3.31v13.28a2.75 2.75 0 1 1-2.75-2.75c.28 0 .55.04.81.12V9.74a6.09 6.09 0 1 0 5.25 6.03V9.03a8.1 8.1 0 0 0 4.77 1.52V7.24c-.34 0-.67-.03-1-.1v-.45Z"/></svg></{{ $homeBranding['socials']['tiktok'] ? 'a' : 'span' }}>
                        <{{ $homeBranding['socials']['facebook'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($homeBranding['socials']['facebook'])href="{{ $homeBranding['socials']['facebook'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="facebook" aria-label="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></{{ $homeBranding['socials']['facebook'] ? 'a' : 'span' }}>
                        <{{ $homeBranding['socials']['youtube'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($homeBranding['socials']['youtube'])href="{{ $homeBranding['socials']['youtube'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="youtube" aria-label="YouTube"><i class="fa fa-youtube-play" aria-hidden="true"></i></{{ $homeBranding['socials']['youtube'] ? 'a' : 'span' }}>
                    </nav>
                </div>
                <a href="{{ route('store-web.catalog') }}">Ver productos</a>
            </div>
            <div class="footer__copyright__text catalog-home-footer__copyright">
                <p>&copy; 2026 Venti360. Todos los derechos reservados. &middot; <a href="https://venti360.com" target="_blank" rel="noopener noreferrer">Venti360.com</a></p>
            </div>
        </div>
    </footer>
@elseif($storeWebPage === 'shop')
<footer class="catalog-shop-footer" id="catalog-contact">
        <div class="catalog-container">
            <div class="catalog-shop-footer__content">
                <div>
                    <img src="{{ asset('store-web/img/logo.png') }}" alt="Catálogo del negocio">
                    <p>Explora todos nuestros productos.</p>
                    <nav class="catalog-social-links" aria-label="Redes sociales">
                        <a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="instagram" aria-label="Instagram"><i class="fa fa-instagram" aria-hidden="true"></i></a>
                        <a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="tiktok" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25h-3.31v13.28a2.75 2.75 0 1 1-2.75-2.75c.28 0 .55.04.81.12V9.74a6.09 6.09 0 1 0 5.25 6.03V9.03a8.1 8.1 0 0 0 4.77 1.52V7.24c-.34 0-.67-.03-1-.1v-.45Z"/></svg></a>
                        <a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="facebook" aria-label="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                        <a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="youtube" aria-label="YouTube"><i class="fa fa-youtube-play" aria-hidden="true"></i></a>
                    </nav>
                </div>
                <a href="{{ route('store-web.home') }}">Volver al inicio</a>
            </div>
            <div class="footer__copyright__text catalog-shop-footer__copyright">
                <p>&copy; 2026 Venti360. Todos los derechos reservados. &middot; <a href="https://venti360.com" target="_blank" rel="noopener noreferrer">Venti360.com</a></p>
            </div>
        </div>
    </footer>
@elseif($storeWebPage === 'detail')
<footer class="catalog-product-detail-footer" id="catalog-contact">
        <div class="catalog-container">
            <div class="catalog-product-detail-footer__content">
                <div>
                    <img src="{{ $detailBranding['logo'] ?? asset('store-web/img/logo.png') }}" alt="Catálogo del negocio">
                    <p>{{ $detailBranding['footer'] ?? 'Consulta este producto directamente con el negocio.' }}</p>
                    <nav class="catalog-social-links" aria-label="Redes sociales">
                        @if(isset($detailBranding))
<{{ $detailBranding['socials']['instagram'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($detailBranding['socials']['instagram'])href="{{ $detailBranding['socials']['instagram'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="instagram" aria-label="Instagram"><i class="fa fa-instagram" aria-hidden="true"></i></{{ $detailBranding['socials']['instagram'] ? 'a' : 'span' }}>
@else
<a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="instagram" aria-label="Instagram"><i class="fa fa-instagram" aria-hidden="true"></i></a>
@endif
                        @if(isset($detailBranding))
<{{ $detailBranding['socials']['tiktok'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($detailBranding['socials']['tiktok'])href="{{ $detailBranding['socials']['tiktok'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="tiktok" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25h-3.31v13.28a2.75 2.75 0 1 1-2.75-2.75c.28 0 .55.04.81.12V9.74a6.09 6.09 0 1 0 5.25 6.03V9.03a8.1 8.1 0 0 0 4.77 1.52V7.24c-.34 0-.67-.03-1-.1v-.45Z"/></svg></{{ $detailBranding['socials']['tiktok'] ? 'a' : 'span' }}>
@else
<a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="tiktok" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25h-3.31v13.28a2.75 2.75 0 1 1-2.75-2.75c.28 0 .55.04.81.12V9.74a6.09 6.09 0 1 0 5.25 6.03V9.03a8.1 8.1 0 0 0 4.77 1.52V7.24c-.34 0-.67-.03-1-.1v-.45Z"/></svg></a>
@endif
                        @if(isset($detailBranding))
<{{ $detailBranding['socials']['facebook'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($detailBranding['socials']['facebook'])href="{{ $detailBranding['socials']['facebook'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="facebook" aria-label="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></{{ $detailBranding['socials']['facebook'] ? 'a' : 'span' }}>
@else
<a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="facebook" aria-label="Facebook"><i class="fa fa-facebook" aria-hidden="true"></i></a>
@endif
                        @if(isset($detailBranding))
<{{ $detailBranding['socials']['youtube'] ? 'a' : 'span' }} class="catalog-social-links__item" @if($detailBranding['socials']['youtube'])href="{{ $detailBranding['socials']['youtube'] }}" target="_blank" rel="noopener noreferrer"@else role="img" aria-disabled="true"@endif data-social="youtube" aria-label="YouTube"><i class="fa fa-youtube-play" aria-hidden="true"></i></{{ $detailBranding['socials']['youtube'] ? 'a' : 'span' }}>
@else
<a class="catalog-social-links__item" href="#" target="_blank" rel="noopener noreferrer" data-social="youtube" aria-label="YouTube"><i class="fa fa-youtube-play" aria-hidden="true"></i></a>
@endif
                    </nav>
                </div>
                <a href="{{ route('store-web.catalog') }}">Ver más productos</a>
            </div>
            <div class="footer__copyright__text catalog-product-detail-footer__copyright">
                <p>&copy; 2026 Venti360. Todos los derechos reservados. &middot; <a href="https://venti360.com" target="_blank" rel="noopener noreferrer">Venti360.com</a></p>
            </div>
        </div>
    </footer>
@endif
