/* Read-only Entry contract. Never load legacy mutation/service handlers here. */
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
            {data: null, className: 'text-center', orderable: false, searchable: false, render: function (r) {return '<a class="btn btn-outline-secondary btn-sm" href="/dashboard/factura/compra/ver/' + encodeURIComponent(r.id) + '">Ver</a>';}}
        ]
    });
    $('.date-range-filter').on('change', function () {table.draw();});
});
