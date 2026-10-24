(function ($) {
    'use strict';
    const context = window.normalPurchaseContext;
    $.ajaxPrefilter(function (options) {
        if (!context || String(options.type || 'GET').toUpperCase() === 'GET') { return; }
        const url = new URL(options.url, window.location.href);
        if (url.origin !== window.location.origin) { return; }
        const path = url.pathname;
        if (!(/^\/dashboard\/(store\/order\/purchase\/normal|update\/order\/purchase\/normal|destroy\/order\/purchase\/normal\/|update\/detail\/order\/purchase\/normal\/|destroy\/detail\/order\/purchase\/normal\/|order_purchase\/change\/status\/|change\/state\/order\/purchase|restore\/order\/purchase\/delete\/)/).test(path)) { return; }
        options.headers = options.headers || {};
        options.headers['X-Purchase-Tenant'] = String(context.tenant_id);
        options.headers['X-Purchase-Company'] = String(context.company_id);
        options.headers.Accept = 'application/json';
    });
})(jQuery);
