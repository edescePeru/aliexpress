(function () {
    'use strict';
    const root = document.getElementById('finance-orders');
    if (!root) return;
    const permissions = JSON.parse(document.getElementById('permissions').value);
    const escape = value => $('<div>').text(value == null || value === '' ? '—' : String(value)).html();
    const text = getter => (row, type) => {
        const value = getter(row);
        return type === 'display' ? escape(value) : (value == null ? '' : value);
    };
    const date = field => (row, type) => {
        const raw = row[field] ? String(row[field]).slice(0, 10) : '';
        return type === 'display' ? escape(raw ? raw.split('-').reverse().join('-') : null) : raw;
    };
    $.fn.dataTable.ext.errMode = 'none';
    $('#dynamic-table').DataTable({
        autoWidth: false, pageLength: 10, order: [],
        ajax: {url: root.dataset.url, dataSrc: 'data', error: function (xhr) {
            $('#finance-empty').addClass('d-none');
            $('#finance-error').removeClass('d-none').text('No se pudieron consultar las órdenes (HTTP ' + xhr.status + ').');
        }},
        drawCallback: function () {
            const api = this.api();
            $('#finance-empty').toggleClass('d-none', api.rows({search: 'applied'}).count() !== 0)
                .text(api.rows().count() ? 'No hay órdenes que coincidan con la búsqueda.' : 'No hay órdenes de compra financieras registradas en esta empresa.');
        },
        columns: [
            {data: null, className: 'text-center', render: text(r => r.id)},
            {data: null, className: 'text-center', render: text(r => r.code)},
            {data: null, className: 'text-center', render: date('date_order')},
            {data: null, className: 'text-center', render: date('date_delivery')},
            {data: null, render: text(r => r.observation)},
            {data: null, render: text(r => r.supplier ? r.supplier.business_name : 'Sin proveedor')},
            {data: null, render: text(r => r.approved_user ? r.approved_user.name : 'Sin aprobar')},
            {data: null, className: 'text-center', render: text(r => r.currency_order)},
            {data: null, className: 'text-right', render: function (row, type) {
                // Preserve the existing role-permission presentation contract.
                return permissions.includes('showPrices_quote') ? text(r => r.total)(row, type) : '';
            }},
            {data: null, className: 'text-center', render: function (row, type) {
                const label = {r: 'Regularizada', nr: 'No regularizada'}[row.regularize] || row.regularize;
                return type === 'display' ? '<span class="badge badge-' + (row.regularize === 'r' ? 'success' : 'secondary') + '">' + escape(label) + '</span>' : (label || '');
            }}
        ],
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_', emptyTable: 'No hay órdenes registradas.',
            zeroRecords: 'No se encontraron resultados', info: 'Mostrando _START_ a _END_ de _TOTAL_ órdenes',
            infoEmpty: '0 órdenes', infoFiltered: '(de _MAX_ órdenes)', loadingRecords: 'Cargando órdenes…',
            paginate: {first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior'}
        }
    });
})();
