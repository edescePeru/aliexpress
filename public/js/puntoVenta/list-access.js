(function ($) {
    'use strict';
    function context() { return document.getElementById('sale-list-context'); }
    function permissions() { const node = context(); return node ? JSON.parse(node.getAttribute('data-permissions') || '{}') : {}; }
    // Header snapshot does not alter FormData or JSON business payloads.
    $.ajaxPrefilter(function (options, original, xhr) {
        const node = context(); if (!node) return;
        const url = new URL(options.url, window.location.href);
        if (url.origin !== window.location.origin) return;
        const path = url.pathname;
        if ((options.type || 'GET').toUpperCase() === 'GET') {
            if (/\/dashboard\/get\/data\/sales\//.test(path)) {
                const creator = $('#filter_created_by_id').val();
                if (creator) options.url += (options.url.indexOf('?') < 0 ? '?' : '&') + 'created_by_id=' + encodeURIComponent(creator);
            }
        }
        if (!/^\/dashboard\/(?:get\/data\/sales\/|sales\/|sale\/|nota-credito\/parcial\/data\/|anular\/order\/|facturador\/generar|consultar\/anulacion\/|generar\/nota-credito\/|consultar\/nota-credito\/|ventas\/|ventas-libres\/)/.test(path)) return;
        xhr.setRequestHeader('X-Sales-Tenant', node.getAttribute('data-tenant'));
        xhr.setRequestHeader('X-Sales-Company', node.getAttribute('data-company'));
        xhr.setRequestHeader('X-Sales-Branch', node.getAttribute('data-branch'));
    });
    function update(root) {
        const p = permissions();
        const rules = {
            print: '[data-print_recibo],[data-imprimir_comprobante],[data-print_anulacion_pdf],[data-print_anulacion_xml],[data-print_anulacion_cdr],[data-ver_nc_parcial_pdf]',
            annul: '[data-anular],[data-generar_nota_credito],[data-generar_nota_credito_parcial],[data-consultar_anulacion],[data-consultar_nota_credito],[data-descartar_error_comprobante]',
            payments: '[data-pagos_parciales],[data-eliminar_pago_parcial]',
            documents: '[data-facturador],[data-recuperar_archivos_nubefact]',
            emit: '[data-generar_comprobante]'
        };
        Object.keys(rules).forEach(function (key) { if (!p[key]) $(root).find(rules[key]).hide().attr('aria-hidden', 'true'); });
        if (!p.dispatch) $(root).find('.switch-dispatch-status').prop('disabled', true);
    }
    window.saleListApplyPermissions = update;
    $(function () {
        update(document);
        $('#filter_created_by_id').on('change', function () { if (typeof getDataOrders === 'function') getDataOrders(1); });
        if (document.body) new MutationObserver(function () { update(document); }).observe(document.body, {childList: true, subtree: true});
    });
})(jQuery);
