'use strict';

(function () {
    const body = document.body;
    let activeOverlay = null;
    let savedBodyPadding = '';
    let savedBodyOverflow = '';

    function focusableElements(container) {
        const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.from(container.querySelectorAll(selector)).filter((element) => element.offsetParent !== null);
    }

    function lockBody() {
        savedBodyPadding = body.style.paddingRight;
        savedBodyOverflow = body.style.overflow;

        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        if (scrollbarWidth > 0) {
            const currentPadding = Number.parseFloat(window.getComputedStyle(body).paddingRight) || 0;
            body.style.paddingRight = `${currentPadding + scrollbarWidth}px`;
        }

        body.style.overflow = 'hidden';
        body.classList.add('catalog-overlay-open');
    }

    function unlockBody() {
        body.style.paddingRight = savedBodyPadding;
        body.style.overflow = savedBodyOverflow;
        body.classList.remove('catalog-overlay-open');
    }

    function createOverlayController(options) {
        const root = document.querySelector(options.root);
        if (!root) return null;

        const panel = root.querySelector(options.panel);
        const triggers = Array.from(document.querySelectorAll(options.triggers));
        const closeControls = Array.from(root.querySelectorAll(options.closeControls));
        let previousFocus = null;

        function open(trigger) {
            if (activeOverlay && activeOverlay !== controller) activeOverlay.close(false);
            if (root.classList.contains('active')) return;

            previousFocus = trigger || document.activeElement;
            root.classList.add('active');
            root.setAttribute('aria-hidden', 'false');
            triggers.forEach((item) => item.setAttribute('aria-expanded', 'true'));
            if (options.bodyClass) body.classList.add(options.bodyClass);
            activeOverlay = controller;
            lockBody();

            window.requestAnimationFrame(() => {
                const initialFocus = root.querySelector(options.initialFocus) || focusableElements(panel)[0] || panel;
                initialFocus.focus();
            });
        }

        function close(restoreFocus = true) {
            if (!root.classList.contains('active')) return;

            root.classList.remove('active');
            root.setAttribute('aria-hidden', 'true');
            triggers.forEach((item) => item.setAttribute('aria-expanded', 'false'));
            if (options.bodyClass) body.classList.remove(options.bodyClass);
            if (activeOverlay === controller) activeOverlay = null;
            unlockBody();

            if (restoreFocus && previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }
        }

        function handleKeydown(event) {
            if (!root.classList.contains('active')) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                close();
                return;
            }

            if (event.key !== 'Tab') return;

            const focusable = focusableElements(panel);
            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            } else if (!panel.contains(document.activeElement)) {
                event.preventDefault();
                first.focus();
            }
        }

        const controller = { open, close };

        triggers.forEach((trigger) => trigger.addEventListener('click', () => open(trigger)));
        closeControls.forEach((control) => control.addEventListener('click', () => close()));
        root.addEventListener('keydown', handleKeydown);

        return controller;
    }

    const mobileMenu = createOverlayController({
        root: '.catalog-mobile-menu',
        panel: '.catalog-mobile-menu__panel',
        triggers: '[data-catalog-menu-open]',
        closeControls: '[data-catalog-menu-close]',
        initialFocus: '.catalog-mobile-menu__close',
        bodyClass: 'catalog-mobile-menu-open'
    });

    if (mobileMenu) {
        document.querySelectorAll('.catalog-mobile-menu__nav a').forEach((link) => {
            link.addEventListener('click', () => mobileMenu.close(false));
        });
    }

    const searchForm = document.querySelector('.catalog-search__form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('.catalog-search__input');
        const clearButton = searchForm.querySelector('.catalog-search__clear');
        const focusTriggers = document.querySelectorAll('[data-catalog-search-focus]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function updateClearButton() {
            clearButton.hidden = searchInput.value.length === 0;
        }

        function focusSearch() {
            const inputBounds = searchInput.getBoundingClientRect();
            const inputIsVisible = inputBounds.top >= 0 && inputBounds.bottom <= window.innerHeight;

            if (!inputIsVisible) {
                searchInput.scrollIntoView({
                    behavior: reducedMotion ? 'auto' : 'smooth',
                    block: 'center'
                });
            }

            searchInput.focus({ preventScroll: true });
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }

        searchForm.addEventListener('submit', (event) => event.preventDefault());
        searchInput.addEventListener('input', updateClearButton);
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
        });
        focusTriggers.forEach((trigger) => trigger.addEventListener('click', focusSearch));
        updateClearButton();
    }

    createOverlayController({
        root: '.catalog-filter-drawer',
        panel: '.catalog-filter-drawer__panel',
        triggers: '.catalog-filter-trigger',
        closeControls: '[data-filter-close], [data-filter-apply]',
        initialFocus: '.catalog-filter-drawer__close',
        bodyClass: 'catalog-filter-open'
    });

    const productGallery = document.querySelector('.catalog-product-gallery');
    if (productGallery) {
        const mainImage = productGallery.querySelector('#catalog-product-main-image');
        const counter = productGallery.querySelector('.catalog-product-gallery__counter');
        const thumbnails = Array.from(productGallery.querySelectorAll('.catalog-product-gallery__thumb'));

        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => {
                thumbnails.forEach((item) => {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });

                thumbnail.classList.add('active');
                thumbnail.setAttribute('aria-pressed', 'true');
                mainImage.src = thumbnail.dataset.galleryImage;
                mainImage.alt = thumbnail.dataset.galleryAlt;
                counter.textContent = `${index + 1} / ${thumbnails.length}`;
            });
        });
    }
})();
