(function ($) {
    'use strict';
    var context = document.getElementById('pos-catalog-context');
    if (!context) return;
    var snapshot = {tenant: context.dataset.tenant, company: context.dataset.company, branch: context.dataset.branch};
    var searchURL = context.dataset.searchUrl, availabilityURL = context.dataset.availabilityUrl;
    $.ajaxPrefilter(function (options, original, xhr) {
        var url = new URL(options.url, window.location.href);
        if (url.origin !== window.location.origin || !/^(GET|HEAD)$/i.test(options.type || 'GET')) return;
        if (!/\/get\/data\/products\/(?:v2\/)?[1-9][0-9]*\/?$/.test(url.pathname)
            && !/\/get\/material\/[1-9][0-9]*\/stock-items\/?$/.test(url.pathname)
            && url.href.split('?')[0] !== searchURL
            && !url.pathname.includes('/pos/catalog/stock-items/')
            && !/\/pos\/(?:stock-items\/[1-9][0-9]*\/items|materials\/[1-9][0-9]*\/presentations)\/?$/.test(url.pathname)) return;
        Object.keys(snapshot).forEach(function (key) {
            xhr.setRequestHeader('X-Material-Inventory-'+key.charAt(0).toUpperCase()+key.slice(1), snapshot[key]);
        });
    });
    window.POS_TAX_KIND_BY_STOCK = window.POS_TAX_KIND_BY_STOCK || {};
    window.posCatalogPrepareProduct = function (node, item) {
        if(item.source==='stock_item') window.POS_TAX_KIND_BY_STOCK[item.stock_item_id || item.id] = item.tax_kind;
        var el = $(node), price = 'Sin precio';
        if (item.has_price) {
            var amount = Number(item.price).toFixed(2);
            if (item.price_is_reference) {
                var symbol = item.currency === 'PEN' ? 'S/. ' : (item.currency === 'USD' ? 'US$ ' : (item.currency ? item.currency+' ' : ''));
                price = 'Desde '+symbol+amount;
            } else {
                price = amount+(item.currency ? ' '+item.currency : '');
            }
        }
        el.find('[data-price]').text(price);
        el.find('[data-name]').text(item.full_name || '');
        if (item.image_url) el.find('[data-image1]').attr('src', item.image_url);
        el.find('[data-pos-stock]').attr('data-pos-stock-item-id', item.stock_item_id || item.id);
        if (item.source === 'stock_item' && !item.has_price) {
            el.find('[data-add_cart], [data-add_cart_special]').removeAttr('data-add_cart data-add_cart_special')
                .addClass('disabled').attr({'aria-disabled':'true', tabindex:'-1'}).prop('disabled', true);
        }
    };
    var searchRequest, stockRequest, searchSequence = 0, stockSequence = 0, restoreVariants = false;
    var modal = $('#posStockLookup');
    $('body').tooltip({selector: '[data-pos-stock-search]', placement: 'top', container: 'body'});
    function cancelRequests() {
        searchSequence++; stockSequence++;
        if (searchRequest) searchRequest.abort();
        if (stockRequest) stockRequest.abort();
    }
    function clearStock() {
        $('#pos-stock-product, #pos-stock-local, #pos-stock-message, #pos-stock-groups, #pos-stock-checked').empty();
    }
    function errorMessage(xhr) {
        if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors;
            if (errors && errors.context) return errors.context[0];
        }
        return xhr.status === 403 ? 'No tiene autorización para consultar estas existencias.'
            : 'No se pudo consultar la información. Recargue la pantalla e inténtelo nuevamente.';
    }
    function loadStock(id) {
        if (!/^[1-9][0-9]*$/.test(String(id))) return;
        var sequence = ++stockSequence;
        if (stockRequest) stockRequest.abort();
        clearStock(); $('#pos-stock-message').text('Consultando existencias…');
        stockRequest = $.getJSON(availabilityURL.replace('__ID__', id)).done(function (data) {
            if (sequence !== stockSequence) return;
            clearStock();
            $('#pos-stock-product').text(data.product.name+' · '+data.product.sku);
            $('#pos-stock-local').text('Disponible en el local actual: '+Number(data.local.available).toFixed(2));
            if (data.local.requires_review || data.requires_review) {
                $('#pos-stock-message').addClass('text-warning').text('Hay saldos que requieren revisión; no se muestran como disponibilidad para traslado.');
            } else $('#pos-stock-message').removeClass('text-warning').text(data.groups.length ? '' : 'Sin existencias disponibles en otros almacenes.');
            data.groups.forEach(function (group) {
                var section = $('<section class="mb-3">').append($('<h6>').text(group.title));
                var table = $('<table class="table table-sm table-bordered"><thead><tr><th>Almacén</th><th>Físico</th><th>Reservado</th><th>Disponible</th></tr></thead><tbody></tbody></table>');
                group.warehouses.forEach(function (warehouse) {
                    var row = $('<tr>'); $('<td>').text(warehouse.name).appendTo(row);
                    ['on_hand','reserved','available'].forEach(function (key) { $('<td>').text(Number(warehouse[key]).toFixed(2)).appendTo(row); });
                    table.find('tbody').append(row);
                });
                section.append($('<div class="table-responsive">').append(table)).appendTo('#pos-stock-groups');
            });
            $('#pos-stock-checked').text('Consultado: '+data.checked_at);
        }).fail(function (xhr, status) { if (sequence === stockSequence && status !== 'abort') $('#pos-stock-message').text(errorMessage(xhr)); });
    }
    function search(page) {
        var sequence = ++searchSequence;
        if (searchRequest) searchRequest.abort();
        stockSequence++;
        if (stockRequest) stockRequest.abort();
        clearStock();
        $('#pos-stock-results, #pos-stock-pages').empty();
        var term = String($('#pos-stock-query').val() || '').trim();
        if (!term) {
            $('#pos-stock-results').text('Escriba un nombre, variante, SKU o código de barras para buscar.');
            return;
        }
        $('#pos-stock-results').text('Buscando…');
        searchRequest = $.getJSON(searchURL, {q: term, page: page || 1}).done(function (data) {
            if (sequence !== searchSequence) return;
            var results = $('#pos-stock-results').empty();
            if (!data.data.length) results.text('Sin productos habilitados que coincidan con la búsqueda.');
            data.data.forEach(function (item) {
                $('<button type="button" class="list-group-item list-group-item-action">').text(item.text)
                    .on('click', function () { loadStock(item.id); }).appendTo(results);
            });
            var p = data.pagination;
            if (p.currentPage > 1) $('<button type="button" class="btn btn-sm btn-light mr-2">Anterior</button>').on('click', function () {search(p.currentPage-1);}).appendTo('#pos-stock-pages');
            if (p.currentPage < p.totalPages) $('<button type="button" class="btn btn-sm btn-light">Siguiente</button>').on('click', function () {search(p.currentPage+1);}).appendTo('#pos-stock-pages');
        }).fail(function (xhr, status) { if (sequence === searchSequence && status !== 'abort') $('#pos-stock-results').text(errorMessage(xhr)); });
    }
    function open(searchMode, id) {
        cancelRequests(); clearStock(); $('#pos-stock-results, #pos-stock-pages').empty();
        $('#pos-stock-search-panel').toggle(searchMode);
        if (searchMode) $('#pos-stock-query').val('');
        var show = function () {
            if (searchMode) modal.one('shown.bs.modal', function () { $('#pos-stock-query').trigger('focus'); });
            modal.modal('show');
            if (!searchMode) loadStock(id);
        };
        var variants = $('#modalStockItemsVenta');
        restoreVariants = variants.hasClass('show');
        if (restoreVariants) variants.one('hidden.bs.modal', show).modal('hide'); else show();
    }
    $(document).on('click', '[data-pos-stock-search]', function (e) { e.preventDefault(); open(true); });
    $(document).on('click', '[data-pos-stock]', function (e) { e.preventDefault(); e.stopPropagation(); open(false, $(this).attr('data-pos-stock-item-id')); });
    $('#pos-stock-search-submit').on('click', function () { search(1); });
    $('#pos-stock-query').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); search(1); } });
    modal.on('hidden.bs.modal', function () { cancelRequests(); if (restoreVariants) { restoreVariants = false; $('#modalStockItemsVenta').modal('show'); } });
})(jQuery);
