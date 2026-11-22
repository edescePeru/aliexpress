let kardexRequest = null;
let kardexRequestVersion = 0;

function kardexState(message, isError) {
    $('#kardex-body').empty().append(
        $('<tr>').append($('<td>', {
            colspan: 10, class: 'text-center py-4' + (isError ? ' text-danger' : ''),
            role: isError ? 'alert' : 'status'
        }).text(message))
    );
}

$(function () {
    $('#form-kardex').on('submit', function (event) {
        event.preventDefault();
        $('#btn-search-kardex').trigger('click');
    });

    $('#stock_item_id').select2({
        placeholder: 'Busque y seleccione un producto...',
        allowClear: true,
        width: 'resolve',
        ajax: {
            url: '/dashboard/stock-items/select',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });

    $('#warehouse_id').select2({
        placeholder: 'Seleccione un almacén...',
        allowClear: true,
        width: 'resolve',
        ajax: {
            url: '/dashboard/warehouses/select',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('#stock_item_id, #warehouse_id').on('change', function () {
        kardexRequestVersion++;
        if (kardexRequest) kardexRequest.abort();
        $('#kardex-header').prop('hidden', true);
        $('#kardex-table').attr('aria-busy', 'false');
        $('#kardex-count').text('Consulta pendiente');
        kardexState('Pulsa Buscar para consultar los filtros seleccionados.');
    });

    $('#btn-search-kardex').on('click', function () {
        let stockItemId = $('#stock_item_id').val();
        let warehouseId = $('#warehouse_id').val();

        if (!stockItemId) {
            $('#kardex-count').text('Selecciona un producto o variante');
            kardexState('Seleccione un producto o variante.');
            $('#stock_item_id').select2('open');
            return;
        }

        loadKardex(stockItemId, warehouseId);
    });
});

function money(simbolo, valor) {
    if (valor === null || valor === undefined || valor === '') {
        return '';
    }
    return simbolo + ' ' + parseFloat(valor).toFixed(2);
}

function loadKardex(stockItemId, warehouseId = null) {
    const version = ++kardexRequestVersion;
    if (kardexRequest) kardexRequest.abort();
    $('#kardex-header').prop('hidden', true);
    $('#kardex-count').text('Consultando movimientos…');
    $('#kardex-table').attr('aria-busy', 'true');
    kardexState('Cargando movimientos…');

    kardexRequest = $.getJSON('/dashboard/kardex/' + stockItemId, {
        warehouse_id: warehouseId
    }, function (res) {
        if (version !== kardexRequestVersion) return;
        let tbody = $('#kardex-body');
        tbody.empty();

        if (!res.rows || res.rows.length === 0) {
            kardexState('No se encontraron movimientos para el producto y almacén seleccionados.');
        } else {
            res.rows.forEach(function (r) {
                let origen = r.source_type + ' #' + r.source_id;

                if (r.source_type === 'transfer_dispatch' || r.source_type === 'transfer_receipt') {
                    const transfer = 'TR-' + String(r.source_id).padStart(6, '0');
                    origen = r.receipt_id ? transfer + ' · Recepción #' + r.receipt_id : transfer + ' · Despacho';
                }

                let tr = $('<tr>');
                tr.append($('<td>', {class: 'text-center text-nowrap'}).text(r.date || ''));
                tr.append($('<td>', {class: 'text-center'}).append(
                    $('<span>', {class: 'badge badge-light'}).text(r.type)
                ));
                tr.append($('<td>', {class: 'text-left text-nowrap'}).text(origen));
                ['qty_in', 'qty_out'].forEach(function (key) {
                    tr.append($('<td>', {class: 'text-center'}).text(r[key] == null ? '' : r[key]));
                });
                ['unit_cost_in', 'unit_cost_out'].forEach(function (key) {
                    tr.append($('<td>', {class: 'text-right text-nowrap'}).text(money(r.simbolo_moneda, r[key])));
                });
                tr.append($('<td>', {class: 'text-center'}).text(r.saldo_qty == null ? '' : r.saldo_qty));
                ['saldo_cost', 'saldo_total'].forEach(function (key) {
                    tr.append($('<td>', {class: 'text-right text-nowrap'}).text(money(r.simbolo_moneda, r[key])));
                });
                tbody.append(tr);
            });
        }

        $('#kardex-count').text((res.rows || []).length + ' movimientos');
        $('#kardex-header').prop('hidden', false);

        $('#kardex-material-name').text(
            res.stock_item_name || res.material_name || ('StockItem ID ' + res.stock_item_id)
        );

        $('#kardex-range').text(
            res.warehouse_name || 'General y almacenes del local actual'
        );

    }).fail(function (xhr, status) {
        if (status === 'abort' || version !== kardexRequestVersion) return;
        const errors = xhr.responseJSON && xhr.responseJSON.errors;
        const messages = {403: 'No tiene permiso o contexto autorizado para esta consulta.',
            404: 'El producto o almacén no existe o no está disponible en el contexto actual.',
            422: 'Revise los filtros de la consulta.', 401: 'Su sesión ha expirado. Inicie sesión nuevamente.',
            419: 'Su sesión ha expirado. Recargue la página.'};
        const firstError = errors && Object.keys(errors)[0];
        const message = firstError ? errors[firstError][0] : (messages[xhr.status] || 'Error al obtener el Kardex. Intente nuevamente.');
        $('#kardex-count').text('Consulta no disponible');
        kardexState(message, true);
    }).always(function () {
        if (version === kardexRequestVersion) $('#kardex-table').attr('aria-busy', 'false');
    });
}
