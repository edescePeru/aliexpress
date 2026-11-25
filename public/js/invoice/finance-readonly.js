/* Invoice list contract with R4 operations; no OrderService handlers. */
$(function () {
    var tableNode = $('#dynamic-table');
    var text = $.fn.dataTable.render.text();
    function safe(value) { return text.display(value == null ? '—' : String(value)); }
    $('#sandbox-container .input-daterange').datepicker({todayBtn: 'linked', clearBtn: true, language: 'es', multidate: false, autoclose: true, orientation: 'bottom', container: '.wrapper'});
    $.fn.dataTable.ext.search.push(function (settings, data) {
        if (settings.nTable !== tableNode[0]) return true;
        var min = $('#start').val(), max = $('#end').val();
        return !min || !max || moment(data[0], 'DD/MM/YYYY').isBetween(moment(min, 'DD/MM/YYYY'), moment(max, 'DD/MM/YYYY'), null, '[]');
    });
    var table = tableNode.DataTable({
        ajax: {url: tableNode.attr('data-read-url'), dataSrc: 'data', error: function () { $('#invoice-read-error').removeClass('d-none').text('No se pudo cargar el listado. Recargue la página para volver a intentar.'); }},
        autoWidth: false, order: [], pageLength: 10,
        drawCallback: function () {
            if (!$('#invoice-report-totals').length) return;
            var pen = 0, usd = 0, equivalent = 0, valid = true;
            this.api().rows({search:'applied'}).data().each(function (row) {
                var amount = Number(row.total), rate = Number(row.currency_venta);
                if (!Number.isFinite(amount)) { valid = false; return; }
                if (row.currency_invoice === 'PEN') { pen += amount; equivalent += amount; }
                else if (row.currency_invoice === 'USD') {
                    usd += amount;
                    if (!Number.isFinite(rate) || rate <= 0) valid = false;
                    else equivalent += amount * rate;
                } else valid = false;
            });
            $('#invoice-total-pen').text(pen.toFixed(2));
            $('#invoice-total-usd').text(usd.toFixed(2));
            $('#invoice-total-equivalent').text(valid ? equivalent.toFixed(2) : 'No disponible: revisar moneda/tipo de cambio');
        },
        dom: '<"next-list-toolbar"<"next-list-search-row"<"next-list-search-control"f><"next-list-length"l>>><"next-list-summary text-wrap"i><"next-list-content"<"table-responsive"t>><"next-list-pagination"p>',
        language: {search: 'Buscar:', lengthMenu: 'Mostrar _MENU_', info: 'Mostrando _START_ a _END_ de _TOTAL_ facturas', infoEmpty: '0 facturas', infoFiltered: '(de _MAX_)', emptyTable: 'No hay facturas en esta consulta.', zeroRecords: 'No se encontraron coincidencias.', loadingRecords: 'Cargando…', paginate: {previous: 'Anterior', next: 'Siguiente'}},
        columns: [
            {data: null, className: 'text-center', render: function (r, type) {var date = r.date_entry || r.date_invoice; return type === 'display' || type === 'filter' ? (date ? moment(date).format('DD/MM/YYYY') : '—') : date;}},
            {data: 'invoice', className: 'text-center', render: safe},
            {data: 'purchase_order', className: 'text-center', render: safe},
            {data: null, render: function (r) {return r.type_order === 'service' ? 'Por servicio' : 'Por compra';}},
            {data: null, render: function (r) {return safe(r.supplier ? r.supplier.business_name : 'Sin proveedor');}},
            {data: null, className: 'text-center', render: function (r) {return r.deferred_invoice === 'off' ? 'No' : 'Sí';}},
            {data: null, render: function (r) {return safe(r.category_invoice ? r.category_invoice.name : 'Sin categoría');}},
            {data: 'currency_invoice', className: 'text-center', render: safe},
            {data: 'sub_total', className: 'text-right', render: safe},
            {data: 'taxes', className: 'text-right', render: safe},
            {data: 'total', className: 'text-right', render: safe},
            {data: null, className: 'text-center', render: function (r) {return Number(r.state_annulled) ? '<span class="badge badge-danger">Anulada</span>' : '<span class="badge badge-success">Activa</span>';}},
            {data: null, className: 'text-center', orderable: false, searchable: false, render: function (r) {return EntryInvoiceOperations.render(r, tableNode.attr('data-operation-mode'));}}
        ]
    });
    $(document).on('entry-invoice:refresh', function () { table.ajax.reload(null, false); });
    $('.date-range-filter').on('change', function () {table.draw();});
});
