(function ($) {
    'use strict';
    // Capture the rendered screen, never a later AJAX response or current session.
    var node = document.getElementById('material-inventory-context');
    if (!node || !$ || !$.ajaxPrefilter) return;
    var context = {
        Tenant: node.getAttribute('data-tenant'),
        Company: node.getAttribute('data-company'),
        Branch: node.getAttribute('data-branch')
    };
    $.ajaxPrefilter(function (options, original, xhr) {
        var url;
        try { url = new URL(options.url, window.location.href); } catch (e) { return; }
        if (url.origin !== window.location.origin ||
            !/^\/dashboard\/(?:(?:stock-items|material)\/[1-9][0-9]*\/inventory-levels|get\/data\/material\/v2\/[1-9][0-9]*|stock-items\/list|stock-items\/[1-9][0-9]*\/toggle-(?:inventory|active)|view\/stock\/material\/all\/items\/[1-9][0-9]*|stock\/material\/item\/[1-9][0-9]*\/code)\/?$/.test(url.pathname)) return;
        Object.keys(context).forEach(function (key) {
            xhr.setRequestHeader('X-Material-Inventory-' + key, context[key]);
        });
    });
})(window.jQuery);
