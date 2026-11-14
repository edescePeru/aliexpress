(function ($) {
    'use strict';
    var node = document.getElementById('material-inventory-context');
    if (!node) return;
    var headers = {}, currentId = null, generation = 0;
    ['Tenant', 'Company', 'Branch'].forEach(function (key) {
        headers['X-Material-Inventory-' + key] = node.getAttribute('data-' + key.toLowerCase());
    });
    function escape(value) { return $('<div>').text(value == null ? '' : String(value)).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    function error(xhr) {
        var body = xhr.responseJSON || {}, messages = [];
        Object.keys(body.errors || {}).forEach(function (key) { messages = messages.concat(body.errors[key]); });
        return escape(messages.length ? messages.join(' ') : body.message || 'No se pudo completar la operación.');
    }
    function rowHtml(p) {
        var values = {
            id: Number(p.id), label: escape(p.label || 'Paquete de ' + p.quantity), quantity: Number(p.quantity), price: p.price === null ? '' : escape(p.price),
            currency: escape(p.currency),
            active: p.active ? 1 : 0, row_class: p.active ? '' : 'bg-light text-muted',
            badge_class: p.active ? 'badge-success' : 'badge-secondary',
            status_text: !p.definition_active ? 'DEFINICIÓN INACTIVA / INVÁLIDA' :
                !p.configured ? 'SIN PRECIO EN ESTE LOCAL' : p.active ? 'ACTIVA EN ESTE LOCAL' : 'INACTIVA EN ESTE LOCAL',
            toggle_text: p.active ? 'Desactivar' : 'Activar',
            toggle_btn_class: p.active ? 'btn-outline-danger' : 'btn-outline-success',
            edit_disabled: p.definition_active ? '' : 'disabled',
            toggle_disabled: p.definition_active && p.configured ? '' : 'disabled'
        };
        return $('#tpl-mp-row').html().replace(/\{([a-z_]+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(values, key) ? values[key] : match;
        });
    }
    function load(id) {
        var sequence = ++generation;
        return $.ajax({url: '/dashboard/materials-presentations/material/' + id + '/presentations/configuration', headers: headers})
            .done(function (res) {
                if (currentId !== id || sequence !== generation) return;
                $('#mp-material-title').text('— ' + (res.material.description || ''));
                $('#presentaciones-content').html($('#tpl-mp-wrapper').html());
                $('#mp-branch-name').text(res.branch.name);
                $('#mp-branch-currency').text(res.currency);
                $('[data-mp-currency]').text(res.currency);
                var rows = res.presentations || [];
                $('#mp-list').html(rows.length ? rows.map(rowHtml).join('') :
                    '<div class="alert alert-light border" role="status">No hay presentaciones definidas para este material. Habilitar una presentación registra su oferta en el local actual.</div>');
            }).fail(function (xhr) {
                if (sequence !== generation) return;
                $('#presentaciones-content').html('<div class="text-danger">' + error(xhr) + '</div>');
            });
    }
    function send(button, options) {
        var $button = $(button), previous = $button.html(), material = currentId;
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando...');
        options.headers = headers;
        return $.ajax(options).done(function (res) {
            if (material === currentId) load(material);
            $.alert({title: 'Guardado', content: escape(res.message)});
        }).fail(function (xhr) {
            $.alert({title: 'No se pudo guardar', content: error(xhr)});
        }).always(function () { $button.prop('disabled', false).html(previous); });
    }
    function validLabel(label) { return typeof label === 'string' && label.trim().length > 0 && Array.from(label.trim()).length <= 255 && !/[\x00-\x1f\x7f]/.test(label); }
    function validPrice(price) { return /^\d{1,12}(?:\.\d{1,2})?$/.test(String(price)); }
    $(document).on('click', '[data-manage_presentations]', function () {
        currentId = Number($(this).data('material'));
        $('#mp-material-title').text('— ' + ($(this).data('description') || ''));
        $('#presentaciones-content').html('<div class="text-muted">Cargando...</div>');
        $('#modalPresentaciones').modal('show');
        load(currentId);
    });
    $(document).on('click', '[data-mp-refresh]', function () { if (currentId) load(currentId); });
    $(document).on('click', '[data-mp-create]', function () {
        var quantity = $('[data-mp-new-quantity]').val(), price = $('[data-mp-new-price]').val(), label = $('[data-mp-new-label]').val();
        if (!/^[1-9][0-9]{0,8}$/.test(String(quantity)) || !validPrice(price) || !validLabel(label)) {
            return $.alert({title: 'Validación', content: 'Ingrese un nombre de hasta 255 caracteres, unidades enteras y un precio con hasta dos decimales.'});
        }
        send(this, {url: '/dashboard/materials-presentations/material/' + currentId + '/presentations',
            method: 'POST', data: {quantity: quantity, price: price, label: label.trim()}});
    });
    $(document).on('click', '[data-mp-edit]', function () {
        var $row = $(this).closest('[data-mp-row]');
        $row.data('originalPrice', $row.find('[data-mp-price]').val());
        $row.data('originalLabel', $row.find('[data-mp-label]').val());
        $row.find('[data-mp-label]').prop('disabled', false);
        $row.find('[data-mp-price]').prop('disabled', false).trigger('focus');
        $row.find('[data-mp-edit],[data-mp-toggle]').addClass('d-none');
        $row.find('[data-mp-save],[data-mp-cancel]').removeClass('d-none');
    });
    $(document).on('click', '[data-mp-cancel]', function () {
        var $row = $(this).closest('[data-mp-row]');
        $row.find('[data-mp-price]').val($row.data('originalPrice')).prop('disabled', true);
        $row.find('[data-mp-label]').val($row.data('originalLabel')).prop('disabled', true);
        $row.find('[data-mp-edit],[data-mp-toggle]').removeClass('d-none');
        $row.find('[data-mp-save],[data-mp-cancel]').addClass('d-none');
    });
    $(document).on('click', '[data-mp-save]', function () {
        var $row = $(this).closest('[data-mp-row]'), price = $row.find('[data-mp-price]').val(), label = $row.find('[data-mp-label]').val();
        if (!validPrice(price) || !validLabel(label)) return $.alert({title: 'Validación', content: 'Ingrese un nombre de hasta 255 caracteres y un precio con hasta dos decimales.'});
        send(this, {url: '/dashboard/materials-presentations/presentation/' + Number($row.data('id')),
            method: 'PUT', data: {quantity: $row.find('[data-mp-quantity]').val(), price: price, label: label.trim(), label_original: $row.data('originalLabel')}});
    });
    $(document).on('click', '[data-mp-toggle]', function () {
        var $row = $(this).closest('[data-mp-row]');
        send(this, {url: '/dashboard/materials-presentations/presentation/' + Number($row.data('id')) + '/toggle', method: 'PATCH'});
    });
})(window.jQuery);
