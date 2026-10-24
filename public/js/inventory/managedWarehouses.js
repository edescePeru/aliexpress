(function ($) {
    'use strict';
    $('.js-managed-warehouses').each(function () {
        var root = $(this), url = root.data('url'), rows = [], busy = false;
        var form = root.find('.js-warehouse-form');
        function notice(title, message, type) {
            $.alert({title: title, content: $('<div>').text(message).html(), type: type});
        }
        function failure(xhr) {
            var body = xhr.responseJSON || {}, message = 'No se pudo completar la operación. Actualice la lista antes de reintentar.';
            if (body.errors) {
                var keys = Object.keys(body.errors);
                if (keys.length) { message = body.errors[keys[0]][0]; }
            } else if (body.message && xhr.status < 500) { message = body.message; }
            notice('Revise la configuración', message, 'red');
        }
        function reset() {
            form[0].reset(); form.find('[name=warehouse_id]').val('');
            form.find('[type=submit]').text('Crear almacén');
        }
        function render(data) {
            rows = data.warehouses;
            root.find('.js-warehouse-warning').text(data.configuration_error || '').prop('hidden', !data.configuration_error);
            var tbody = root.find('.js-warehouse-rows').empty();
            var select = root.find('.js-warehouse-preference select').empty();
            select.append($('<option>').val('').text('Usar general predeterminado'));
            rows.forEach(function (w) {
                var tr = $('<tr>'), actions = $('<td>');
                tr.append($('<td>').text(w.name), $('<td>').text(w.comment || ''));
                var description = w.locations_count === 1 ? 'Una ubicación' : 'Revisar: ' + w.locations_count + ' ubicaciones';
                if (w.is_default) { description += ' · Predeterminado general'; }
                tr.append($('<td>').text(description));
                actions.append($('<button type="button" class="btn btn-outline-primary btn-sm mr-2">').text('Editar').on('click', function () {
                    form.find('[name=warehouse_id]').val(w.id);
                    form.find('[name=name]').val(w.name).trigger('focus');
                    form.find('[name=comment]').val(w.comment || '');
                    form.find('[type=submit]').text('Guardar cambios');
                }));
                if (!data.is_branch && (!w.is_default || data.configuration_error) && w.locations_count === 1) {
                    actions.append($('<button type="button" class="btn btn-outline-secondary btn-sm">').text('Usar como predeterminado').on('click', function () {
                        save($(this), {action: 'default', warehouse_id: w.id});
                    }));
                }
                tr.append(actions); tbody.append(tr);
                if (data.is_branch) {
                    select.append($('<option>').val(w.id).text(w.name).prop('disabled', w.locations_count !== 1));
                }
            });
            if (data.is_branch) {
                var selected = data.default_entry_warehouse_id;
                if (selected && !rows.some(function (w) { return w.id === selected; })) {
                    select.append($('<option>').val(selected).text('Preferencia inválida: seleccione otro destino').prop('disabled', true));
                }
                select.val(selected || '');
            }
            root.find('.js-warehouse-status').text(rows.length ? rows.length + ' almacén(es).' : 'Todavía no hay almacenes en este ámbito.');
        }
        function load() {
            root.find('button, select').prop('disabled', true);
            return $.ajax({url: url, dataType: 'json', headers: {Accept: 'application/json'}})
                .done(function (data) { render(data); root.find('button, select').prop('disabled', false); })
                .fail(function (xhr) {
                    root.find('.js-warehouse-status').text('No se pudo actualizar la lista.');
                    root.find('.js-warehouse-reload').prop('disabled', false);
                    failure(xhr);
                });
        }
        function save(button, data) {
            if (busy) { return; }
            busy = true;
            data._token = form.find('[name=_token]').val();
            var label = button.html();
            root.find('button, select').prop('disabled', true);
            button.html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando…');
            $.ajax({url: url, method: 'POST', data: data, dataType: 'json', headers: {Accept: 'application/json'}})
                .done(function (result) {
                    reset(); notice('Configuración guardada', result.message, 'green');
                }).fail(failure).always(function () {
                    button.html(label);
                    if (!form.find('[name=warehouse_id]').val()) { form.find('[type=submit]').text('Crear almacén'); }
                    busy = false;
                    load();
                });
        }
        form.on('submit', function (event) {
            event.preventDefault();
            save(form.find('[type=submit]'), {action: 'save', warehouse_id: form.find('[name=warehouse_id]').val(),
                name: form.find('[name=name]').val(), comment: form.find('[name=comment]').val()});
        });
        root.find('.js-warehouse-preference').on('submit', function (event) {
            event.preventDefault();
            save($(this).find('[type=submit]'), {action: 'preference', warehouse_id: $(this).find('select').val()});
        });
        root.find('.js-warehouse-cancel').on('click', reset);
        root.find('.js-warehouse-reload').on('click', load);
        load();
    });
})(jQuery);
