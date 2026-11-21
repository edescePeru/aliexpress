(function ($) {

    function downloadExport(dateFrom, dateTo) {
        // Descargar archivo (NO AJAX) -> form POST invisible
        const $form = $('<form>', {
            method: 'POST',
            action: window.routes.export
        });

        $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $form.append($('<input>', { type: 'hidden', name: 'date_from', value: dateFrom }));
        $form.append($('<input>', { type: 'hidden', name: 'date_to', value: dateTo }));

        $('body').append($form);
        $form.trigger('submit');
        $form.remove();
    }

    let page = 1;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function getFilters() {
        return {
            page: page,
            date_from: $('#fDesde').val() || '',
            date_to: $('#fHasta').val() || '',
            buscar_doc: $('#fBuscarDoc').val() || ''
        };
    }

    function fmtDate(dateStr) {
        if (!dateStr) return '';
        // si viene con Z (UTC), lo formateamos igual a DD/MM/YYYY
        return moment(dateStr).format('DD/MM/YYYY');
    }

    function badgeStatus(status, conflict) {
        if (conflict) return '<span class="badge badge-danger">CONFLICTO DE NUMERACIÓN</span>';
        if (status === 'ACCEPTED') return '<span class="badge badge-success">ACEPTADA</span>';
        if (status === 'SENT') return '<span class="badge badge-warning">POR CONFIRMAR</span>';
        if (status === 'PENDING_SUNAT') return '<span class="badge badge-warning">PENDIENTE</span>';
        if (status === 'REJECTED') return '<span class="badge badge-danger">RECHAZADA</span>';
        if (status === 'ERROR') return '<span class="badge badge-danger">ERROR</span>';
        return '<span class="badge badge-secondary">' + (status || '-') + '</span>';
    }

    function buildActions(g) {
        const urlShow = window.routes.showView.replace(':id', g.id);
        const parts = [];

        // Ver readonly
        parts.push(`<a class="btn btn-sm btn-outline-primary" href="${urlShow}">Ver</a>`);

        if (g.identity_conflict) return parts.join(' ');

        // Consultar si está pendiente / o no tiene links aún
        if (window.greCanConsult && ['PENDING_SUNAT', 'SENT', 'ERROR'].includes(g.status)) {
            parts.push(`<button class="btn btn-sm btn-outline-warning ml-1 btnConsultar" data-id="${g.id}">Consultar</button>`);
        }

        // Links si accepted
        if (g.status === 'ACCEPTED') {
            const available = g.documents_available || {};
            ['pdf', 'xml', 'cdr'].forEach(function (type) {
                if (!available[type] || !(type === 'pdf' ? window.greCanPdf : window.greCanDocuments)) return;
                const link = window.routes.document.replace(':id', g.id).replace(':type', type);
                parts.push(`<a class="btn btn-sm btn-outline-secondary ml-1" target="_blank" rel="noopener noreferrer" href="${link}">${type.toUpperCase()}</a>`);
            });
        }

        return parts.join(' ');
    }

    function renderRows(paginated) {
        let rows = [];

        if (!paginated.data || paginated.data.length === 0) {
            rows.push('<tr><td colspan="7" class="text-center p-4">No existen registros</td></tr>');
        } else {

            paginated.data.forEach(function (g) {

                const motivo = g.motivo_traslado_name
                    ? `${g.motivo_traslado_code} - ${g.motivo_traslado_name}`
                    : (g.motivo_traslado_code || '');

                rows.push(`
                    <tr data-row-id="${g.id}">
                        <td>Remitente</td>
                        <td>${g.serie}-${g.numero ?? ''}</td>
                        <td>${fmtDate(g.fecha_emision)}</td>
                        <td>${motivo}</td>
                        <td>${g.partida_ubigeo ?? ''} → ${g.llegada_ubigeo ?? ''}</td>
                        <td>${badgeStatus(g.status, g.identity_conflict)}</td>
                        <td class="text-right">
                            ${buildActions(g)}
                        </td>
                    </tr>
                `);
            });
        }

        $('#tbodyGuides').html(rows.join(''));

        $('#paginationInfo').text(
            `Página ${paginated.current_page} de ${paginated.last_page} | Total: ${paginated.total}`
        );

        $('#prevPage').prop('disabled', paginated.current_page <= 1);
        $('#nextPage').prop('disabled', paginated.current_page >= paginated.last_page);
    }

    function loadList() {
        $('#tbodyGuides').html('<tr><td colspan="7" class="text-center p-4">Cargando...</td></tr>');

        $.get(window.routes.list, getFilters())
            .done(function (res) {
                renderRows(res.data);
            })
            .fail(function () {
                toastr.error('Error cargando guías');
            });
    }

    // Consultar fila
    $(document).on('click', '.btnConsultar', function () {
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.post(window.routes.consult.replace(':id', id))
            .done(function (res) {
                toastr.success(res.message || 'Consulta OK');
                loadList(); // MVP: recarga lista
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error consultando');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $(document).on('click', '#btnBuscar', function () {
        page = 1;
        loadList();
    });

    $(document).on('click', '#prevPage', function () {
        if (page > 1) {
            page--;
            loadList();
        }
    });

    $(document).on('click', '#nextPage', function () {
        page++;
        loadList();
    });

    // Emitir → redirigir a create
    $(document).on('click', '#btnEmitir', function () {
        $.confirm({
            title: 'Elegir tipo de Guía',
            content: '',
            buttons: {
                Remitente: {
                    btnClass: 'btn-primary',
                    action: function () {
                        window.location.href = window.routes.create + '?type=remitente';
                    }
                },
                Cancelar: function () {}
            }
        });
    });

    function showSubmissionResult() {
        const url = new URL(window.location.href);
        const code = url.searchParams.get('gre_result');
        const reference = url.searchParams.get('gre_ref');
        const notices = {
            accepted: ['success', 'Guía enviada y aceptada por SUNAT. Ya puedes revisar sus documentos.'],
            pending: ['info', 'Guía enviada a Nubefact. La respuesta de SUNAT está pendiente. Usa Consultar para actualizar el estado.'],
            rejected: ['danger', 'SUNAT rechazó la guía. Revisa el detalle antes de realizar otra acción.'],
            existing: ['info', 'La guía ya estaba guardada. Se abrió el listado sin repetir el envío.'],
            uncertain: ['warning', 'No se pudo confirmar el envío. Revisa la guía en el listado y consulta su estado antes de crear o enviar otra.']
        };
        if (code && Object.prototype.hasOwnProperty.call(notices, code)) {
            const notice = notices[code];
            let message = notice[1];
            if (reference && /^[a-f0-9]{24}$/.test(reference)) message += ' Referencia de diagnóstico: ' + reference;
            $('#greSubmissionNotice').removeAttr('hidden').addClass('alert alert-' + notice[0]).text(message);
        }
        if (url.searchParams.has('gre_result') || url.searchParams.has('gre_ref')) {
            url.searchParams.delete('gre_result');
            url.searchParams.delete('gre_ref');
            window.history.replaceState(window.history.state, '', url.toString());
        }
    }

    $(document).ready(function () {
        showSubmissionResult();
        loadList();

        // Abrir modal
        $(document).on('click', '#btnExport', function () {
            $('#modalExportGuides').modal('show');
        });

        // Descargar
        $(document).on('click', '#btnDoExport', function () {
            const from = $('#expDateFrom').val();
            const to = $('#expDateTo').val();

            if (!from || !to) {
                toastr.error('Selecciona las dos fechas.');
                return;
            }
            if (from > to) {
                toastr.error('La fecha "Desde" no puede ser mayor que "Hasta".');
                return;
            }

            $('#modalExportGuides').modal('hide');
            downloadExport(from, to);
        });
    });

})(jQuery);
