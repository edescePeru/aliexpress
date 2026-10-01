/* Home navigation adapter. No business requests, persistence or data binding. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const form = document.querySelector('[data-home-catalog-search]');
    if (!form) return;
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const input = form.querySelector('[name="search"]');
        const search = input.value.trim();
        if (!search) { input.focus(); return; }
        const destination = new URL(form.action, window.location.href);
        destination.searchParams.set('search', search);
        window.location.assign(destination.href);
    });
});
