(function ($) {
    'use strict';
    $(function () {
        var form = document.getElementById('receipt-form');
        if (!form) return;
        var saving = false;
        $(form).on('submit', function (event) {
            event.preventDefault();
            if (saving || !form.reportValidity()) return;
            var items = [];
            var invalid = false;
            form.querySelectorAll('[data-receipt-line]').forEach(function (row) {
                var quantity = row.querySelector('[data-receipt-quantity]');
                if (quantity.disabled || Number(quantity.value) === 0) return;
                var warehouse = row.querySelector('[data-receipt-warehouse]');
                var option = warehouse.options[warehouse.selectedIndex];
                if (!warehouse.value || !option || !option.dataset.location) invalid = true;
                items.push({
                    order_purchase_detail_id: row.dataset.receiptLine,
                    entered: quantity.value,
                    warehouse_id: warehouse.value,
                    id_location: option ? option.dataset.location : '',
                    material_lote: row.querySelector('[data-receipt-lot]').value,
                    date_vence: row.querySelector('[data-receipt-expiration]').value
                });
            });
            var errors = $('#receipt-errors').addClass('d-none').empty();
            if (!items.length || invalid) {
                errors.text(!items.length ? 'Ingrese una cantidad mayor que cero.' : 'Seleccione un almacén válido para cada producto recibido.').removeClass('d-none');
                return;
            }
            var data = new FormData(form);
            data.set('items', JSON.stringify(items));
            var button = $('#receipt-save');
            saving = true;
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando');
            $.ajax({url: form.action, type: 'POST', data: data, processData: false, contentType: false,
                headers: {'Accept': 'application/json'}})
                .done(function (response) {
                    // Mantener bloqueado hasta abandonar esta recepción evita reenvíos accidentales.
                    var leave = function () { window.location.assign(response.url); };
                    if ($.confirm) {
                        $.confirm({title: 'Ingreso guardado', content: $('<div>').text(response.message).html(), type: 'green',
                            buttons: {aceptar: {text: 'Aceptar', btnClass: 'btn-success'}}, onClose: leave});
                    } else { window.alert(response.message); leave(); }
                })
                .fail(function (xhr) {
                    var response = xhr.responseJSON || {};
                    var messages = response.errors ? Object.keys(response.errors).reduce(function (all, key) {
                        return all.concat(response.errors[key]);
                    }, []) : [response.message || 'No se pudo confirmar el guardado. Puede reintentar esta misma recepción.'];
                    messages.forEach(function (message) { $('<div>').text(message).appendTo(errors); });
                    errors.removeClass('d-none');
                    saving = false;
                    button.prop('disabled', false).text('Guardar ingreso');
                });
        });
    });
})(jQuery);
