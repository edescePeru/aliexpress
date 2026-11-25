/* Read-only report: documents, exports and inventory mutations are not exposed. */
$(document).ready(function () {
    const text = $.fn.dataTable.render.text();
    let queried = false;
    const table = $('#dynamic-table').DataTable({
        data: [], autoWidth: false, order: [], pageLength: 10,
        dom: '<"next-list-toolbar d-flex flex-wrap justify-content-between"lf><"table-responsive"t><"next-list-pagination"ip>',
        columns: [
            {data: 'referral_guide', defaultContent: '', className: 'text-center', render: text},
            {data: 'purchase_order', defaultContent: '', className: 'text-center', render: text},
            {data: 'invoice', defaultContent: '', className: 'text-center', render: text},
            {data: 'entry_type', render: text},
            {data: 'supplier', render: function (supplier, type) {
                const value = supplier ? supplier.business_name : 'Sin proveedor';
                return type === 'display' ? text.display(value) : value;
            }},
            {data: 'date_entry', className: 'text-center', render: function (value, type) {
                return type === 'display' ? (value ? moment(value).format('DD/MM/YYYY') : 'Sin fecha') : value;
            }},
            {data: 'deferred_invoice', className: 'text-center', render: function (value, type) {
                const label = value === 'off' ? 'NO' : 'SI';
                return type === 'display' ? '<span class="badge badge-light">' + label + '</span>' : label;
            }},
            {data: 'total', className: 'text-right', render: text},
            {data: 'currency_invoice', className: 'text-center', render: text},
            {data: null, className: 'text-center', orderable: false, searchable: false, render: function (row) { return EntryInvoiceOperations.render(row, 'report'); }}
        ],
        language: {
            lengthMenu: 'Mostrar _MENU_', search: 'Buscar en resultados:',
            zeroRecords: 'No se encontraron coincidencias.', emptyTable: 'Sin resultados para mostrar.',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ entradas', infoEmpty: '0 entradas',
            infoFiltered: '(de _MAX_ entradas)',
            paginate: {first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior'}
        }
    });
    $('#dynamic-table_filter').addClass('mw-100').find('label').addClass('d-flex flex-wrap align-items-center mb-0');
    $('#dynamic-table_filter input').addClass('ml-0 mw-100');
    $('#dynamic-table').closest('.table-responsive').attr({'tabindex': '0', 'aria-label': 'Resultados; desplazamiento horizontal local'});
    $('#sandbox-container .input-daterange').datepicker({
        format: 'dd/mm/yyyy', todayBtn: 'linked', clearBtn: true,
        language: 'es', multidate: false, autoclose: true, orientation: 'bottom auto', container: 'body'
    });
    function error(message) {
        $('#report-error').text(message).removeClass('d-none');
    }
    $('#myForm').on('submit', function (event) {
        event.preventDefault();
        const $button = $('#submitButton');
        if ($button.prop('disabled')) return;
        const start = $('#start').val();
        const end = $('#end').val();
        $('#report-error').addClass('d-none').empty();
        if (!start || !end) { error('Selecciona ambas fechas (dd/mm/aaaa).'); return; }
        if (moment(end, 'DD/MM/YYYY').diff(moment(start, 'DD/MM/YYYY'), 'months') >= 6) {
            toastr.warning('El rango seleccionado es mayor a 6 meses, puede demorar en cargar.', 'Reporte de entradas');
        }
        $button.prop('disabled', true);
        $('#element_loader').attr('aria-busy', 'true');
        $('#report-status').text('Consultando entradas…');
        $.ajax({
            type: 'POST', url: '/dashboard/get/json/entries/purchase/report',
            data: {_token: $('input[name=_token]').val(), start: start, end: end},
            success: function (response) {
                if (!response || !Array.isArray(response.data)) {
                    error('El servidor devolvió una respuesta inesperada.');
                    $('#report-status').text('Consulta fallida. Los resultados anteriores no se han actualizado.');
                    return;
                }
                table.clear().rows.add(response.data).draw();
                queried = true;
                $('#report-status').text(response.data.length ? response.data.length + ' entradas · Empresa activa' : 'No se encontraron entradas en este rango.');
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                let message = 'No se pudo cargar el reporte. Intenta nuevamente.';
                if (xhr.status === 403) message = 'No tienes permiso o un contexto válido para consultar este reporte.';
                else if (xhr.status === 422) message = Object.values(response.errors || {}).flat().join(' ') || response.message || 'Revisa el rango de fechas (dd/mm/aaaa).';
                else if (xhr.status === 419) message = 'La sesión expiró. Recarga la página antes de consultar.';
                error(message);
                $('#report-status').text(queried ? 'Consulta fallida. Se conservan los resultados de la consulta anterior.' : 'Consulta fallida; no hay resultados cargados.');
            },
            complete: function () {
                $('#element_loader').removeAttr('aria-busy');
                $button.prop('disabled', false);
            }
        });
    });
});
