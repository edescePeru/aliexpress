/* Physical Items read path; no item-code mutation handler is loaded. */
(function ($) {
    'use strict';
    const text = value => $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html();
    function state(value) {
        const labels = {entered: 'Ingresado', scraped: 'Retazo', reserved: 'Reservado'};
        return '<span class="badge badge-secondary">' + text(labels[value] || value) + '</span><small class="text-muted d-block">' + text(value) + '</small>';
    }
    function detail(item) {
        const location = item.location || {};
        const parts = [['Estado físico', item.state], ['Estado de inventario', item.state_item], ['Tipo de retacería', item.typescrap && item.typescrap.name], ['Ubicación', location.description], ['Área', location.area && location.area.name], ['Almacén', location.warehouse && location.warehouse.name], ['Estante', location.shelf && location.shelf.name], ['Nivel', location.level && location.level.name], ['Contenedor', location.container && location.container.name], ['Posición', location.position && location.position.name]];
        return '<div class="next-form-section"><dl class="row mb-0">' + parts.map(function (part) { return '<dt class="col-sm-4">' + part[0] + '</dt><dd class="col-sm-8 text-break">' + text(part[1]) + '</dd>'; }).join('') + '</dl></div>';
    }
    $(function () {
        const $table = $('#dynamic-table');
        $table.on('xhr.dt', function (event, settings, data, xhr) {
            if (data && !data.error) { $('#items-read-error').addClass('d-none').empty(); return; }
            const response = xhr.responseJSON || {};
            const errors = response.errors && Object.values(response.errors);
            // A rejected snapshot must not leave the previous context's rows visible.
            $table.find('tbody').empty();
            $('#dynamic-table_info, #dynamic-table_paginate').empty();
            $('#items-read-error').removeClass('d-none').text(errors && errors.length ? [].concat(errors[0])[0] : response.message || 'No se pudo consultar los Items. Recargue la página para actualizar el contexto.');
            return true;
        });
        const table = $table.DataTable({
            processing: true, serverSide: true,
            ajax: {url: window.stockItemItemsDataUrl, type: 'GET'},
            autoWidth: false, pageLength: 25, lengthMenu: [10, 25, 50, 100], deferRender: true, rowId: 'id',
            dom: '<"next-list-toolbar d-flex flex-wrap align-items-center justify-content-between"lf><"table-responsive"t><"next-list-pagination"ip>',
            columns: [
                {className: 'text-center', orderable: false, searchable: false, data: null, defaultContent: '<button type="button" class="btn btn-outline-secondary btn-sm next-table-cell-action item-detail-toggle" aria-expanded="false" aria-label="Ver detalle del Item"><i class="fas fa-chevron-down" aria-hidden="true"></i></button>'},
                {data: 'material_description', name: 'material.full_name', render: function (value, type) { return type === 'display' ? text(value) : value; }},
                {data: 'code', name: 'code', className: 'text-center', render: function (value, type) { return type === 'display' ? text(value) : value; }},
                {data: 'price', name: 'price', className: 'text-right', render: function (value, type) { return type === 'display' ? text(value) : value; }},
                {data: 'percentage', name: 'percentage', className: 'text-right', render: function (value, type) { return type === 'display' ? text(value) : value; }},
                {data: 'state_item', orderable: false, searchable: false, className: 'text-center', render: function (value, type) { return type === 'display' ? state(value) : value; }}
            ],
            order: [],
            language: {processing: 'Consultando…', search: 'Buscar:', lengthMenu: 'Mostrar _MENU_', info: 'Mostrando _START_ a _END_ de _TOTAL_ Items', infoEmpty: 'Sin Items en este contexto', infoFiltered: '(de _MAX_ Items)', zeroRecords: 'No se encontraron Items.', emptyTable: 'No hay Items en este contexto.', paginate: {first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último'}},
            initComplete: function () { $table.closest('.table-responsive').attr({tabindex: '0', 'aria-label': 'Items; desplazamiento horizontal de tabla'}); }
        });
        $table.on('click', '.item-detail-toggle', function () {
            const row = table.row($(this).closest('tr'));
            const open = !row.child.isShown();
            if (open) row.child(detail(row.data())).show(); else row.child.hide();
            $(this).attr({'aria-expanded': String(open), 'aria-label': open ? 'Cerrar detalle del Item' : 'Ver detalle del Item'}).find('i').toggleClass('fa-chevron-up', open).toggleClass('fa-chevron-down', !open);
        });
    });
})(window.jQuery);
