(function ($) {

    let page = 1;

    function getFilters() {
        return {
            page: page,
            date_from: $('#fDesde').val() || '',
            date_to: $('#fHasta').val() || '',
            buscar_doc: $('#fBuscarDoc').val() || ''
        };
    }

    function renderRows(paginated) {

        let rows = [];

        if (!paginated.data || paginated.data.length === 0) {
            rows.push('<tr><td colspan="7" class="text-center p-4">No existen registros</td></tr>');
        } else {

            paginated.data.forEach(function (g) {

                let estadoBadge = g.status;

                if (g.status === 'ACCEPTED') {
                    estadoBadge = '<span class="badge badge-success">ACEPTADA</span>';
                } else if (g.status === 'PENDING_SUNAT') {
                    estadoBadge = '<span class="badge badge-warning">PENDIENTE</span>';
                } else if (g.status === 'REJECTED') {
                    estadoBadge = '<span class="badge badge-danger">RECHAZADA</span>';
                }

                rows.push(`
                    <tr>
                        <td>Remitente</td>
                        <td>${g.serie}-${g.numero ?? ''}</td>
                        <td>${g.fecha_emision ?? ''}</td>
                        <td>${g.motivo_traslado_code ?? ''}</td>
                        <td>${g.partida_ubigeo ?? ''} → ${g.llegada_ubigeo ?? ''}</td>
                        <td>${estadoBadge}</td>
                        <td class="text-right">
                            <button class="btn btn-sm btn-outline-primary btnVer" data-id="${g.id}">
                                Ver
                            </button>
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

    /* =========================
       Eventos
    ==========================*/

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

    // Export
    $(document).on('click', '#btnExport', function () {
        $.confirm({
            title: 'Exportar',
            content: '¿Exportar todo el rango filtrado?',
            buttons: {
                Si: function () {
                    toastr.info('Exportación pendiente de implementación');
                },
                No: function () {}
            }
        });
    });

    /* =========================
       Inicialización
    ==========================*/

    $(document).ready(function () {
        loadList();
    });

})(jQuery);
