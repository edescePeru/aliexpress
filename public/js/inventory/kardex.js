$(function () {

    // 1) Inicializar select de materiales (Select2 con AJAX)
    $('#material_id').select2({
        placeholder: 'Busque y seleccione un material...',
        allowClear: true,
        width: 'resolve',
        ajax: {
            url: '/dashboard/materials/select',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // término de búsqueda
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

    // 2) Click en Buscar Kardex
    $('#btn-search-kardex').on('click', function () {
        let materialId = $('#material_id').val();
        let from = $('#from_date').val();
        let to   = $('#to_date').val();

        if (!materialId) {
            alert('Seleccione un material.');
            return;
        }

        loadKardex(materialId, from, to);
    });
});

function money(simbolo, valor) {
    if (valor === null || valor === undefined || valor === '' || Number(valor) === 0) {
        return '';
    }
    return simbolo + ' ' + parseFloat(valor).toFixed(2);
}

function loadKardex(materialId, from, to) {
    $('#kardex-body').html(
        '<tr><td colspan="10" class="text-center">Cargando...</td></tr>'
    );

    $.getJSON('/dashboard/kardex/' + materialId, { from: from, to: to }, function (res) {
        let tbody = $('#kardex-body');
        tbody.empty();

        if (!res.rows || res.rows.length === 0) {
            tbody.append(
                '<tr><td colspan="10" class="text-center">No se encontraron movimientos para el rango seleccionado.</td></tr>'
            );
        } else {
            res.rows.forEach(function (r) {
                let origen = r.source_type + ' #' + r.source_id;

                let tr = $('<tr>');
                tr.append('<td>' + (r.date || '') + '</td>');
                tr.append('<td>' + r.type + '</td>');
                tr.append('<td>' + origen + '</td>');
                tr.append('<td class="text-right">' + (r.qty_in || '') + '</td>');
                tr.append('<td class="text-right">' + (r.qty_out || '') + '</td>');
                tr.append('<td class="text-right">' + money(r.simbolo_moneda, r.unit_cost_in) + '</td>');
                tr.append('<td class="text-right">' + money(r.simbolo_moneda, r.unit_cost_out) + '</td>');
                tr.append('<td class="text-right">' + (r.saldo_qty || 0) + '</td>');
                tr.append('<td class="text-right">' + money(r.simbolo_moneda, r.saldo_cost) + '</td>');
                tr.append('<td class="text-right">' + money(r.simbolo_moneda, r.saldo_total) + '</td>');
                tbody.append(tr);
            });
        }

        // Encabezado info material
        $('#kardex-header').show();
        $('#kardex-material-name').text(res.material_name || ('ID ' + res.material_id));
        let rangeText = (from || 'Inicio') + ' al ' + (to || 'Hoy');
        $('#kardex-range').text(rangeText);
    }).fail(function () {
        $('#kardex-body').html(
            '<tr><td colspan="10" class="text-center text-danger">Error al obtener el Kardex.</td></tr>'
        );
    });
}