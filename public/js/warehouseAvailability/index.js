/* global jQuery */
(function ($) {
    'use strict';
    $(function () {
        var form = document.getElementById('wa-search');
        if (!form) return;
        var request = null, revision = 0;
        var product = $('#wa-product');
        function context() {
            return {form_tenant_id: $(form).find('[name=form_tenant_id]').val(),
                form_company_id: $(form).find('[name=form_company_id]').val(), form_branch_id: $(form).find('[name=form_branch_id]').val()};
        }
        function error(xhr) {
            var data = xhr.responseJSON || {};
            var messages = data.errors ? Object.keys(data.errors).reduce(function (all, key) { return all.concat(data.errors[key]); }, []) : [];
            $('#wa-error').removeClass('d-none').text(messages.length ? messages.join(' ') : (data.message || 'No se pudo consultar la disponibilidad. Reintenta o recarga si cambió el contexto.'));
        }
        function quantity(value) { return value === null ? '—' : value; }
        function render(data) {
            $('#wa-selected').text(data.product.text); $('#wa-checked').text('Consultado: ' + data.checked_at); $('#wa-summary').removeClass('d-none');
            var target = $('#wa-groups').empty();
            data.groups.forEach(function (group, index) {
                var section = $('<section class="col-12 col-md-6 col-xl-3 mb-3">').attr('aria-labelledby', 'wa-group-' + index);
                var heading = $('<h2 class="h6 mb-3">').attr('id', 'wa-group-' + index).text(group.title);
                if (group.current) heading.append($('<span class="badge badge-primary ml-2">').text('Local actual'));
                section.append(heading);
                if (!group.warehouses.length) section.append($('<p class="text-muted small">').text('Sin almacenes configurados.'));
                group.warehouses.forEach(function (warehouse) {
                    var box = $('<article class="info-box align-items-start mb-3">');
                    box.append($('<span class="info-box-icon bg-light text-primary">').append($('<i class="fas fa-warehouse" aria-hidden="true">')));
                    var content = $('<div class="info-box-content">');
                    content.append($('<h3 class="h6 text-wrap mb-2">').text(warehouse.name));
                    content.append($('<span class="text-muted small">').text('Disponible'));
                    content.append($('<strong class="info-box-number h3 mt-0">').text(quantity(warehouse.available)));
                    content.append($('<span class="small text-muted d-block">').text('Existencias: ' + quantity(warehouse.on_hand)));
                    content.append($('<span class="small text-muted d-block mb-2">').text('Reservado: ' + quantity(warehouse.reserved)));
                    if (Number(warehouse.incoming) > 0) content.append($('<span class="small text-info d-block">').text('Por recibir: ' + warehouse.incoming));
                    if (Number(warehouse.outgoing) > 0) content.append($('<span class="small text-info d-block">').text('Enviado en tránsito: ' + warehouse.outgoing));
                    if (warehouse.requires_review) content.append($('<span class="small text-warning d-block mt-2">').text('Revisar inventario y trazabilidad antes de trasladar.'));
                    if (warehouse.request_url) content.append($('<a class="btn btn-outline-primary btn-sm mt-2">').attr('href', warehouse.request_url).text('Solicitar traslado'));
                    box.append(content); section.append(box);
                });
                target.append(section);
            });
        }
        function load() {
            var id = product.val(); var token = ++revision;
            if (request) request.abort();
            $('#wa-groups').empty(); $('#wa-summary').addClass('d-none'); $('#wa-error').addClass('d-none').empty();
            $('#wa-empty').toggleClass('d-none', Boolean(id)); $('#wa-loading').toggleClass('d-none', !id); $('#wa-refresh').prop('disabled', true);
            if (!id) return;
            var params = context(); params.stock_item_id = id;
            request = $.ajax({url: $(form).data('stock-url'), data: params, dataType: 'json', headers: {'Accept': 'application/json'}})
                .done(function (data) { if (token === revision) render(data); })
                .fail(function (xhr) { if (token === revision && xhr.statusText !== 'abort') error(xhr); })
                .always(function () { if (token === revision) { $('#wa-loading').addClass('d-none'); $('#wa-refresh').prop('disabled', !product.val()); } });
        }
        product.select2({theme: 'bootstrap4', width: '100%', placeholder: 'Buscar por producto, variante, SKU o código de barras', allowClear: true, minimumInputLength: 1,
            ajax: {url: $(form).data('products-url'), dataType: 'json', delay: 300,
                data: function (params) { var data = context(); data.q = params.term || ''; data.page = params.page || 1; return data; },
                transport: function (params, success, failure) { var xhr = $.ajax(params); xhr.done(success); xhr.fail(function (response) { if (response.statusText !== 'abort') error(response); failure(response); }); return xhr; }}});
        product.on('change', load);
        $(form).on('submit', function (event) { event.preventDefault(); load(); });
    });
}(jQuery));
