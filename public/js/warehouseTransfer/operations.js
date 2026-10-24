/* global jQuery */
(function ($) {
    'use strict';
    $(function () {
        var busy = false;
        var create = document.getElementById('wt-create');
        var nextRow = 0;

        function message(text) {
            $('#wt-errors').removeClass('d-none').text(text);
            document.getElementById('wt-errors').scrollIntoView({block: 'nearest'});
        }
        function clearErrors() { $('#wt-errors').addClass('d-none').empty(); }
        function confirm(text, action, cancel) {
            if ($.confirm) {
                $.confirm({title: 'Confirmar operación', content: $('<div>').text(text).html(),
                    buttons: {confirmar: {text: 'Confirmar', btnClass: 'btn-primary', action: action},
                        cancelar: {text: 'Volver', action: cancel || function () {}}}});
            } else if (window.confirm(text)) { action(); }
            else if (cancel) { cancel(); }
        }
        function submit(form, data, button) {
            if (busy) return;
            busy = true; clearErrors();
            var controls = $('form').find('input,select,textarea,button').toArray().map(function (control) {
                return {element: control, disabled: control.disabled};
            });
            controls.forEach(function (control) { control.element.disabled = true; });
            var original = $(button).html();
            $(button).html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando').attr('aria-busy', 'true');
            $.ajax({url: $(form).data('url'), type: 'POST', data: data, processData: false, contentType: false,
                dataType: 'json', headers: {'Accept': 'application/json'}})
                .done(function (response) {
                    var leave = function () { window.location.assign(response.url); };
                    if ($.alert) $.alert({title: 'Operación completada', content: $('<div>').text(response.message).html(), type: 'green', onClose: leave});
                    else { window.alert(response.message); leave(); }
                })
                .fail(function (xhr) {
                    var response = xhr.responseJSON || {};
                    var errors = response.errors ? Object.keys(response.errors).reduce(function (all, key) { return all.concat(response.errors[key]); }, []) : [];
                    var fallback = xhr.status === 419 ? 'La sesión venció. Recargue la página.' : 'No se pudo confirmar la operación. Reintente desde esta página; se conservará la misma clave.';
                    message(errors.length ? errors.join(' ') : (response.message || fallback));
                    busy = false;
                    controls.forEach(function (control) { control.element.disabled = control.disabled; });
                    $(button).html(original).removeAttr('aria-busy');
                });
        }

        if (create) {
            var product = $('#wt-product');
            var source = $('#source_warehouse_id');
            var previousSource = source.val();
            product.select2({theme: 'bootstrap4', width: '100%', placeholder: 'Buscar por producto, variante, SKU o código de barras', minimumInputLength: 1,
                ajax: {url: $(create).data('products-url'), dataType: 'json', delay: 300,
                    data: function (params) { return {q: params.term || '', page: params.page || 1,
                        source_warehouse_id: source.val(), form_tenant_id: $(create).find('[name=form_tenant_id]').val(),
                        form_company_id: $(create).find('[name=form_company_id]').val(), form_branch_id: $(create).find('[name=form_branch_id]').val()}; },
                    transport: function (params, success, failure) {
                        var origin = source.val();
                        var request = $.ajax(params);
                        request.done(function (data) { success(origin === source.val() ? data : {results: [], pagination: {more: false}}); });
                        request.fail(function (xhr) { if (xhr.statusText !== 'abort') message((xhr.responseJSON || {}).message || 'No se pudieron consultar los productos. Recargue si cambió de empresa o local.'); failure(xhr); });
                        return request;
                    }},
                templateResult: function (item) { return item.id ? $('<span>').text(item.text + ' · Disponible: ' + item.available) : item.text; }
            });
            source.on('change', function () {
                if (busy) return;
                var selected = source.val();
                var change = function () {
                    $('#wt-products tr[data-stock-id]').remove(); $('#wt-empty').show();
                    product.val(null).trigger('change').select2('close').prop('disabled', !selected);
                    previousSource = selected;
                };
                if ($('#wt-products tr[data-stock-id]').length) {
                    confirm('Al cambiar el origen se quitarán los productos de esta solicitud. ¿Continuar?', change, function () { source.val(previousSource); });
                } else { change(); }
            });
            function addProduct(item) {
                if ($('#wt-products tr[data-stock-id="' + Number(item.id) + '"]').length) { message('El producto ya está en la solicitud. Modifique su cantidad.'); product.val(null).trigger('change'); return; }
                clearErrors(); $('#wt-empty').hide();
                var row = $('<tr>').attr('data-stock-id', item.id);
                row.append($('<td>').text(item.text)); row.append($('<td class="text-center">').text(item.available));
                var inputId = 'wt-requested-' + nextRow++;
                row.append($('<td class="text-center">').append($('<label class="sr-only">').attr('for', inputId).text('Cantidad solicitada de ' + item.text))
                    .append($('<input class="form-control text-center wt-requested" type="number" required>').attr({id: inputId, min: item.tracks_items ? '1' : '0.01', step: item.tracks_items ? '1' : '0.01', max: '9999999999.99'}).val('1')));
                row.append($('<td class="text-center">').append($('<button type="button" class="btn btn-outline-danger btn-sm wt-remove">').text('Quitar')));
                $('#wt-products').append(row); product.val(null).trigger('change');
            }
            product.on('select2:select', function (event) { addProduct(event.params.data); });
            var prefill = $(create).data('prefill');
            if (prefill && prefill.product) {
                source.val(String(prefill.source_warehouse_id)).trigger('change');
                addProduct(prefill.product);
            }
            $('#wt-products').on('click', '.wt-remove', function () { if (!busy) { $(this).closest('tr').remove(); if (!$('#wt-products tr[data-stock-id]').length) $('#wt-empty').show(); } });
            $(create).on('submit', function (event) {
                event.preventDefault(); if (busy || !create.reportValidity()) return;
                if (source.val() === $('#destination_warehouse_id').val()) { message('Origen y destino deben ser diferentes.'); return; }
                var rows = $('#wt-products tr[data-stock-id]');
                if (!rows.length) { message('Agregue al menos un producto.'); return; }
                var data = new FormData(create);
                rows.each(function (index) { data.append('lines[' + index + '][stock_item_id]', $(this).attr('data-stock-id')); data.append('lines[' + index + '][requested_quantity]', $(this).find('.wt-requested').val()); });
                submit(create, data, create.querySelector('[type=submit]'));
            });
        }

        $('.wt-operation, #wt-receive').on('submit', function (event) {
            event.preventDefault(); var form = this;
            if (busy || !form.reportValidity()) return;
            var data = new FormData(form); var index = 0;
            if (form.id === 'wt-receive') {
                $(form).find('.wt-receive-quantity:not(:disabled)').each(function () {
                    if (Number(this.value) > 0) { data.append('lines[' + index + '][transfer_line_id]', $(this).attr('data-line-id')); data.append('lines[' + index + '][quantity]', this.value); index++; }
                });
                if (!index) { message('Indique al menos una cantidad recibida mayor que cero.'); return; }
            }
            confirm($(form).data('confirm'), function () { submit(form, data, form.querySelector('[type=submit]')); });
        });
    });
}(jQuery));
