/* LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION. No variant resolution or business requests. */
(function () {
    'use strict';
    var node = document.getElementById('store-web-detail-contract');
    if (!node || document.body.dataset.storeWebFixture !== 'false') return;
    var contract = JSON.parse(node.textContent);
    var failed = new Set();
    var stage = document.getElementById('catalog-product-main-image');
    function fallback(image, url) {
        function recover() {
            if (image.src === url) return;
            if (image === stage) {
                failed.add(image.src);
                document.querySelectorAll('[data-gallery-image]').forEach(function (button) {
                    if (failed.has(button.dataset.galleryImage)) button.dataset.galleryImage = url;
                });
            }
            image.src = url;
        }
        image.addEventListener('error', recover);
        if (image.complete && !image.naturalWidth) recover();
    }
    document.querySelectorAll('.catalog-product-gallery img').forEach(function (image) { fallback(image, contract.fallback); });
    document.querySelectorAll('.header__logo img, .catalog-mobile-menu__logo img, .catalog-product-detail-footer img').forEach(function (image) { fallback(image, contract.logoFallback); });
    document.querySelectorAll('.catalog-whatsapp-cta').forEach(function (button) {
        button.disabled = !/^\d+$/.test(contract.phone);
        button.addEventListener('click', function () {
            if (button.disabled) return;
            var message = 'Hola, quisiera consultar por ' + contract.name + '. ' + contract.url;
            window.open('https://wa.me/' + contract.phone + '?text=' + encodeURIComponent(message), '_blank', 'noopener,noreferrer');
        });
    });
}());
