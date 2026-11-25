/* Physical Items with contextual code editing; stock is never changed here. */
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
                {data: 'code', name: 'code', className: 'text-center', render: function (value, type, row) {
                    if (type !== 'display') return value;
                    const id = text(row.id).replace(/"/g, '&quot;');
                    const action = window.canEditItemCode ? '<div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del Item ' + id + '"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><button type="button" class="dropdown-item btn-edit-item-code" data-item-id="' + id + '"><i class="fas fa-edit next-row-action-item-icon" aria-hidden="true"></i>Editar código</button></div></div>' : '';
                    return '<div class="d-flex align-items-center justify-content-between"><span class="text-break">' + text(value) + '</span>' + action + '</div>';
                }},
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
        let saving = false, codeTrigger;
        // The shared dropdown may be portalled outside the table: resolve data by Item ID.
        $(document).on('click', '.btn-edit-item-code', function () {
            if (!window.canEditItemCode || saving) return;
            const id = $(this).data('item-id');
            const row = table.rows().data().toArray().find(item => String(item.id) === String(id));
            if (!row) return;
            const owner = $(this).closest('.next-row-actions-menu').data('nextRowActionsOwner');
            codeTrigger = owner ? $(owner).find('.next-row-actions-trigger')[0] : $(this).closest('.next-row-actions').find('.next-row-actions-trigger')[0];
            $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
            $('#edit_item_id').val(id); $('#edit_item_code').val(row.code || '').removeClass('is-invalid');
            $('#item-code-error').addClass('d-none').empty();
            $('#modalEditarCodigoItem').modal('show');
        });
        $('#modalEditarCodigoItem').on('shown.bs.modal', function () { $('#edit_item_code').trigger('focus'); }).on('hidden.bs.modal', function () {
            if (codeTrigger && document.contains(codeTrigger)) codeTrigger.focus();
        });
        $('#formEditarCodigoItem').on('submit', function (event) {
            event.preventDefault();
            if (saving || !window.canEditItemCode) return;
            const id = $('#edit_item_id').val(), code = $('#edit_item_code').val().trim();
            if (!id || !code) { $('#edit_item_code').addClass('is-invalid').trigger('focus'); return; }
            saving = true; $('#btnGuardarCodigoItem').prop('disabled', true);
            $('#item-code-error').addClass('d-none').empty(); $('#edit_item_code').removeClass('is-invalid');
            $.ajax({url: window.stockItemCodeUrl.replace(':id', encodeURIComponent(id)), type: 'PUT', data: {code: code, _token: $('#formEditarCodigoItem input[name="_token"]').val()}})
                .done(function (response) { toastr.success(response.message || 'Código actualizado correctamente.'); $('#modalEditarCodigoItem').modal('hide'); table.ajax.reload(null, false); })
                .fail(function (xhr) {
                    const data = xhr.responseJSON || {}, errors = data.errors && Object.values(data.errors);
                    $('#item-code-error').removeClass('d-none').text(errors && errors.length ? [].concat(errors[0])[0] : data.message || 'No se pudo actualizar el código.');
                    if (data.errors && data.errors.code) $('#edit_item_code').addClass('is-invalid').trigger('focus');
                }).always(function () { saving = false; $('#btnGuardarCodigoItem').prop('disabled', false); });
        });
    });
})(window.jQuery);
