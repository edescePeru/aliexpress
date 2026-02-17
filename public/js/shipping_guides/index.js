(function ($) {

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

    function badgeStatus(status) {
        if (status === 'ACCEPTED') return '<span class="badge badge-success">ACEPTADA</span>';
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

        // Consultar si está pendiente / o no tiene links aún
        if (g.status === 'PENDING_SUNAT') {
            parts.push(`<button class="btn btn-sm btn-outline-warning ml-1 btnConsultar" data-id="${g.id}">Consultar</button>`);
        }

        // Links si accepted
        if (g.status === 'ACCEPTED') {
            if (g.pdf_link) parts.push(`<a class="btn btn-sm btn-outline-success ml-1" target="_blank" href="${g.pdf_link}">PDF</a>`);
            if (g.xml_link) parts.push(`<a class="btn btn-sm btn-outline-secondary ml-1" target="_blank" href="${g.xml_link}">XML</a>`);
            if (g.cdr_link) parts.push(`<a class="btn btn-sm btn-outline-secondary ml-1" target="_blank" href="${g.cdr_link}">CDR</a>`);
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
                        <td>${badgeStatus(g.status)}</td>
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

    $(document).ready(function () {
        loadList();
    });

})(jQuery);
