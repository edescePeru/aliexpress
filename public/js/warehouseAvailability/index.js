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
                var section = $('<section>').attr('aria-labelledby', 'wa-group-' + index).attr('data-group-key', group.key);
                var header = $('<div class="next-list-summary">');
                header.append($('<h2 class="h6 mb-0">').attr('id', 'wa-group-' + index).text(group.title));
                header.append($('<span class="badge badge-light">').text(group.current ? 'Local actual' : (group.key === 'general' ? 'Almacén general' : 'Otro local')));
                section.append(header);
                var scroll = $('<div class="table-responsive" tabindex="0">').attr('aria-label', group.title + '; desplazamiento horizontal');
                var table = $('<table class="table table-bordered table-hover table-sm next-data-table">');
                table.append('<thead><tr><th>Almacén</th><th class="text-center">Existencias</th><th class="text-center">Reservado</th><th class="text-center">Disponible</th><th class="text-center">Por recibir</th><th class="text-center">Enviado en tránsito</th><th class="text-center">Trazabilidad</th></tr></thead>');
                var body = $('<tbody>');
                if (!group.warehouses.length) body.append('<tr><td colspan="7" class="next-table-empty">Sin almacenes visibles en este grupo.</td></tr>');
                group.warehouses.forEach(function (warehouse) {
                    var row = $('<tr>').attr('data-warehouse-id', warehouse.id).attr('data-stock-item-id', data.product.id);
                    row.append($('<td>').text(warehouse.name));
                    ['on_hand', 'reserved', 'available', 'incoming', 'outgoing'].forEach(function (field) {
                        row.append($('<td class="text-center">').toggleClass('font-weight-bold', field === 'available').text(quantity(warehouse[field])));
                    });
                    row.append($('<td class="text-center">').append($('<span class="badge">').addClass(warehouse.requires_review ? 'badge-warning' : 'badge-success').text(warehouse.requires_review ? 'Requiere revisión' : 'Consistente')));
                    body.append(row);
                });
                table.append(body); scroll.append(table); section.append(scroll); target.append(section);
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
