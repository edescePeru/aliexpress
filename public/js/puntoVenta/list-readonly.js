/* Current Sales List read contract. No financial handlers or synthetic data. */
(function ($) {
    'use strict';
    let request, detailRequest, sequence = 0, detailSequence = 0, rows = new Map(), detailTrigger;
    const filterIds = {code: 'code', year: 'year', startDate: 'start', endDate: 'end',
        customer_id: 'filter_customer_id', payment_status: 'filter_payment_status',
        dispatch_status: 'filter_dispatch_status', cash_box_id: 'filter_cash_box_id',
        cash_box_subtype_id: 'filter_cash_box_subtype_id', invoice_status: 'filter_invoice_status',
        sale_status: 'filter_sale_status', created_by_id: 'filter_created_by_id'};
    function escape(value) { return $('<span>').text(value == null ? '—' : String(value)).html(); }
    function badge(text, color) { return '<span class="badge badge-' + color + '">' + escape(text) + '</span>'; }
    function headers() {
        const node = document.getElementById('sale-list-context');
        return {'X-Sales-Tenant': node.dataset.tenant, 'X-Sales-Company': node.dataset.company, 'X-Sales-Branch': node.dataset.branch};
    }
    function message(xhr) {
        const body = xhr.responseJSON || {};
        if (body.errors) return Object.keys(body.errors).map(key => [].concat(body.errors[key]).join(' ')).join(' ');
        if (xhr.status === 401 || xhr.status === 419) return 'La sesión ha caducado. Recarga la página.';
        if (xhr.status === 403) return 'No tienes permiso para esta consulta.';
        if (xhr.status === 404) return 'La venta no está disponible en el contexto actual.';
        return 'No se pudo completar la consulta. Recarga la página o vuelve a intentarlo.';
    }
    function closeMenus() { $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide'); }
    function load(page) {
        const token = ++sequence;
        if (request) request.abort();
        closeMenus(); rows.clear();
        $('#body-table, #pagination').empty();
        $('#sales-empty, #sales-table-wrap').prop('hidden', true);
        $('#sales-feedback').addClass('d-none').text('');
        $('#numberItems').text('—'); $('#textPagination').text('Cargando ventas…');
        const filters = {};
        Object.keys(filterIds).forEach(key => { filters[key] = $('#' + filterIds[key]).val() || ''; });
        request = $.ajax({url: '/dashboard/get/data/sales/' + page, method: 'GET', data: filters, headers: headers(), dataType: 'json'})
            .done(function (response) { if (token === sequence) render(response); })
            .fail(function (xhr, status) {
                if (status === 'abort' || token !== sequence) return;
                $('#sales-feedback').removeClass('d-none').text(message(xhr));
                $('#textPagination').text('Consulta no disponible');
            });
    }
    // Display backend payment states; never recompute amounts or percentages.
    function payment(row) {
        const states = {rojo: ['Menos del 50%', 'danger'], naranja: ['50% a 99.99%', 'warning'], verde: ['100% o más', 'success']};
        const state = states[row.estado_pago_parcial];
        return state ? badge(state[0], state[1]) : badge(row.pagos_parciales_venta === 'n' ? 'Pago completo' : 'No registrado', 'secondary');
    }
    function emission(row) {
        let color = 'secondary';
        if (row.sunat_status === 'Error' || row.has_rejected_credit_note) color = 'danger';
        else if (row.annulment_status === 'waiting_sunat_process' || row.annulment_status === 'requires_credit_note') color = 'warning';
        else if (row.annulment_status === 'pending' || row.has_pending_credit_note) color = 'info';
        else if (row.annulment_status === 'accepted') color = 'dark';
        else if (row.type_document === '01' || row.type_document === '03') color = 'success';
        return badge(row.estado_comprobante || 'SIN COMPROBANTE', color);
    }
    function safeDocument(url, id) {
        if (!url) return null;
        try {
            const parsed = new URL(url, window.location.href);
            if (parsed.origin !== window.location.origin) return null;
            const prefix = '/dashboard/sales/' + id + '/documents/';
            return parsed.pathname.indexOf(prefix) === 0 || parsed.pathname === '/dashboard/imprimir/documento/venta/' + id ? parsed.href : null;
        } catch (_) { return null; }
    }
    function actions(row) {
        const id = String(row.id), trigger = 'sales-actions-' + id;
        let links = '<button type="button" class="dropdown-item" role="menuitem" data-ver_detalles data-id="' + escape(id) + '"><i class="fas fa-eye next-row-action-item-icon" aria-hidden="true"></i>Ver detalle</button>';
        const permissions = JSON.parse(document.getElementById('sale-list-context').dataset.permissions || '{}');
        if (permissions.print) {
            const docs = [['print_url', row.print_label || 'Ver comprobante'], ['annulment_pdf_url_local', 'PDF anulación'],
                ['annulment_xml_url_local', 'XML anulación'], ['annulment_cdr_url_local', 'CDR anulación'],
                ['credit_note_pdf_url_local', 'PDF nota de crédito'], ['credit_note_xml_url_local', 'XML nota de crédito'],
                ['credit_note_cdr_url_local', 'CDR nota de crédito'], ['partial_credit_note_pdf_url_local', 'PDF nota de crédito parcial']];
            docs.forEach(function (doc) {
                const url = safeDocument(row[doc[0]], id);
                if (url) links += '<a class="dropdown-item" role="menuitem" href="' + escape(url) + '" target="_blank" rel="noopener">' + escape(doc[1]) + '</a>';
            });
        }
        return '<div class="dropdown next-row-actions"><button type="button" id="' + escape(trigger) + '" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de ' + escape(row.code) + '"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu" role="menu" aria-labelledby="' + escape(trigger) + '">' + links + '</div></div>';
    }
    function render(response) {
        const p = response.pagination;
        $('#numberItems').text(p.totalFilteredRecords);
        $('#textPagination').text('Mostrando ' + p.startRecord + ' a ' + p.endRecord + ' de ' + p.totalFilteredRecords + ' ventas.');
        $('#sales-empty').prop('hidden', response.data.length !== 0);
        $('#sales-table-wrap').prop('hidden', response.data.length === 0);
        response.data.forEach(function (row) {
            rows.set(String(row.id), row);
            const tr = $('<tr>');
            [['code', 'center'], ['date', 'center'], ['nombre_cliente', 'left'], ['created_by_name', 'left'], ['currency', 'center'], ['total', 'right'], ['tipo_pago', 'left']].forEach(function (col) {
                $('<td>').addClass('text-' + col[1]).text(row[col[0]] == null || row[col[0]] === '' ? 'No registrado' : row[col[0]]).appendTo(tr);
            });
            $('<td class="text-center">').html(payment(row)).appendTo(tr);
            const dispatch = Number(row.is_annulled) === 1 ? badge(row.annulment_accepted_at || 'ANULADA', 'secondary') : badge(row.dispatch_status || 'No registrado', row.dispatch_status === 'despachado' ? 'success' : 'warning');
            $('<td class="text-center">').html(dispatch).appendTo(tr);
            $('<td class="text-center" data-estado_comprobante>').html(emission(row)).appendTo(tr);
            $('<td class="text-center" data-buttons>').html(actions(row)).appendTo(tr);
            $('#body-table').append(tr);
        });
        pagination(p);
    }
    function pagination(p) {
        const current = Number(p.currentPage), total = Number(p.totalPages);
        $('#pagination').empty();
        function item(page, label, active) {
            const li = $('<li class="page-item">').toggleClass('active', !!active);
            $('<button type="button" class="page-link">').text(label).attr('data-item', page).attr('aria-label', 'Página ' + page).attr('aria-current', active ? 'page' : null).appendTo(li);
            $('#pagination').append(li);
        }
        if (!total) return;
        if (current > 1) item(current - 1, '‹');
        const pages = [1, total];
        for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) pages.push(i);
        let previous = 0;
        [...new Set(pages)].sort((a, b) => a - b).forEach(function (page) {
            if (page - previous > 1) $('#pagination').append('<li class="page-item disabled"><span class="page-link">…</span></li>');
            item(page, page, page === current); previous = page;
        });
        if (current < total) item(current + 1, '›');
    }
    function detail(id) {
        const row = rows.get(String(id));
        if (!row) return;
        detailTrigger = document.getElementById('sales-actions-' + id);
        closeMenus();
        const token = ++detailSequence;
        if (detailRequest) detailRequest.abort();
        $('#sales-detail-title').text('Detalle · ' + row.code);
        $('#sales-detail-summary').empty();
        $('#order-details-content').text('Cargando detalle…');
        $('#orderDetailsModal').modal('show');
        detailRequest = $.ajax({url: '/dashboard/sales/' + encodeURIComponent(id) + '/details', method: 'GET', headers: headers(), dataType: 'json'})
            .done(function (response) {
                if (token !== detailSequence) return;
                // Metadata came from the same guarded list, not a second unscoped lookup.
                [['Cliente', row.nombre_cliente], ['Usuario registrador', row.created_by_name], ['Fecha', row.date], ['Moneda', row.currency],
                    ['Importe' + (row.pagos_parciales_venta === 's' ? ' (abonado / total)' : ''), row.total], ['Método de pago', row.tipo_pago], ['Comprobante / emisión', row.estado_comprobante],
                    ['Despacho', row.dispatch_status], ['Anulación', row.estado_anulacion]].forEach(function (field) {
                    const col = $('<div class="col-md-6 col-lg-4 mb-3 text-break">');
                    $('<strong class="d-block">').text(field[0]).appendTo(col);
                    $('<span>').text(field[1] || 'No registrado').appendTo(col); $('#sales-detail-summary').append(col);
                });
                const content = $('<div class="table-responsive" tabindex="0" aria-label="Productos de la venta">');
                const table = $('<table class="table table-sm table-bordered next-data-table">').append('<thead><tr><th class="text-center">Código</th><th class="text-left">Producto</th><th class="text-center">Cantidad</th><th class="text-right">Precio</th><th class="text-right">Total</th></tr></thead>');
                const body = $('<tbody>');
                response.details.forEach(function (line) {
                    const tr = $('<tr>');
                    [['code', 'center'], ['producto', 'left'], ['quantity', 'center'], ['price', 'right'], ['total', 'right']].forEach(col => $('<td>').addClass('text-' + col[1]).text(line[col[0]]).appendTo(tr));
                    body.append(tr);
                });
                if (!response.details.length) body.append('<tr><td colspan="5" class="text-center">Sin líneas registradas</td></tr>');
                table.append(body); content.append(table); $('#order-details-content').empty().append(content);
            }).fail(function (xhr, status) {
                if (status !== 'abort' && token === detailSequence) $('#order-details-content').empty().append($('<div class="alert alert-danger" role="alert">').text(message(xhr)));
            });
    }
    $(function () {
        $('.select2').select2({width: '100%', theme: 'bootstrap4'});
        $('#sandbox-container .input-daterange').datepicker({format: 'dd/mm/yyyy', language: 'es', clearBtn: true, autoclose: true});
        $('.next-list-toolbar').on('submit', function (event) { event.preventDefault(); load(1); });
        $('#btnBusquedaAvanzada').on('click', function () {
            $(this).attr('aria-expanded', String($(this).attr('aria-expanded') !== 'true'));
            $('.busqueda-avanzada').stop(true, true).slideToggle();
        });
        $('#filter_cash_box_id').on('change', function () {
            const uses = $(this).find(':selected').attr('data-uses_subtypes') === '1';
            $('#filter_cash_box_subtype_wrap').prop('hidden', !uses);
            if (!uses) $('#filter_cash_box_subtype_id').val('').trigger('change.select2');
        });
        $('.next-list-toolbar select, .date-range-filter').on('change', function () { load(1); });
        $('#sales-reset').on('click', function () {
            $('.next-list-toolbar')[0].reset();
            $('.select2').trigger('change.select2');
            $('#sandbox-container .input-daterange input').datepicker('clearDates');
            $('#filter_cash_box_subtype_wrap').prop('hidden', true); load(1);
        });
        $('#pagination').on('click', '[data-item]', function () { load(Number($(this).attr('data-item'))); });
        $(document).on('click', '[data-ver_detalles]', function () { detail($(this).attr('data-id')); });
        $(document).on('hidden.bs.dropdown', '.next-row-actions', function () {
            $(this).children('.next-row-actions-trigger').attr('aria-expanded', 'false');
        });
        $('#orderDetailsModal').on('hidden.bs.modal', function () {
            ++detailSequence; if (detailRequest) detailRequest.abort();
            if (detailTrigger && document.contains(detailTrigger)) detailTrigger.focus();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const menu = event.target.closest('.next-row-actions-menu');
            const id = menu && menu.getAttribute('aria-labelledby');
            if (!id || id.indexOf('sales-actions-') !== 0) return;
            event.preventDefault(); event.stopPropagation();
            const trigger = document.getElementById(id); $(trigger).dropdown('hide'); trigger.focus();
        }, true);
        load(1);
    });
})(jQuery);
